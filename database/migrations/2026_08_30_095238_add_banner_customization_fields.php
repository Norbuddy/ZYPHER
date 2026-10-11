<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // All banner customization columns already exist in the database.
        // Nothing needs to be added here.
    }

    public function down(): void
    {
        // Intentionally left empty because these columns
        // belong to the existing banners table structure.
    }
};