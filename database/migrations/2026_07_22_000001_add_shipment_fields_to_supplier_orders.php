<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supplier_orders') && !Schema::hasColumn('supplier_orders', 'tracking_number')) {
            Schema::table('supplier_orders', function (Blueprint $table) {
                $table->string('tracking_number')->nullable()->after('items');
                $table->string('shipment_status')->default('pending')->after('tracking_number');
                $table->timestamp('shipped_at')->nullable()->after('shipment_status');
                $table->timestamp('delivered_at')->nullable()->after('shipped_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('supplier_orders') && Schema::hasColumn('supplier_orders', 'tracking_number')) {
            Schema::table('supplier_orders', function (Blueprint $table) {
                $table->dropColumn(['tracking_number', 'shipment_status', 'shipped_at', 'delivered_at']);
            });
        }
    }
};
