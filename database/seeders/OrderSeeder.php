<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIds = User::query()->pluck('id');
        $customerIds = Customer::query()->pluck('id');
        $employeeIds = Employee::query()->where('status', 'active')->where('is_archived', false)->pluck('id');

        if ($userIds->isEmpty() || $customerIds->isEmpty()) {
            return;
        }

        $statuses = ['pending', 'in_transit', 'delivered', 'completed', 'cancelled'];

        $random = fake()->numberBetween(8, 30);
        $successfulOrders = 0;

        // Create random orders with items
        for ($i = 1; $i <= $random; $i++) {
            $orderType = fake()->randomElement(['deliver']);
            $status = fake()->randomElement($statuses);
            $paymentStatus = match ($status) {
                'delivered', 'completed' => 'paid',
                'cancelled' => 'refunded',
                default => fake()->boolean(60) ? 'paid' : 'unpaid',
            };

            $datePart = now()->format('ymd');
            $prefix = "OR{$datePart}";

            $lastReceiptNumber = Order::query()
                ->where('receipt_number', 'like', "{$prefix}%")
                ->orderByDesc('receipt_number')
                ->value('receipt_number');

            if ($lastReceiptNumber) {
                $numericPart = substr($lastReceiptNumber, strlen($prefix));
                $lastNumber = is_numeric($numericPart) ? (int) $numericPart : 0;
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            $order = Order::create([
                'customer_id' => $customerIds->random(),
                'created_by' => $userIds->random(),
                'delivered_by' => $orderType === 'deliver' && $employeeIds->isNotEmpty() ? $employeeIds->random() : null,
                'order_total' => 0,
                'order_type' => $orderType,
                'payment_type' => fake()->randomElement(['cash', 'gcash']),
                'status' => $status,
                'payment_status' => $paymentStatus,
                'receipt_number' => $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT),
            ]);

            $products = Product::query()
                ->whereNotNull('stocks')
                ->where('stocks', '>', 0)
                ->inRandomOrder()
                ->take(fake()->numberBetween(1, 4))
                ->get();

            if ($products->isEmpty()) {
                $order->delete();
                continue;
            }

            $orderTotal = 0;
            $hasItems = false;

            foreach ($products as $product) {
                $available = is_numeric($product->stocks) ? (int) $product->stocks : 0;
                if ($available < 1) {
                    continue;
                }

                $quantity = fake()->numberBetween(1, min(5, $available));
                $lineTotal = $quantity * (float) $product->price;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'total_price' => $lineTotal,
                ]);

                $remainingStocks = max(0, (int) $product->stocks - $quantity);
                $soldCount = (int) ($product->sold ?? 0) + $quantity;

                $product->update([
                    'stocks' => $remainingStocks,
                    'sold' => $soldCount,
                    'is_in_stock' => $remainingStocks > 0,
                ]);

                $orderTotal += $lineTotal;
                $hasItems = true;
            }

            if (! $hasItems) {
                $order->delete();
                continue;
            }

            $order->update([
                'order_total' => $orderTotal,
                'payment_status' => $paymentStatus,
            ]);

            $successfulOrders++;
        }

        $this->command->line("{$successfulOrders} orders seeded");
    }
}
