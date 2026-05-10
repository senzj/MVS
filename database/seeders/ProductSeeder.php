<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            ['name' => 'Fresh Chicken Breast', 'category' => 'meat', 'price' => 195.00],
            ['name' => 'Ground Pork', 'category' => 'meat', 'price' => 220.00],
            ['name' => 'Tilapia', 'category' => 'seafood', 'price' => 175.00],
            ['name' => 'Milk 1L', 'category' => 'dairy', 'price' => 399.00],
            ['name' => 'Cheddar Cheese', 'category' => 'dairy', 'price' => 120.00],
            ['name' => 'Farm Eggs 12s', 'category' => 'eggs', 'price' => 189.00],
            ['name' => 'Tomatoes', 'category' => 'vegetables', 'price' => 165.00],
            ['name' => 'Onions', 'category' => 'vegetables', 'price' => 172.00],
            ['name' => 'Potatoes', 'category' => 'vegetables', 'price' => 180.00],
            ['name' => 'Bananas', 'category' => 'fruits', 'price' => 160.00],
            ['name' => 'Apples', 'category' => 'fruits', 'price' => 145.00],
            ['name' => 'Orange Juice', 'category' => 'beverages', 'price' => 110.00],
            ['name' => 'Bottled Water 500ml', 'category' => 'beverages', 'price' => 20.00],
            ['name' => 'Potato Chips', 'category' => 'snacks', 'price' => 45.00],
            ['name' => 'Chocolate Cookies', 'category' => 'snacks', 'price' => 120.00],
            ['name' => 'Soy Sauce', 'category' => 'condiments', 'price' => 93.00],
            ['name' => 'Vinegar', 'category' => 'condiments', 'price' => 85.00],
            ['name' => 'White Rice 5kg', 'category' => 'grains', 'price' => 980.00],
            ['name' => 'Bread Loaf', 'category' => 'bakery', 'price' => 98.00],
            ['name' => 'MGas', 'category' => 'gas', 'price' => 1450.00],
            ['name' => 'Cabbage', 'category' => 'vegetables', 'price' => 150.00],
            ['name' => 'Carrots', 'category' => 'vegetables', 'price' => 155.00],
            ['name' => 'Cucumbers', 'category' => 'vegetables', 'price' => 145.00],
            ['name' => 'Strawberries', 'category' => 'fruits', 'price' => 150.00],
            ['name' => 'Grapes', 'category' => 'fruits', 'price' => 240.00],
            ['name' => 'Pineapple', 'category' => 'fruits', 'price' => 130.00],
        ];

        foreach ($products as $product) {
            $stocks = fake()->numberBetween(20, 120);

            Product::create([
                'name' => $product['name'],
                'description' => fake()->sentence(12),
                'stocks' => $stocks,
                'sold' => 0,
                'is_in_stock' => $stocks > 0,
                'category' => $product['category'],
                'price' => $product['price'],
            ]);
        }
    }
}
