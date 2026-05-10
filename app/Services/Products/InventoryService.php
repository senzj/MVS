<?php

namespace App\Services\Products;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

// Centralized inventory management for all stock changes:
class InventoryService
{
    // Remove stock when an order is placed or quantity increased
    public function deduct(int $productId, int $qty): void
    {
        if ($qty <= 0) {
            return;
        }

        $product = Product::lockForUpdate()->find($productId);

        if (! $product) {
            throw ValidationException::withMessages([
                'product' => 'Product not found.',
            ]);
        }

        if ($product->stocks < $qty) {
            throw ValidationException::withMessages([
                'product' => "Insufficient stock for {$product->name}",
            ]);
        }

        $product->stocks -= $qty;
        $product->sold += $qty;
        $product->is_in_stock = $product->stocks > 0;
        $product->save();
    }

    // Restore stock when an order is cancelled or quantity decreased
    public function restore(int $productId, int $qty): void
    {
        if ($qty <= 0) {
            return;
        }

        $product = Product::lockForUpdate()->find($productId);

        if (! $product) {
            return;
        }

        $product->stocks += $qty;
        $product->sold = max(0, $product->sold - $qty);
        $product->is_in_stock = true;
        $product->save();
    }

    // Synchronize inventory when an order item's quantity is updated
    public function sync(int $productId, int $oldQty, int $newQty): void
    {
        $difference = $newQty - $oldQty;

        // Increased quantity
        if ($difference > 0) {
            $this->deduct($productId, $difference);
        }

        // Decreased quantity
        if ($difference < 0) {
            $this->restore($productId, abs($difference));
        }
    }
}
