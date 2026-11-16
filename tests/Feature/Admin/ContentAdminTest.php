<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Product;
use App\Models\ProductPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsAdmin;
use Tests\TestCase;

/** Product texts in English and Arabic side by side (REQ-CMS-38 to REQ-CMS-40). Site texts, reviews, FAQs and terms left the admin on 8 Nov 2026. */
class ContentAdminTest extends TestCase
{
    use ActsAsAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        foreach (ProductPhoto::query()->pluck('path') as $path) {
            Storage::disk('public')->put($path, 'image');
        }
        $this->asOwner();
    }

    #[Test]
    public function req_cms_38_req_cms_39_req_cms_40_every_text_in_english_and_arabic_side_by_side(): void
    {
        $product = Product::query()->where('slug', 'wrap-set')->firstOrFail();

        $page = Livewire::test(EditProduct::class, ['record' => $product->slug]);
        foreach (['name', 'description', 'note', 'badge'] as $field) {
            $page->assertFormFieldExists("{$field}_en")->assertFormFieldExists("{$field}_ar");
        }
        // Arabic fields are written right to left and sit in the same two-column row as the English ones.
        $page->assertSeeHtml('dir="rtl"');

        $page->fillForm(['name_ar' => 'طقم الليل'])->call('save')->assertHasNoFormErrors();
        $this->inArabic()->get('/product/wrap-set')->assertSee('طقم الليل');
        $this->get('/product/wrap-set')->assertDontSee('طقم الليل');
    }
}
