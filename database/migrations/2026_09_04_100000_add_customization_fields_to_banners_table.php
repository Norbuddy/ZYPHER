<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columns and their definitions. Kept as a callable map so we can
     * add each one individually and skip any that already exist —
     * safe to run whether the DB already has these columns or not.
     */
    protected function columns(): array
    {
        return [
            'button_link' => fn (Blueprint $table) => $table->string('button_link')->nullable()->after('button_text'),
            'title_font' => fn (Blueprint $table) => $table->string('title_font')->default('Inter')->after('button_link'),
            'subtitle_font' => fn (Blueprint $table) => $table->string('subtitle_font')->default('Inter')->after('title_font'),
            'button_font' => fn (Blueprint $table) => $table->string('button_font')->default('Inter')->after('subtitle_font'),
            'title_color' => fn (Blueprint $table) => $table->string('title_color')->default('#101114')->after('button_font'),
            'subtitle_color' => fn (Blueprint $table) => $table->string('subtitle_color')->default('#666762')->after('title_color'),
            'button_text_color' => fn (Blueprint $table) => $table->string('button_text_color')->default('#FFFFFF')->after('subtitle_color'),
            'button_background' => fn (Blueprint $table) => $table->string('button_background')->default('#101114')->after('button_text_color'),
            'button_gradient_color' => fn (Blueprint $table) => $table->string('button_gradient_color')->default('#33D6C8')->after('button_background'),
            'button_style' => fn (Blueprint $table) => $table->string('button_style')->default('fill')->after('button_gradient_color'),
        ];
    }

    public function up(): void
    {
        foreach ($this->columns() as $name => $addColumn) {
            if (Schema::hasColumn('banners', $name)) {
                continue;
            }

            Schema::table('banners', function (Blueprint $table) use ($addColumn) {
                $addColumn($table);
            });
        }
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            foreach (array_keys($this->columns()) as $name) {
                if (Schema::hasColumn('banners', $name)) {
                    $table->dropColumn($name);
                }
            }
        });
    }
};