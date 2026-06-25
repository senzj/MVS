<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\DiscountPreset;
use App\Models\Product;
use App\Models\User;
use App\Services\Products\InventoryService;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $inventoryService = new InventoryService();
        $userIds = User::query()->pluck('id');
        $customerIds = Customer::query()->pluck('id');
        $employeeIds = Employee::query()->where('status', 'active')->where('is_archived', false)->pluck('id');

        if ($userIds->isEmpty() || $customerIds->isEmpty()) {
            $this->command->warn('Skipping OrderSeeder: no users or customers found. Run UserSeeder and CustomerSeeder first.');
            return;
        }

        $statuses = ['pending', 'in_transit', 'delivered', 'completed', 'cancelled'];
        $discountPresets = DiscountPreset::query()->where('is_active', true)->get();

        $random = fake()->numberBetween(8, 30);
        $ordersCreated = 0;

        // Create random orders with items
        for ($i = 1; $i <= $random; $i++) {
            $orderType = fake()->randomElement(['deliver', 'walk_in']);
            $storeOpenHour = (int) config('storeconfig.store_open_hour', 7);
            $storeCloseHour = (int) config('storeconfig.store_close_hour', 20);
            $orderTimestamp = fake()->dateTimeBetween(
                now()->copy()->setTime($storeOpenHour, 0, 0),
                now()->copy()->setTime(max($storeOpenHour, $storeCloseHour), 59, 59)
            );

            // Get all configured payment types
            $otherPaymentTypes = config('storeconfig.other_payment_types', []);
            $allPaymentTypes = array_merge(['cash', 'gcash'], $otherPaymentTypes);
            $paymentType = fake()->randomElement($allPaymentTypes);

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
                'payment_type' => $paymentType,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'receipt_number' => $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT),
            ]);

            $products = Product::query()
                ->where('stocks', '>', 0)
                ->inRandomOrder()
                ->take(fake()->numberBetween(1, 4))
                ->get();

            if ($products->isEmpty()) {
                $order->delete();
                $this->command->warn("Order #{$i} skipped: no products with stock.");
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

                // Use InventoryService to deduct and log the movement
                $inventoryService->deduct(
                    $product->id,
                    $quantity,
                    'Order Created',
                    $order,
                    'Order seeding'
                );

                $orderTotal += $lineTotal;
                $hasItems = true;
            }

            $discountPreset = null;
            $discountAmount = 0;

            if ($discountPresets->isNotEmpty() && fake()->boolean(30)) {
                $discountPreset = $discountPresets->random();

                if ($discountPreset->type === 'percentage') {
                    $discountAmount = min($orderTotal, $orderTotal * ((float) $discountPreset->value / 100));
                } else {
                    $discountAmount = min($orderTotal, (float) $discountPreset->value);
                }
            }

            $finalTotal = max(0, $orderTotal - $discountAmount);

            $amountReceived = null;
            $changeAmount = null;
            if ($paymentType === 'cash' && $paymentStatus === 'paid') {
                $amountReceived = round($finalTotal + fake()->randomFloat(2, 0, 300), 2);
                $changeAmount = round(max(0, $amountReceived - $finalTotal), 2);
            }

            if (! $hasItems) {
                $order->delete();
                continue;
            }

            $order->update([
                'order_total' => $finalTotal,
                'discount_preset_id' => $discountPreset?->id,
                'discount_type' => $discountPreset?->type ?? 'none',
                'discount_value' => $discountPreset?->value ?? 0,
                'amount_received' => $amountReceived,
                'change_amount' => $changeAmount,
                'payment_status' => $paymentStatus,
            ]);

            $ordersCreated++;
        }

        $this->command->line("{$ordersCreated}/{$random} orders created.");
    }
}
