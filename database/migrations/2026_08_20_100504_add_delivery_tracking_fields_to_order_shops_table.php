<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_shops', function (Blueprint $table) {
            $table->text('store_address')->nullable()->after('shipping_method');
            $table->decimal('store_latitude', 10, 7)->nullable()->after('store_address');
            $table->decimal('store_longitude', 10, 7)->nullable()->after('store_latitude');

            $table->decimal('delivery_distance_km', 10, 2)->nullable()->after('store_longitude');
            $table->unsignedInteger('estimated_delivery_minutes')->nullable()->after('delivery_distance_km');
        });
    }

    public function down(): void
    {
        Schema::table('order_shops', function (Blueprint $table) {
            $table->dropColumn([
                'store_address',
                'store_latitude',
                'store_longitude',
                'delivery_distance_km',
                'estimated_delivery_minutes',
            ]);
        });
    }
};