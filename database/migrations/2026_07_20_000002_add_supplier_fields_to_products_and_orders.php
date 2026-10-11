<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'supplier_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('supplier_id')->nullable()->after('category_id')->constrained('suppliers')->nullOnDelete();
            });
        }

        if (Schema::hasTable('supplier_orders') && !Schema::hasColumn('supplier_orders', 'items')) {
            Schema::table('supplier_orders', function (Blueprint $table) {
                $table->json('items')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('supplier_orders') && Schema::hasColumn('supplier_orders', 'items')) {
            Schema::table('supplier_orders', function (Blueprint $table) {
                $table->dropColumn('items');
            });
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'supplier_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('supplier_id');
            });
        }
    }
};
