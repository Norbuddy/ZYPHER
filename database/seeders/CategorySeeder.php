<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $allowed = [
            'T-Shirts',
            'Long Sleeves',
            'Pants',
            'Jacket',
            'Shoes',
        ];

        foreach ($allowed as $index => $name) {
            Category::updateOrCreate(
                ['name' => $name],
                [
                    'slug' => Str::slug($name),
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }

        // Disable old categories
        Category::whereNotIn('name', $allowed)
            ->update(['is_active' => false]);
    }
}