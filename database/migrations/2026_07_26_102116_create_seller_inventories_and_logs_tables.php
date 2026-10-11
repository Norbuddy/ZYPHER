<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('seller_inventories')) {
            Schema::create('seller_inventories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_id')->constrained('teams')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->unsignedInteger('quantity')->default(0);
                $table->string('source_type'); // 'supplier' | 'manual'
                $table->unsignedBigInteger('source_id')->nullable();
                $table->timestamps();

                $table->unique(['seller_id', 'product_id']);
            });
        }

        if (!Schema::hasTable('inventory_logs')) {
            Schema::create('inventory_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_id')->constrained('teams')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('type'); // 'in' | 'out' | 'adjust'
                $table->integer('quantity');
                $table->string('source_type'); // 'supplier' | 'manual'
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('note')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
        Schema::dropIfExists('seller_inventories');
    }
};