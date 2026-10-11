<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Note: with Laravel Scout, calling $product->searchable() already queues this
 * automatically if QUEUE is configured. This job exists for cases where you want
 * to explicitly re-sync (e.g. after a bulk price/stock update across many variants).
 */
class SyncProductToSearchIndex implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected int $productId) {}

    public function handle(): void
    {
        $product = Product::find($this->productId);

        if (!$product) return;

        $product->refreshPriceRange();
        $product->searchable(); // pushes to Meilisearch via Scout
    }
}
