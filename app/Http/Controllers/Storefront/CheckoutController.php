<?php

namespace App\Http\Controllers\Storefront;

use App\Domain\Checkout\CheckoutRefused;
use App\Domain\Checkout\PlaceOrderAction;
use App\Domain\Orders\OrderReceipt;
use App\Domain\Orders\OrderRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlaceOrderRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\HeaderUtils;

/** Places the order the browser sends, and shows its confirmation page (SRS 3.3, C.6). */
class CheckoutController extends Controller
{
    /** At most 5 orders an hour from one IP address (REQ-SEC-24). */
    private const ORDERS_PER_HOUR = 5;

    public function place(PlaceOrderRequest $request, PlaceOrderAction $placeOrder): JsonResponse
    {
        $limit = 'orders:'.$request->ip();

        if (RateLimiter::tooManyAttempts($limit, self::ORDERS_PER_HOUR)) {
            return response()->json(['message' => __('store.checkout.too_many')], 429);
        }

        try {
            $order = $placeOrder->execute($request->toData());
        } catch (CheckoutRefused $refused) {
            return response()->json([
                'message' => match ($refused->reason) {
                    CheckoutRefused::PAUSED => __('store.paused.reason'),
                    CheckoutRefused::UNAVAILABLE => trans_choice('store.checkout.unavailable', count($refused->products), ['products' => implode(', ', $refused->products)]),
                    default => __('store.checkout.bag_changed'),
                },
                'reason' => $refused->reason,
                'ids' => $refused->productIds,
            ], 422);
        }

        RateLimiter::hit($limit, 3600);

        // Only the address of the confirmation page goes back: no customer data (REQ-SEC-36).
        return response()->json(['redirect' => store_route('confirmation', $order->link_token)], 201);
    }

    /** Opens only with the order's random link token (REQ-SEC-41); anything else goes to the shop (D.6.7). */
    public function confirmation(string $token, OrderRepository $orders): View|RedirectResponse
    {
        $order = $orders->findByToken($token);

        if ($order === null) {
            return redirect()->to(store_route('shop'));
        }

        // The photo of each piece's colour, while its product is still on the site, as in the demo.
        $order->load(['items.productColor' => fn ($query) => $query->select(['id', 'product_id'])->whereHas('product')->with('mainPhoto:id,product_color_id,path')]);

        return view('storefront.confirmation', ['order' => $order]);
    }

    /**
     * The order's PDF receipt, in the order's language (REQ-CHK-24, REQ-CHK-25). Only the random link
     * token opens it, and no cache may keep a copy (REQ-SEC-43).
     */
    public function receipt(string $token, OrderRepository $orders, OrderReceipt $receipt): Response
    {
        $order = $orders->findByToken($token);

        abort_if($order === null, 404);

        return response($receipt->pdf($order), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $receipt->filename($order)),
            'Cache-Control' => 'no-store, private',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Referrer-Policy' => 'same-origin',
        ]);
    }
}
