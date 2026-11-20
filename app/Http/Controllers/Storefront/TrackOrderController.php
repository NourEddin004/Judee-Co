<?php

namespace App\Http\Controllers\Storefront;

use App\Domain\Orders\OrderSearch;
use App\Domain\Orders\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Track order" (2026-11-20): the customer types the order number from the receipt and sees where
 * the order is. No sign-in (the owner declined accounts), so the answer holds only what is safe
 * to show to anyone who has the number: status, date, number of pieces, total and city. Never the
 * name, phone or street. The menu's popup asks for JSON; without scripts the page shows the same.
 */
class TrackOrderController extends Controller
{
    public function __invoke(Request $request): View|JsonResponse
    {
        $typed = trim((string) $request->query('number', ''));
        $order = null;
        $error = null;

        if ($typed !== '') {
            $number = OrderSearch::normalizeNumber(mb_substr($typed, 0, 20));
            $order = $number === null ? null : Order::query()->with('items')->where('number', $number)->first();
            $error = $number === null ? 'invalid' : ($order === null ? 'not_found' : null);
        }

        $result = $order ? self::present($order) : null;

        if ($request->expectsJson()) {
            return response()->json($result ? ['order' => $result, 'html' => view('storefront.partials.track-result', ['result' => $result])->render()] : ['error' => $error ?? 'invalid', 'message' => __('store.track.error_'.($error ?? 'invalid'))], $result ? 200 : 404)
                ->header('Cache-Control', 'no-store');
        }

        return view('storefront.track', ['typed' => $typed, 'result' => $result, 'error' => $error]);
    }

    /** The public view of an order. Steps are the normal life of an order; a cancelled one has none. */
    public static function present(Order $order): array
    {
        $steps = [OrderStatus::New, OrderStatus::Confirmed, OrderStatus::OutForDelivery, OrderStatus::Delivered];
        $reached = array_search($order->status, $steps, true);
        $locale = app()->getLocale();
        $pieces = $order->itemCount();

        return [
            'number' => $order->number,
            'status' => $order->status->value,
            'status_label' => __('store.track.status.'.$order->status->value),
            'message' => __('store.track.message.'.$order->status->value),
            'cancelled' => $order->status === OrderStatus::Cancelled,
            'steps' => array_map(fn (OrderStatus $step, int $i) => [
                'label' => __('store.track.status.'.$step->value),
                'done' => $reached !== false && $i <= $reached,
                'current' => $reached === $i,
            ], $steps, array_keys($steps)),
            'placed' => $order->placed_at?->locale($locale)->translatedFormat('j F Y'),
            'pieces' => trans_choice('store.track.pieces', $pieces, ['count' => $pieces]),
            'total' => Money::jod($order->total),
            'total_jod' => (string) $order->total,
            'city' => $order->city_name,
        ];
    }
}
