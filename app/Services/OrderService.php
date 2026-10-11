<?php

namespace App\Services;

use App\Enums\OrderShopStatus;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShop;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected InventoryService $inventory,
        protected CommissionService $commission,
        protected VoucherService $vouchers,
    ) {}

    /**
     * The core checkout operation:
     * 1. Lock stock for every item in the cart
     * 2. Create the parent Order (payment_status = pending_payment)
     * 3. Split cart items by shop into Order Shops
     * 4. Snapshot commission rate per shop
     * 5. Create Order Items with price/label snapshots
     *
     * All wrapped in a DB transaction - if any step fails, nothing is committed
     * and no stock reservation lingers.
     */
    public function placeOrder(User $user, Cart $cart, ?int $addressId, ?Voucher $platformVoucher = null): Order
    {
        return DB::transaction(function () use ($user, $cart, $addressId, $platformVoucher) {
            $itemsByShop = $cart->itemsGroupedByShop();

            if ($itemsByShop->isEmpty()) {
                throw new \RuntimeException('Cart is empty.');
            }

            // Step 1: reserve stock for every line item up front.
            foreach ($cart->items as $cartItem) {
                $this->inventory->reserve($cartItem->product_variant_id, $cartItem->quantity);
            }

            $orderTotal = 0;
            $platformDiscount = 0;

            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $user->id,
                'address_id' => $addressId,
                'total_amount' => 0, // patched at the end
                'platform_voucher_id' => $platformVoucher?->id,
                'payment_status' => PaymentStatus::PendingPayment,
            ]);

            foreach ($itemsByShop as $teamId => $items) {
                $team = $items->first()->variant->product->team;
                $shopSubtotal = $items->sum(fn ($item) => $item->lineTotal());

                $rate = $this->commission->rateFor($team);
                $calc = $this->commission->calculate($shopSubtotal, $rate);

                $orderShop = OrderShop::create([
                    'order_id' => $order->id,
                    'team_id' => $teamId,
                    'status' => OrderShopStatus::PendingPayment,
                    'subtotal' => $shopSubtotal,
                    'commission_rate' => $rate,
                    'platform_fee' => $calc['platform_fee'],
                    'seller_payout' => $calc['seller_payout'],
                ]);

                foreach ($items as $cartItem) {
                    $variant = $cartItem->variant;

                    OrderItem::create([
                        'order_shop_id' => $orderShop->id,
                        'product_variant_id' => $variant->id,
                        'product_name' => $variant->product->name,
                        'variant_label' => $variant->label,
                        'sku' => $variant->sku,
                        'price_at_purchase' => $variant->price,
                        'quantity' => $cartItem->quantity,
                        'applied_voucher_id' => $cartItem->applied_voucher_id,
                    ]);
                }

                $orderTotal += $shopSubtotal;
            }

            if ($platformVoucher) {
                $platformDiscount = $platformVoucher->calculateDiscount($orderTotal);
                $this->vouchers->recordUsage($platformVoucher, $user, $platformDiscount, $order->id);
            }

            $order->update([
                'total_amount' => max(0, $orderTotal - $platformDiscount),
                'platform_voucher_discount' => $platformDiscount,
            ]);

            // Clear the cart now that the order has been created.
            $cart->items()->delete();

            return $order->fresh('orderShops.items');
        });
    }

    /**
     * Payment confirmed (called by whatever future payment webhook/mark-as-paid action).
     * Commits stock deduction and advances every order_shop to processing.
     */
    public function confirmPayment(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->orderShops as $orderShop) {
                foreach ($orderShop->items as $item) {
                    if ($item->product_variant_id) {
                        $this->inventory->commit($item->product_variant_id, $item->quantity);
                    }
                }
                $orderShop->transitionTo(\App\Enums\OrderShopStatus::Processing);
            }

            $order->update([
                'payment_status' => PaymentStatus::Paid,
                'paid_at' => now(),
            ]);
        });
    }

    /**
     * Payment failed or the reservation window expired: release all locked stock.
     */
    public function releaseUnpaidOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->orderShops as $orderShop) {
                foreach ($orderShop->items as $item) {
                    if ($item->product_variant_id) {
                        $this->inventory->release($item->product_variant_id, $item->quantity);
                    }
                }
                $orderShop->transitionTo(\App\Enums\OrderShopStatus::Cancelled, note: 'Payment not completed in time.');
            }

            $order->update(['payment_status' => PaymentStatus::Failed]);
        });
    }
}
