<?php

namespace App\Jobs;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Scheduled job (register in routes/console.php or App\Console\Kernel to run every few minutes).
 * Releases stock reservations for orders that were never paid within the reservation window.
 */
class ReleaseExpiredStockReservation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // How long a checkout has to complete payment before its stock lock is released.
    protected int $reservationMinutes = 30;

    public function handle(OrderService $orderService): void
    {
        $expired = Order::where('payment_status', PaymentStatus::PendingPayment)
            ->where('created_at', '<=', now()->subMinutes($this->reservationMinutes))
            ->get();

        foreach ($expired as $order) {
            $orderService->releaseUnpaidOrder($order);
        }
    }
}
