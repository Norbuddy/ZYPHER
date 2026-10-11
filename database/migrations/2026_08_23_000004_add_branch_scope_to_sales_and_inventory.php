<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['pos_sales', 'seller_inventories', 'inventory_logs', 'product_variants'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'branch_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->nullOnDelete();
                });
            }
        }

        if (Schema::hasTable('seller_inventories')) {
            $indexes = collect(Schema::getIndexes('seller_inventories'))->pluck('name');

            Schema::table('seller_inventories', function (Blueprint $table) use ($indexes) {
                if (! $indexes->contains('seller_inventories_seller_id_index')) {
                    $table->index('seller_id');
                }
                if (! $indexes->contains('seller_inventories_product_id_index')) {
                    $table->index('product_id');
                }
                if ($indexes->contains('seller_inventories_seller_id_product_id_unique')) {
                    $table->dropUnique('seller_inventories_seller_id_product_id_unique');
                }
                if (! $indexes->contains('seller_inventories_seller_id_product_id_branch_id_unique')) {
                    $table->unique(['seller_id', 'product_id', 'branch_id']);
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['pos_sales', 'seller_inventories', 'inventory_logs', 'product_variants'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'branch_id')) {
                if ($tableName === 'seller_inventories') {
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->dropUnique('seller_inventories_seller_id_product_id_branch_id_unique');
                        $table->unique(['seller_id', 'product_id']);
                    });
                }

                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['branch_id']);
                    $table->dropColumn('branch_id');
                });
            }
        }
    }
};