<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('teams', 'starting_cash_balance')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->decimal('starting_cash_balance', 12, 2)->default(0)->after('commission_rate');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('teams', 'starting_cash_balance')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->dropColumn('starting_cash_balance');
            });
        }
    }
};