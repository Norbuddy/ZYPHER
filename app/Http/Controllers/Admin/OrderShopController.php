<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\OrderShopStatus;
use App\Models\OrderShop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderShopController extends Controller
{
    public function index(Request $request): Response
    {
        $orderShops = OrderShop::with('team:id,name', 'order.user:id,name')
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/Orders/Index', ['orderShops' => $orderShops]);
    }

    /** Admin resolves a dispute by forcing a refund on a specific order_shop. */
    public function refund(Request $request, OrderShop $orderShop): RedirectResponse
    {
        $orderShop->transitionTo(OrderShopStatus::Refunded, $request->user()->id, 'Refunded by admin after dispute resolution.');

        return back()->with('success', 'Order shop marked as refunded.');
    }
}
