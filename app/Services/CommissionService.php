<?php

namespace App\Services;

use App\Models\Team;

class CommissionService
{
    /**
     * Determine the commission rate to apply for this shop right now.
     * Snapshot this value onto order_shops at time of sale - never look it up
     * live for historical orders, since rates can change over time.
     */
    public function rateFor(Team $team): float
    {
        // Extend here later for per-category or promotional commission tiers.
        return (float) $team->commission_rate;
    }

    public function calculate(float $subtotal, float $rate): array
    {
        $platformFee = round($subtotal * ($rate / 100), 2);
        $sellerPayout = round($subtotal - $platformFee, 2);

        return [
            'platform_fee' => $platformFee,
            'seller_payout' => $sellerPayout,
        ];
    }
}
