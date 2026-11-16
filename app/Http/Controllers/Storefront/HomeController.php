<?php

namespace App\Http\Controllers\Storefront;

use App\Domain\Content\SiteContent;
use App\Domain\Rack\HeroRack;
use App\Http\Controllers\Controller;
use App\Models\ProductColor;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(HeroRack $rack, SiteContent $content): View
    {
        // The three looks are product colours the owner picks, in the owner's order (REQ-CMS-20).
        $lookIds = array_map('intval', array_filter(explode(',', $content->text('home.about.looks'))));
        $looks = ProductColor::query()
            ->whereKey($lookIds)
            ->whereHas('product')
            ->select(['id', 'product_id', 'color_id', 'position'])
            ->with([
                'product' => fn ($query) => $query->select(['id', 'slug', 'name_en', 'name_ar'])->with('colors:id,product_id,position'),
                'color:id,slug,name_en,name_ar',
                'photos' => fn ($query) => $query->select(['id', 'product_color_id', 'path', 'is_main', 'is_hover', 'position']),
            ])
            ->get()
            ->sortBy(fn (ProductColor $look) => array_search($look->id, $lookIds, true))
            ->take(3)
            ->values();

        return view('storefront.home', [
            'rack' => $rack->items(),
            'looks' => $looks,
            'reviews' => $content->reviews(),
        ]);
    }
}
