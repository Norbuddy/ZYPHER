<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('password_reset_otps')) {
            return;
        }

        Schema::table('password_reset_otps', function (Blueprint $table) {
            $table->unsignedTinyInteger('attempts')->default(0)->after('used');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('password_reset_otps')) {
            return;
        }

        Schema::table('password_reset_otps', function (Blueprint $table) {
            $table->dropColumn('attempts');
        });
    }
};
