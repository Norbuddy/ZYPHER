<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_shops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->restrictOnDelete();
            $table->string('status')->default('pending_payment');
            // pending_payment, processing, shipped, completed, cancelled, refund_requested, refunded

            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_fee', 12, 2)->default(0);
            $table->decimal('shop_voucher_discount', 12, 2)->default(0);
            $table->foreignId('shop_voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();

            $table->decimal('commission_rate', 5, 2); // snapshot at time of sale
            $table->decimal('platform_fee', 12, 2);
            $table->decimal('seller_payout', 12, 2);
            $table->string('payout_status')->default('unpaid'); // unpaid, processing, paid_out

            $table->string('courier_name')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('shipping_method')->nullable();

            $table->timestamp('processing_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['order_id']);
            $table->index(['team_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_shops');
    }
};
