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
        $previousStatus = $orderShop->status;
        $orderShop->transitionTo(OrderShopStatus::Refunded, $request->user()->id, 'Refunded by admin after dispute resolution.');

        // Restore seller product variant inventory if order was active
        if ($previousStatus !== OrderShopStatus::Refunded && $previousStatus !== OrderShopStatus::Cancelled) {
            foreach ($orderShop->items()->with('variant')->get() as $item) {
                if ($item->variant) {
                    $item->variant->increment('stock', $item->quantity);
                }
            }
        }

        return back()->with('success', 'Order shop marked as refunded and inventory restored to seller.');
    }

    /** Stream download of all orders as CSV report. */
    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = 'orders_report_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Order Number', 'Shop Name', 'Customer Name', 'Subtotal', 'Shipping Fee', 'Status', 'Date']);

            OrderShop::with('team:id,name', 'order.user:id,name')
                ->latest()
                ->chunk(200, function ($orders) use ($handle) {
                    foreach ($orders as $os) {
                        fputcsv($handle, [
                            $os->order?->order_number ?? 'N/A',
                            $os->team?->name ?? 'N/A',
                            $os->order?->user?->name ?? 'N/A',
                            $os->subtotal,
                            $os->shipping_fee,
                            $os->status?->value ?? (string) $os->status,
                            $os->created_at?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
