<?php

namespace App\Providers;

use App\Domain\Activity\ActivityLogger;
use App\Domain\Activity\DatabaseActivityLogger;
use App\Domain\Auth\AlertOwnerOfNewDevice;
use App\Domain\Auth\LockAfterFailedLogins;
use App\Domain\Catalog\CatalogData;
use App\Domain\Catalog\ProductStockChecker;
use App\Domain\Checkout\CartPriceCalculator;
use App\Domain\Checkout\CashOnDelivery;
use App\Domain\Checkout\DeliveryFeeCalculator;
use App\Domain\Checkout\OrderNotifier;
use App\Domain\Checkout\PaymentMethod;
use App\Domain\Checkout\PriceCalculator;
use App\Domain\Checkout\StockChecker;
use App\Domain\Checkout\StoreStatus;
use App\Domain\Content\SiteContent;
use App\Domain\Delivery\CityFlatFee;
use App\Domain\Delivery\DeliveryList;
use App\Domain\Media\ImageStore;
use App\Domain\Monitoring\DiskUsage;
use App\Domain\Orders\EloquentOrderRepository;
use App\Domain\Orders\OrderReceipt;
use App\Domain\Orders\OrderRepository;
use App\Domain\Rack\EloquentRackItemRepository;
use App\Domain\Rack\Image\AlignHookStep;
use App\Domain\Rack\Image\AlphaChannelChecker;
use App\Domain\Rack\Image\RackImagePipeline;
use App\Domain\Rack\Image\ReencodeStep;
use App\Domain\Rack\Image\ScaleToRackHeightStep;
use App\Domain\Rack\Image\TransparencyChecker;
use App\Domain\Rack\Image\TrimTransparentStep;
use App\Domain\Rack\Image\WebpVariantsStep;
use App\Domain\Rack\RackItemRepository;
use App\Domain\Settings\SettingsStoreStatus;
use App\Domain\Settings\StoreSettings;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Color;
use App\Models\ContentBlock;
use App\Models\DeliveryArea;
use App\Models\DeliveryCity;
use App\Models\Faq;
use App\Models\Order;
use App\Models\OrderNote;
use App\Models\PolicySection;
use App\Models\Product;
use App\Models\ProductPhoto;
use App\Models\RackItem;
use App\Models\Review;
use App\Models\Setting;
use App\Models\Shade;
use App\Models\Size;
use App\Models\SizeGuide;
use App\Models\User;
use App\Notifications\QueuedMailNotifier;
use App\Policies\ActivityLogPolicy;
use App\Policies\OrderNotePolicy;
use App\Policies\OrderPolicy;
use App\Policies\OwnerPolicy;
use App\Policies\UserPolicy;
use App\Support\StoreCache;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use RuntimeException;
use Spatie\Backup\Events\BackupZipWasCreated;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StoreCache::class, fn ($app) => new StoreCache($app['cache.store']));

        // Read once per request, then reused by every view of the page.
        $this->app->scoped(CatalogData::class);
        $this->app->scoped(SiteContent::class);
        $this->app->scoped(StoreSettings::class);
        $this->app->scoped(DeliveryList::class);

        // Modules depend on interfaces; the concrete classes are chosen here (C.13, dependency inversion).
        $this->app->bind(ActivityLogger::class, DatabaseActivityLogger::class);
        $this->app->bind(ImageStore::class, fn () => new ImageStore(Storage::disk('public')));
        $this->app->when(OrderReceipt::class)->needs(Filesystem::class)->give(fn () => Storage::disk('public'));
        $this->app->bind(DiskUsage::class, fn () => new DiskUsage(storage_path()));
        $this->app->bind(RackItemRepository::class, EloquentRackItemRepository::class);
        $this->app->bind(TransparencyChecker::class, AlphaChannelChecker::class);
        // Checkout (C.11): each rule behind its own small interface.
        $this->app->bind(StoreStatus::class, SettingsStoreStatus::class);
        $this->app->bind(StockChecker::class, ProductStockChecker::class);
        $this->app->bind(DeliveryFeeCalculator::class, CityFlatFee::class);
        $this->app->bind(PriceCalculator::class, CartPriceCalculator::class);
        $this->app->bind(PaymentMethod::class, CashOnDelivery::class);
        $this->app->bind(OrderRepository::class, EloquentOrderRepository::class);
        $this->app->bind(OrderNotifier::class, QueuedMailNotifier::class);
        $this->app->bind(RackImagePipeline::class, function () {
            $height = config('store.rack.height');

            return new RackImagePipeline([
                new ReencodeStep($height),
                new TrimTransparentStep,
                new ScaleToRackHeightStep($height),
                new AlignHookStep,
                new WebpVariantsStep(config('store.rack.variant_heights')),
            ]);
        });
    }

    public function boot(): void
    {
        // Admin passwords (My account and the reset page): at least 12 characters with a capital
        // letter, a small letter, a number and a symbol.
        Password::defaults(fn () => Password::min(12)->letters()->mixedCase()->numbers()->symbols());

        // Outside production, a one-query-per-item mistake fails loudly (REQ-DB-20).
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventAccessingMissingAttributes(! $this->app->isProduction());

        // Only the owner manages anything in the admin, checked on every request and action (C.1, REQ-SEC-39).
        foreach ([Category::class, Collection::class, Color::class, ContentBlock::class, DeliveryArea::class, DeliveryCity::class, Faq::class, PolicySection::class, Product::class, ProductPhoto::class, RackItem::class, Review::class, Setting::class, Shade::class, Size::class, SizeGuide::class] as $model) {
            Gate::policy($model, OwnerPolicy::class);
        }
        Gate::policy(ActivityLog::class, ActivityLogPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(OrderNote::class, OrderNotePolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Event::listen(Failed::class, [LockAfterFailedLogins::class, 'failed']);
        Event::listen(Login::class, [LockAfterFailedLogins::class, 'succeeded']);
        Event::listen(Login::class, AlertOwnerOfNewDevice::class);

        // Every backup is encrypted (REQ-SEC-28): without a password the backup stops before it is
        // copied anywhere, and the failure is emailed.
        Event::listen(BackupZipWasCreated::class, function () {
            if (blank(config('backup.backup.password'))) {
                throw new RuntimeException('BACKUP_ARCHIVE_PASSWORD is not set, and backups are always encrypted');
            }
        });

        RateLimiter::for('storefront', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
        // Order tracking: enough for a customer who mistypes, far too few to guess numbers.
        RateLimiter::for('track', fn (Request $request) => Limit::perMinute(15)->by($request->ip()));
    }
}
