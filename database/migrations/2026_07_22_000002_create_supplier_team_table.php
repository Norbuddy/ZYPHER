<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('supplier_team')) {
            Schema::create('supplier_team', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
                $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->unique(['supplier_id', 'team_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_team');
    }
};
