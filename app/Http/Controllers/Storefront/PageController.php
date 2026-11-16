<?php

namespace App\Http\Controllers\Storefront;

use App\Domain\Content\SiteContent;
use App\Domain\Delivery\DeliveryList;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

/** The bag page, whose list lives in the customer's browser, checkout and the content pages. */
class PageController extends Controller
{
    public function cart(): View
    {
        return view('storefront.cart', ['delivery' => app(DeliveryList::class)->cities()]);
    }

    public function checkout(DeliveryList $delivery): View
    {
        return view('storefront.checkout', ['cities' => $delivery->jordanCities(), 'gulf' => $delivery->gulfCountries()]);
    }

    public function contact(SiteContent $content): View
    {
        return view('storefront.contact', ['faqs' => $content->faqs()]);
    }

    public function terms(SiteContent $content): View
    {
        return view('storefront.terms', ['sections' => $content->policy('terms')]);
    }
}
