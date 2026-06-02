<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Truncate tables in the correct order to avoid foreign key constraint issues
        // Schema::disableForeignKeyConstraints();

        // OrderItem::truncate();
        // Order::truncate();
        // Log::truncate();
        // Product::truncate();
        // Employee::truncate();
        // Customer::truncate();
        // User::truncate();

        // Re-enable foreign key constraints after truncation
        // Schema::enableForeignKeyConstraints();

        $this->call([
            UserSeeder::class,
            CustomerSeeder::class,
            EmployeeSeeder::class,
            ProductCategorySeeder::class,
            ProductSeeder::class,
            DiscountPresetSeeder::class,
            // OrderSeeder::class,
        ]);
    }
}
