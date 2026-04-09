<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            Customer::create([
                'name' => fake()->name(),
                'unit' => fake()->boolean(70) ? 'Unit ' . fake()->numberBetween(1, 40) : null,
                'address' => fake()->address(),
                'contact_number' => '09' . fake()->numerify('#########'),
            ]);
        }
    }
}
