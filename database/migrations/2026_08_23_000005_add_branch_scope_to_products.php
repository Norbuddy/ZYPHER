<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'branch_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('team_id')->constrained('branches')->nullOnDelete();
                $table->index(['team_id', 'branch_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'branch_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
                $table->dropIndex(['team_id', 'branch_id']);
                $table->dropColumn('branch_id');
            });
        }
    }
};