<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\SupplierOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $supplier = $user->currentTeam ? $user->currentTeam->supplier : null;

        $orders = $supplier
            ? SupplierOrder::where('supplier_id', $supplier->id)
                ->where('status', '!=', 'rejected')
                ->latest()
                ->get()
            : collect();

        $payload = $orders->map(function (SupplierOrder $order) {
            $items = is_array($order->items) ? $order->items : [];

            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'shop_name' => $order->shop_name ?? $order->shop?->name ?? $order->team?->name ?? 'Unknown',
                'status' => $order->status,
                'shipment_status' => $order->shipment_status ?? 'pending',
                'tracking_number' => $order->tracking_number,
                'date_ordered' => $order->created_at?->format('M d, Y'),
                'items' => $items,
                'total_quantity' => collect($items)->sum('quantity'),
            ];
        })->values();

        return Inertia::render('Supplier/Shipment', [
            'orders' => $payload,
        ]);
    }

    public function updateStatus(Request $request, SupplierOrder $order)
    {
        $this->authorizeSupplier($request, $order);

        $data = $request->validate(['shipment_status' => ['required', 'in:pending,processing,shipped,delivered,cancelled,delivery_attempt_unsuccessful']]);

        if ($order->shipment_status === 'delivered' && $data['shipment_status'] !== 'delivered') {
            abort(422, 'Delivered shipments cannot be updated.');
        }

        $isNewlyShipped = $data['shipment_status'] === 'shipped' && $order->shipment_status !== 'shipped';

        \Illuminate\Support\Facades\DB::transaction(function () use ($order, $data, $isNewlyShipped) {
            $locked = SupplierOrder::where('id', $order->id)->lockForUpdate()->first();

            if ($isNewlyShipped) {
                $inventory = new \App\Services\InventoryService();
                $items = is_array($locked->items) ? $locked->items : [];

                foreach ($items as $item) {
                    $productId = $item['id'] ?? null;
                    $quantity = isset($item['quantity']) ? (int) $item['quantity'] : 0;

                    if (!$productId || $quantity <= 0) {
                        continue;
                    }

                    $variant = \App\Models\ProductVariant::where('product_id', $productId)->lockForUpdate()->first();
                    if (!$variant) {
                        abort(422, "Unable to locate inventory for supplier order item #{$productId}.");
                    }

                    $inventory->commit($variant->id, $quantity);
                }
            }

            $locked->shipment_status = $data['shipment_status'];
            if ($data['shipment_status'] === 'shipped') {
                $locked->shipped_at = Carbon::now();
            }
            if ($data['shipment_status'] === 'delivered') {
                $locked->delivered_at = Carbon::now();
            }
            $locked->save();
        });

        return back();
    }

    public function setTracking(Request $request, SupplierOrder $order)
    {
        $this->authorizeSupplier($request, $order);

        $data = $request->validate(['tracking_number' => ['required', 'string', 'max:255']]);

        $order->tracking_number = $data['tracking_number'];
        $order->save();

        return response()->json(['tracking_number' => $order->tracking_number]);
    }

    private function authorizeSupplier(Request $request, SupplierOrder $order): void
    {
        $supplier = $request->user()->currentTeam?->supplier;
        abort_unless($supplier && $order->supplier_id === $supplier->id, 403);
    }
}
