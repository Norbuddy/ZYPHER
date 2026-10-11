<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('support_token', 20)
                ->nullable()
                ->unique()
                ->after('is_support');

            $table->string('support_subject')
                ->nullable()
                ->after('support_token');

            $table->string('support_category', 50)
                ->nullable()
                ->after('support_subject');

            $table->string('support_status', 30)
                ->default('waiting_support')
                ->after('support_category');

            $table->timestamp('closed_at')
                ->nullable()
                ->after('support_status');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique(['support_token']);
            $table->dropColumn([
                'support_token',
                'support_subject',
                'support_category',
                'support_status',
                'closed_at',
            ]);
        });
    }
};