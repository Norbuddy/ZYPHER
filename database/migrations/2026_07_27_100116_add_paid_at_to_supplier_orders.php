<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supplier_orders') && !Schema::hasColumn('supplier_orders', 'paid_at')) {
            Schema::table('supplier_orders', function (Blueprint $table) {
                $table->timestamp('paid_at')->nullable()->after('receiving_note');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('supplier_orders') && Schema::hasColumn('supplier_orders', 'paid_at')) {
            Schema::table('supplier_orders', function (Blueprint $table) {
                $table->dropColumn('paid_at');
            });
        }
    }
};