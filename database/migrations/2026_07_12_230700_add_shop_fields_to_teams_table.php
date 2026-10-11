<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('slug')->unique()->nullable()->after('name');
            $table->text('description')->nullable()->after('slug');
            $table->string('logo_path')->nullable()->after('description');
            $table->string('status')->default('pending')->after('logo_path'); // pending, approved, suspended, rejected
            $table->decimal('rating_avg', 3, 2)->default(0)->after('status');
            $table->unsignedInteger('rating_count')->default(0)->after('rating_avg');
            $table->decimal('commission_rate', 5, 2)->default(5.00)->after('rating_count');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['slug', 'description', 'logo_path', 'status', 'rating_avg', 'rating_count', 'commission_rate']);
        });
    }
};
