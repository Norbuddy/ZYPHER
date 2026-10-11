<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Support conversations have no shop - they go to the admin support inbox instead.
            $table->boolean('is_support')->default(false)->after('order_shop_id');
        });

        // team_id needs to become nullable for support threads (MySQL requires dropping
        // and re-adding the FK to change nullability on an existing foreign key column).
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
        });
        Schema::table('conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->nullable()->change();
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
        });

        // The old unique(['user_id','team_id']) constraint breaks once team_id can be null
        // (multiple support threads would collide on NULL under some engines). Replace it.
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('is_support');
            $table->unsignedBigInteger('team_id')->nullable(false)->change();
            $table->unique(['user_id', 'team_id']);
        });
    }
};
