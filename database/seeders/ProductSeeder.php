<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategories;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryIds = ProductCategories::query()
            ->pluck('id', 'name')
            ->toArray();

        $products = [
            ['name' => 'Fresh Chicken Breast', 'category' => 'Meat', 'price' => 195.00],
            ['name' => 'Ground Pork', 'category' => 'Meat', 'price' => 220.00],
            ['name' => 'Tilapia', 'category' => 'Seafood', 'price' => 175.00],
            ['name' => 'Milk 1L', 'category' => 'Dairy', 'price' => 399.00],
            ['name' => 'Cheddar Cheese', 'category' => 'Dairy', 'price' => 120.00],
            ['name' => 'Farm Eggs 12s', 'category' => 'Eggs', 'price' => 189.00],
            ['name' => 'Tomatoes', 'category' => 'Vegetables', 'price' => 165.00],
            ['name' => 'Onions', 'category' => 'Vegetables', 'price' => 172.00],
            ['name' => 'Potatoes', 'category' => 'Vegetables', 'price' => 180.00],
            ['name' => 'Bananas', 'category' => 'Fruits', 'price' => 160.00],
            ['name' => 'Apples', 'category' => 'Fruits', 'price' => 145.00],
            ['name' => 'Orange Juice', 'category' => 'Beverages', 'price' => 110.00],
            ['name' => 'Bottled Water 500ml', 'category' => 'Beverages', 'price' => 20.00],
            ['name' => 'Potato Chips', 'category' => 'Snacks', 'price' => 45.00],
            ['name' => 'Chocolate Cookies', 'category' => 'Snacks', 'price' => 120.00],
            ['name' => 'Soy Sauce', 'category' => 'Condiments', 'price' => 93.00],
            ['name' => 'Vinegar', 'category' => 'Condiments', 'price' => 85.00],
            ['name' => 'White Rice 5kg', 'category' => 'Grains', 'price' => 980.00],
            ['name' => 'Bread Loaf', 'category' => 'Bakery', 'price' => 98.00],
            ['name' => 'MGas', 'category' => 'Gas', 'price' => 1450.00],
            ['name' => 'Cabbage', 'category' => 'Vegetables', 'price' => 150.00],
            ['name' => 'Carrots', 'category' => 'Vegetables', 'price' => 155.00],
            ['name' => 'Cucumbers', 'category' => 'Vegetables', 'price' => 145.00],
            ['name' => 'Strawberries', 'category' => 'Fruits', 'price' => 150.00],
            ['name' => 'Grapes', 'category' => 'Fruits', 'price' => 240.00],
            ['name' => 'Pineapple', 'category' => 'Fruits', 'price' => 130.00],
        ];

        $productsCreated = 0;

        foreach ($products as $product) {
            $stocks = fake()->numberBetween(20, 120);
            $categoryId = $categoryIds[$product['category']] ?? null;

            Product::create([
                'name' => $product['name'],
                'description' => fake()->sentence(12),
                'stocks' => $stocks,
                'sold' => 0,
                'is_in_stock' => $stocks > 0,
                'category_id' => $categoryId,
                'price' => $product['price'],
            ]);

            $productsCreated++;
        }

        $this->command->line("{$productsCreated} products added");
    }
}
