<?php

namespace App\Services;

use App\Enums\VoucherOwnerType;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;

class VoucherService
{
    /**
     * Validate that a voucher can be applied by this user against this subtotal.
     * Returns an error message string, or null if valid.
     */
    public function validate(Voucher $voucher, User $user, float $subtotal): ?string
    {
        if (!$voucher->isValidNow()) {
            return 'This voucher is no longer valid.';
        }

        if ($subtotal < (float) $voucher->min_spend) {
            return "Minimum spend of ₱{$voucher->min_spend} required for this voucher.";
        }

        $userUsageCount = $voucher->usages()->where('user_id', $user->id)->count();
        if ($userUsageCount >= $voucher->usage_limit_per_user) {
            return 'You have already used this voucher the maximum number of times.';
        }

        return null;
    }

    /**
     * Record that a voucher was used, decrementing implicit availability via the usages table.
     */
    public function recordUsage(Voucher $voucher, User $user, float $discountAmount, ?int $orderId = null, ?int $orderItemId = null): void
    {
        DB::transaction(function () use ($voucher, $user, $discountAmount, $orderId, $orderItemId) {
            $voucher->usages()->create([
                'user_id' => $user->id,
                'order_id' => $orderId,
                'order_item_id' => $orderItemId,
                'discount_amount' => $discountAmount,
                'used_at' => now(),
            ]);
        });
    }

    public function isShopVoucher(Voucher $voucher): bool
    {
        return $voucher->owner_type === VoucherOwnerType::Shop;
    }

    public function isPlatformVoucher(Voucher $voucher): bool
    {
        return $voucher->owner_type === VoucherOwnerType::Platform;
    }
}
