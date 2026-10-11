<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Lock stock for a checkout attempt. Uses a row lock (lockForUpdate) to
     * prevent two simultaneous checkouts from both reserving the last unit.
     *
     * @throws \RuntimeException if stock is insufficient
     */
    public function reserve(int $variantId, int $quantity): void
    {
        DB::transaction(function () use ($variantId, $quantity) {
            $variant = ProductVariant::where('id', $variantId)->lockForUpdate()->firstOrFail();

            if (!$variant->hasStockFor($quantity)) {
                throw new \RuntimeException("Insufficient stock for variant #{$variantId}.");
            }

            $variant->increment('reserved_stock', $quantity);
        });
    }

    public function reserveByProduct(int $productId, int $quantity): void
    {
        DB::transaction(function () use ($productId, $quantity) {
            $variants = ProductVariant::where('product_id', $productId)
                ->lockForUpdate()
                ->get();

            if ($variants->isEmpty()) {
                throw new \RuntimeException("Unable to locate inventory for product #{$productId}.");
            }

            $remaining = $quantity;
            foreach ($variants as $variant) {
                if ($remaining <= 0) {
                    break;
                }

                $variantStock = $variant->availableStock();
                if ($variantStock <= 0) {
                    continue;
                }

                $reserveAmount = min($variantStock, $remaining);
                $variant->increment('reserved_stock', $reserveAmount);
                $remaining -= $reserveAmount;
            }
        });
    }

    /**
     * Payment confirmed: convert the reservation into a real deduction.
     * Net effect: stock decreases, reserved_stock decreases by the same amount.
     */
    public function commit(int $variantId, int $quantity): void
    {
        DB::transaction(function () use ($variantId, $quantity) {
            $variant = ProductVariant::where('id', $variantId)->lockForUpdate()->firstOrFail();

            if ($variant->stock < $quantity) {
                throw new \RuntimeException("Insufficient total stock to commit reservation for variant #{$variantId}.");
            }

            $releaseReserved = min($quantity, $variant->reserved_stock);
            $variant->decrement('stock', $quantity);
            $variant->decrement('reserved_stock', $releaseReserved);
        });
    }

    /**
     * Payment failed/expired: release the reservation without touching real stock.
     */
    public function release(int $variantId, int $quantity): void
    {
        DB::transaction(function () use ($variantId, $quantity) {
            $variant = ProductVariant::where('id', $variantId)->lockForUpdate()->firstOrFail();

            $variant->decrement('reserved_stock', min($quantity, $variant->reserved_stock));
        });
    }
}
