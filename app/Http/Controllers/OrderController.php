<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = $request->user()->orders()
            ->with([
                'orderShops.team:id,name,slug',
                'orderShops.items.variant.product.images',
            ])
            ->latest()
            ->paginate(10);

        return Inertia::render('Account/Orders', [
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $order->load(['orderShops.items.variant', 'orderShops.team', 'orderShops.statusHistories', 'address']);

        return Inertia::render('Account/OrderTracking', ['order' => $order]);
    }

    /** Customer confirms receipt - moves order_shop to completed. */
    public function confirmReceipt(Request $request, int $orderShopId): \Illuminate\Http\RedirectResponse
    {
        $orderShop = \App\Models\OrderShop::whereHas('order', fn ($q) => $q->where('user_id', $request->user()->id))
            ->findOrFail($orderShopId);

        abort_unless($orderShop->status === \App\Enums\OrderShopStatus::Shipped, 422);

        $orderShop->transitionTo(\App\Enums\OrderShopStatus::Completed, $request->user()->id, 'Customer confirmed receipt.');

        return back()->with('success', 'Order marked as received. You can now leave a review.');
    }
}
