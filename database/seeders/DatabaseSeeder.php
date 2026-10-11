<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
        ]);

        // User::factory(10)->withPersonalTeam()->create();

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Zypher admin account - use this to log into /admin/*
        User::firstOrCreate(
            ['email' => 'admin@zypher.test'],
            [
                'name' => 'Zypher Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'is_admin' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'supplier@example.com'],
            [
                'name' => 'Supplier Demo',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'is_supplier' => true,
            ]
        );
    }
}
