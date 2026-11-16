<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Resources\Colors\Pages\ManageColors;
use App\Filament\Resources\SizeGuides\Pages\ManageSizeGuides;
use App\Models\Category;
use App\Models\Color;
use App\Models\Shade;
use App\Models\SizeGuide;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsAdmin;
use Tests\TestCase;

/** Categories, colors, collections, sizes and size guides (REQ-CMS-14 to REQ-CMS-18). */
class CatalogListsAdminTest extends TestCase
{
    use ActsAsAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->asOwner();
    }

    #[Test]
    public function req_cms_14_manage_categories(): void
    {
        Livewire::test(ManageCategories::class)
            ->callAction('create', ['name_en' => 'Abayas', 'name_ar' => 'عبايات', 'slug' => 'abayas', 'page_title_en' => 'Abayas'])
            ->assertHasNoFormErrors();
        $abayas = Category::query()->where('slug', 'abayas')->firstOrFail();
        $this->get('/abayas')->assertOk()->assertSee('Abayas');

        Livewire::test(ManageCategories::class)
            ->callAction(TestAction::make('edit')->table($abayas), ['name_en' => 'Abayas & Dresses'])
            ->callAction(TestAction::make('delete')->table($abayas));
        $this->assertModelMissing($abayas);
    }

    #[Test]
    public function req_cms_41_a_category_with_products_is_not_deleted(): void
    {
        $sets = Category::query()->where('slug', 'sets')->firstOrFail();

        Livewire::test(ManageCategories::class)
            ->callAction(TestAction::make('delete')->table($sets))
            ->assertNotified('This cannot be deleted');

        $this->assertModelExists($sets);
    }

    #[Test]
    public function req_cms_15_manage_colors(): void
    {
        Livewire::test(ManageColors::class)
            ->callAction('create', ['name_en' => 'Lilac', 'name_ar' => 'ليلكي', 'shade_id' => Shade::query()->where('key', 'purple')->value('id')])
            ->assertHasNoFormErrors();
        $lilac = Color::query()->where('name_en', 'Lilac')->firstOrFail();

        Livewire::test(ManageColors::class)
            ->callAction(TestAction::make('edit')->table($lilac), ['name_en' => 'Soft Lilac'])
            ->callAction(TestAction::make('delete')->table($lilac->refresh()));
        $this->assertModelMissing($lilac);
    }

    #[Test]
    public function req_cms_18_edit_the_size_guides(): void
    {
        $guide = SizeGuide::query()->firstOrFail();

        Livewire::test(ManageSizeGuides::class)
            ->callAction(TestAction::make('edit')->table($guide), [
                'content_en' => "Size | Bust (cm)\nS | 80–84\nM | 84–88",
            ])
            ->assertHasNoFormErrors();

        $guide->refresh();
        $this->assertEquals(['columns' => ['Size', 'Bust (cm)'], 'rows' => [['S', '80–84'], ['M', '84–88']]], $guide->content_en);
        $this->assertSame(['المقاس', 'الصدر (سم)', 'الخصر (سم)', 'الورك (سم)'], $guide->content_ar['columns']);   // Arabic kept intact

        Livewire::test(ManageSizeGuides::class)
            ->callAction(TestAction::make('edit')->table($guide), ['content_en' => "Size | Bust\nS"])
            ->assertHasFormErrors(['content_en']);
    }
}
