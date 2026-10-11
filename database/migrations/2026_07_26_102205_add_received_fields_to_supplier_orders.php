<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supplier_orders') && !Schema::hasColumn('supplier_orders', 'received_at')) {
            Schema::table('supplier_orders', function (Blueprint $table) {
                $table->timestamp('received_at')->nullable()->after('delivered_at');
                $table->string('receiving_note')->nullable()->after('received_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('supplier_orders') && Schema::hasColumn('supplier_orders', 'received_at')) {
            Schema::table('supplier_orders', function (Blueprint $table) {
                $table->dropColumn(['received_at', 'receiving_note']);
            });
        }
    }
};