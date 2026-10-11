<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('supplier_orders', 'payment_status')) {
                $table->string('payment_status')->default('pending')->after('status');
            }
            if (! Schema::hasColumn('supplier_orders', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('payment_status');
            }
            if (! Schema::hasColumn('supplier_orders', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('payment_reference');
            }
            if (! Schema::hasColumn('supplier_orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_method');
            }
        });
    }

    public function down(): void
    {
        Schema::table('supplier_orders', function (Blueprint $table) {
            $cols = array_filter(['payment_status', 'payment_reference', 'payment_method', 'paid_at'], fn ($c) => Schema::hasColumn('supplier_orders', $c));
            if ($cols) {
                $table->dropColumn($cols);
            }
        });
    }
};
