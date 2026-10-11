<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_shop_id')->constrained('order_shops')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            $table->string('product_name');
            $table->string('variant_label')->nullable();
            $table->string('sku');
            $table->decimal('price_at_purchase', 12, 2);
            $table->unsignedInteger('quantity');

            $table->foreignId('applied_voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->decimal('item_discount', 12, 2)->default(0);

            $table->timestamps();

            $table->index('order_shop_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
