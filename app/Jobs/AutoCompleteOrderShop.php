<?php

namespace App\Jobs;

use App\Enums\OrderShopStatus;
use App\Models\OrderShop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Scheduled job - auto-completes shipped orders after N days if the customer
 * never manually confirmed receipt. Mirrors how Shopee/Lazada behave.
 */
class AutoCompleteOrderShop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $autoCompleteDays = 7;

    public function handle(): void
    {
        $shipped = OrderShop::where('status', OrderShopStatus::Shipped)
            ->where('shipped_at', '<=', now()->subDays($this->autoCompleteDays))
            ->get();

        foreach ($shipped as $orderShop) {
            $orderShop->transitionTo(OrderShopStatus::Completed, note: 'Auto-completed after delivery window.');
        }
    }
}
