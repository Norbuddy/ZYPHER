<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type'); // 'platform' | 'shop'
            $table->foreignId('team_id')->nullable()->constrained('teams')->cascadeOnDelete();
            $table->string('code')->nullable()->unique();
            $table->string('discount_type'); // 'percentage' | 'fixed_amount'
            $table->decimal('discount_value', 12, 2);
            $table->decimal('min_spend', 12, 2)->default(0);
            $table->decimal('max_discount_amount', 12, 2)->nullable();
            $table->unsignedInteger('usage_limit_total')->nullable();
            $table->unsignedInteger('usage_limit_per_user')->default(1);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('status')->default('active'); // active, expired, disabled
            $table->timestamps();

            $table->index(['owner_type', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
