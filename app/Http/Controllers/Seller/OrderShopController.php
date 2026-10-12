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

        // Load seller's saved addresses from "My Address" for 1-click auto-locate
        $user = $request->user();
        $team = $user->currentTeam ?? $user->ownedTeams()->first() ?? $user->teams()->first();

        $addressesQuery = $user->addresses()->get();

        // If user is branch staff and has no personal addresses, check shop owner's saved addresses
        if ($addressesQuery->isEmpty() && $team && $team->user_id && $team->user_id !== $user->id) {
            $owner = $team->owner ?? \App\Models\User::find($team->user_id);
            if ($owner) {
                $addressesQuery = $owner->addresses()->get();
            }
        }

        $sellerAddresses = $addressesQuery->map(function ($addr) {
            $formatted = collect([
                $addr->line1,
                $addr->line2,
                $addr->city,
                $addr->province,
                $addr->postal_code,
            ])->filter()->join(', ');

            return [
                'id' => $addr->id,
                'full_name' => $addr->full_name,
                'phone' => $addr->phone,
                'line1' => $addr->line1,
                'line2' => $addr->line2,
                'city' => $addr->city,
                'province' => $addr->province,
                'postal_code' => $addr->postal_code,
                'formatted_address' => $formatted,
                'latitude' => $addr->latitude !== null ? (float) $addr->latitude : null,
                'longitude' => $addr->longitude !== null ? (float) $addr->longitude : null,
                'is_default' => (bool) $addr->is_default,
                'type' => 'my_address',
            ];
        });

        // If user is assigned to a branch, also make the branch address available
        if ($user->branch) {
            $branch = $user->branch;
            $branchFormatted = collect([
                $branch->address,
                $branch->barangay,
                $branch->city,
                $branch->province,
            ])->filter()->join(', ');

            $sellerAddresses->push([
                'id' => 'branch_' . $branch->id,
                'full_name' => $branch->name . ' (Branch)',
                'phone' => $branch->phone ?? '',
                'line1' => $branch->address,
                'line2' => $branch->barangay,
                'city' => $branch->city,
                'province' => $branch->province,
                'postal_code' => '',
                'formatted_address' => $branchFormatted,
                'latitude' => $branch->latitude !== null ? (float) $branch->latitude : null,
                'longitude' => $branch->longitude !== null ? (float) $branch->longitude : null,
                'is_default' => $sellerAddresses->isEmpty(),
                'type' => 'branch',
            ]);
        }

        $defaultStoreAddress = $sellerAddresses->firstWhere('is_default', true) ?? $sellerAddresses->first();

        return Inertia::render(
            'Seller/Orders/Index',
            [
                'orderShops' => $orderShops,
                'sellerAddresses' => $sellerAddresses->values(),
                'defaultStoreAddress' => $defaultStoreAddress,
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
