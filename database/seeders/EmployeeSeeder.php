<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = [
            'Daniel Flores',
            'Catherine Lim',
            'Mark Bautista',
            'Jessa Rivera',
            'Paolo Gomez',
            'Rina Dela Cruz',
            'Ethan Castillo',
            'Nina Mendoza',
        ];

        foreach ($employees as $name) {
            Employee::create([
                'name' => $name,
                'status' => fake()->boolean(80) ? 'active' : 'inactive',
                'contact_number' => '09' . fake()->numerify('#########'),
                'is_archived' => false,
            ]);
        }
    }
}
