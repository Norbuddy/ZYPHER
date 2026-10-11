<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Audit trail - who changed the status and when. Useful for thesis documentation/screenshots.
        Schema::create('order_shop_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_shop_id')->constrained('order_shops')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('order_shop_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_shop_status_histories');
    }
};
