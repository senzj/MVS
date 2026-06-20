<?php

namespace Database\Seeders;

use App\Models\ProductCategories;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Meat', 'description' => 'Fresh and frozen meat products.'],
            ['name' => 'Seafood', 'description' => 'Fish and seafood selections.'],
            ['name' => 'Dairy', 'description' => 'Milk, cheese, and dairy essentials.'],
            ['name' => 'Eggs', 'description' => 'Fresh egg products.'],
            ['name' => 'Vegetables', 'description' => 'Fresh vegetable produce.'],
            ['name' => 'Fruits', 'description' => 'Seasonal and fresh fruits.'],
            ['name' => 'Beverages', 'description' => 'Drinks and refreshments.'],
            ['name' => 'Snacks', 'description' => 'Ready-to-eat snack items.'],
            ['name' => 'Condiments', 'description' => 'Sauces, spices, and condiments.'],
            ['name' => 'Grains', 'description' => 'Rice and grain staples.'],
            ['name' => 'Bakery', 'description' => 'Bread and bakery products.'],
            ['name' => 'Gas', 'description' => 'Cooking gas and related products.'],
        ];

        $successfulCategories = 0;

        foreach ($categories as $category) {
            ProductCategories::query()->updateOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description']]
            );

            $successfulCategories++;
        }

        $this->command->line("{$successfulCategories} product categories added or updated");
    }
}
