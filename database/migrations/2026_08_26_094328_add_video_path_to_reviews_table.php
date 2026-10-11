<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('reviews', 'video_path')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->string('video_path')->nullable()->after('image_paths');
            });
        }
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('video_path');
        });
    }
};