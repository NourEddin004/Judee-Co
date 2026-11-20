<?php

namespace App\Providers\Filament;

use App\Filament\Auth\EditProfile;
use App\Filament\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Filament\Support\InitialsAvatar;
use App\Http\Controllers\Admin\PackingSlipController;
use App\Http\Middleware\AdminLocale;
use App\Http\Middleware\RequireOwnerTwoStep;
use App\Support\DesignTokens;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\BasePage;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Schemas\Components\Component;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/** The store admin (SRS 3.2, 3.5, 3.6, 4.1), laid out like the admin panel preview. */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('JUDE')
            // The same pink JUDE logo as the website.
            ->brandLogo(fn () => asset('images/jude-logo.png'))
            ->brandLogoHeight('1.75rem')
            ->favicon(asset('favicon-32.png'))
            ->login(Login::class)
            ->passwordReset()                                   // REQ-USR-09
            ->profile(EditProfile::class, isSimple: false)
            // No search box in the top bar: the admin is kept simple.
            ->globalSearch(false)
            ->multiFactorAuthentication(
                [AppAuthentication::make()->brandName('JUDE')->recoverable()],
                isRequired: true,
            )
            // Required for the owner, the only account (REQ-USR-03).
            ->multiFactorAuthenticationRequiredMiddlewareName(RequireOwnerTwoStep::class)
            ->colors([
                'primary' => DesignTokens::rosePalette(),
                'gray' => Color::hex(DesignTokens::color('ink-muted')),
                'danger' => Color::hex(DesignTokens::color('bad')),
                'success' => Color::hex(DesignTokens::color('ok')),
                'warning' => Color::hex(DesignTokens::color('warn')),
                'info' => Color::hex(DesignTokens::color('info')),
            ])
            ->font('Montserrat', provider: LocalFontProvider::class)
            ->defaultAvatarProvider(InitialsAvatar::class)
            ->darkMode(false)
            ->sidebarWidth('15rem')
            ->maxContentWidth('6xl')
            ->navigationGroups([
                // Keyed by name so the resources' 'Products' group matches; the label is translated.
                'Products' => NavigationGroup::make()->label(fn () => __('Products')),
            ])
            // "My account" in the menu, so the email and password are easy to find.
            ->navigationItems([
                NavigationItem::make('My account')
                    ->label(fn () => __('My account'))
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->url(fn () => EditProfile::getUrl())
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.auth.profile'))
                    ->sort(99),
            ])
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => Blade::render("@vite(['resources/css/admin.css', 'resources/js/admin.js'])"))
            ->renderHook(PanelsRenderHook::BODY_END, fn () => view('admin.bottom-nav'))
            // A clear way back to the dashboard from every other admin page.
            ->renderHook(PanelsRenderHook::PAGE_START, fn () => view('admin.back-to-dashboard'))
            // "Prepared by RafaqTech" credit under every admin page, sign-in included.
            ->renderHook(PanelsRenderHook::FOOTER, fn () => view('admin.credit'))
            // English / العربية: the admin in either language, right to left in Arabic.
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn () => view('admin.language-switch'))
            ->renderHook(PanelsRenderHook::SIMPLE_PAGE_START, fn () => view('admin.language-switch'))
            ->routes(fn () => Route::get('language/{locale}', fn (Request $request, string $locale) => AdminLocale::switchTo($request, $locale))
                ->name('language'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            // Kept for every Livewire request too, so buttons and messages stay in the chosen language.
            ->middleware([AdminLocale::class], isPersistent: true)
            ->authMiddleware([
                Authenticate::class,                            // REQ-SEC-38
            ])
            // Pages outside Filament that still need an admin session and the owner's two-step login.
            ->authenticatedRoutes(fn () => Route::get('orders/{order}/packing-slip', PackingSlipController::class)
                ->middleware(RequireOwnerTwoStep::class)
                ->name('orders.packing-slip'));
    }

    public function boot(): void
    {
        // Admin lists load 25 rows per page (REQ-DB-27).
        Table::configureUsing(fn (Table $table) => $table
            ->paginated([25])
            ->defaultPaginationPageOption(25));

        // Labels Filament makes from field names ("price" -> "Price") are looked up in lang/ar.json too.
        Component::configureUsing(fn (Component $component) => method_exists($component, 'translateLabel') ? $component->translateLabel() : null);
        Column::configureUsing(fn (Column $column) => $column->translateLabel());
        BaseFilter::configureUsing(fn (BaseFilter $filter) => $filter->translateLabel());

        // A form's Save and Create buttons stay on screen while the owner scrolls (REQ-UX-61).
        BasePage::stickyFormActions();
    }
}
