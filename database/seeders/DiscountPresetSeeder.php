<?php

namespace Database\Seeders;

use App\Models\DiscountPreset;
use Illuminate\Database\Seeder;

class DiscountPresetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $presets = [
            [
                'name' => 'Senior Citizen',
                'type' => 'percentage',
                'value' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'PWD',
                'type' => 'percentage',
                'value' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Government Employee',
                'type' => 'percentage',
                'value' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Employee Discount',
                'type' => 'percentage',
                'value' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Special Promo',
                'type' => 'fixed',
                'value' => 50,
                'is_active' => true,
            ],
        ];

        foreach ($presets as $preset) {
            DiscountPreset::updateOrCreate(
                ['name' => $preset['name']],
                $preset
            );
        }
    }
}
