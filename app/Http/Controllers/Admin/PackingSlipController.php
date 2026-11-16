<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** A printable packing slip for one order (REQ-ORD-22). Admin session only (REQ-SEC-38). */
class PackingSlipController extends Controller
{
    public function __invoke(Order $order): View
    {
        Gate::authorize('view', $order);                     // REQ-SEC-39

        $order->load('items:id,order_id,product_name,color_name,size,unit_price,quantity');

        return view('admin.packing-slip', ['order' => $order]);
    }
}
