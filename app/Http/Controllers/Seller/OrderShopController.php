<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Enums\OrderShopStatus;
use App\Http\Requests\Seller\UpdateOrderShopStatusRequest;
use App\Models\OrderShop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderShopController extends Controller
{
        public function index(Request $request): Response
    {
        $orderShops = $request->user()
            ->currentTeam
            ->orderShops()
            ->with([
                    'items.variant.product.images',
                    'order.user:id,name',
                    'order.address',
                    ])
            ->latest()
            ->get()
            ->map(function ($os) {
                $address = $os->order?->address;

                $productImageUrl = null;
                foreach ($os->items as $item) {
                    $product = $item->variant?->product;
                    if (! $product || $product->images->isEmpty()) {
                        continue;
                    }
                    $image = $product->images->firstWhere('is_primary', true)
                        ?? $product->images->first();
                    if ($image) {
                        $productImageUrl = $image->url;
                        break;
                    }
                }

                return [
                    'id' => $os->id,
                    'order_number' => $os->order?->order_number,
                    'product_names' => $os->items
                        ->pluck('product_name')
                        ->filter()
                        ->values()
                        ->all(),
                    'product_image_url' => $productImageUrl,
                    'customer_name' => $os->order?->user?->name ?? 'Customer',
                    'customer_address' => $address ? [
                        'full_name' => $address->full_name,
                        'phone' => $address->phone,
                        'line1' => $address->line1,
                        'line2' => $address->line2,
                        'city' => $address->city,
                        'province' => $address->province,
                        'postal_code' => $address->postal_code,
                        'latitude' => $address->latitude,
                        'longitude' => $address->longitude,
                    ] : null,
                    'subtotal' => (float) $os->subtotal,
                    'status' => $os->status?->value ?? (string) $os->status,
                    'created_at' => $os->created_at?->toIso8601String(),
                    'courier_name' => $os->courier_name,
                    'tracking_number' => $os->tracking_number,
                    'shipping_method' => $os->shipping_method,
                    'store_address' => $os->store_address,
                    'store_latitude' => $os->store_latitude,
                    'store_longitude' => $os->store_longitude,
                    'delivery_distance_km' => $os->delivery_distance_km,
                    'estimated_delivery_minutes' => $os->estimated_delivery_minutes,
                ];
            });

        return Inertia::render(
            'Seller/Orders/Index',
            [
                'orderShops' => $orderShops,
                
            ]
        );
    }

    public function markProcessing(Request $request, OrderShop $orderShop): RedirectResponse
    {
        $this->authorizeShopOwnership($request, $orderShop);

        $orderShop->transitionTo(OrderShopStatus::Processing, $request->user()->id);

        return back()->with('success', 'Order marked as processing.');
    }

    public function markShipped( UpdateOrderShopStatusRequest $request, OrderShop $orderShop): RedirectResponse 
    {
        $this->authorizeShopOwnership(
            $request,
            $orderShop
        );

        $data = $request->validated();

        // Courier is intentionally fixed.
        $data['courier_name'] = 'Lalamove';

        $orderShop->update([
            'courier_name' =>
                'Lalamove',

            'tracking_number' =>
                $data['tracking_number'] ?? null,

            'shipping_method' =>
                $data['shipping_method']
                ?? 'Lalamove Delivery',

            'store_address' =>
                $data['store_address'],

            'store_latitude' =>
                $data['store_latitude'],

            'store_longitude' =>
                $data['store_longitude'],

            'delivery_distance_km' =>
                $data['delivery_distance_km'],

            'estimated_delivery_minutes' =>
                $data['estimated_delivery_minutes'],
        ]);

        $orderShop->transitionTo(
            OrderShopStatus::Shipped,
            $request->user()->id
        );

        return back()->with(
            'success',
            'Order marked as shipped. Customer has been notified.'
        );
    }

    protected function authorizeShopOwnership(Request $request, OrderShop $orderShop): void
    {
        abort_unless($orderShop->team_id === $request->user()->currentTeam->id, 403);
    }
}
