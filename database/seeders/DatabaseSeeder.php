<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Log;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        OrderItem::truncate();
        Order::truncate();
        Log::truncate();
        Product::truncate();
        Employee::truncate();
        Customer::truncate();
        User::truncate();

        Schema::enableForeignKeyConstraints();

        $this->call([
            UserSeeder::class,
            CustomerSeeder::class,
            EmployeeSeeder::class,
            ProductSeeder::class,
            OrderSeeder::class,
        ]);
    }
}
