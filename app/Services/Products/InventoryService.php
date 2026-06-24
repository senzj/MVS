<?php

namespace App\Services\Products;

use App\Models\InventoryMovement;
use App\Models\ItemRestocks;
use App\Models\Product;
use App\Models\ProductRestock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /**
     * Perform a supplier restock: add stock, record cost, log the movement.
     *
     * @throws ValidationException
     */
    public function restockProduct(
        int     $productId,
        int     $qty,
        float   $unitCost,
        string  $unitType  = 'pcs',
        ?string $notes     = null,
        ?int    $userId    = null
    ): ProductRestock {
        if ($qty <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Restock quantity must be at least 1.',
            ]);
        }

        if ($unitCost < 0) {
            throw ValidationException::withMessages([
                'unit_cost' => 'Unit cost cannot be negative.',
            ]);
        }

        return DB::transaction(function () use ($productId, $qty, $unitCost, $unitType, $notes, $userId) {

            // Lock the product row for this transaction
            $product = Product::query()->lockForUpdate()->findOrFail($productId);

            $totalCost = round($qty * $unitCost, 2);

            // Weighted-average cost
            $currentStocks = (int) $product->stocks;
            $currentCost   = (float) ($product->cost ?? 0);

            $newAvgCost = ($currentStocks + $qty) > 0
                ? (($currentStocks * $currentCost) + ($qty * $unitCost)) / ($currentStocks + $qty)
                : $unitCost;

            // Create the restock batch record
            $restock = ProductRestock::create([
                'user_id'    => $userId ?? Auth::id(),
                'total_cost' => $totalCost,
                'notes'      => $notes,
            ]);

            // Create the line item
            ItemRestocks::create([
                'restock_id' => $restock->id,
                'product_id' => $product->id,
                'quantity'   => $qty,
                'unit_cost'  => $unitCost,
                'total_cost' => $totalCost,
                'unit_type'  => $unitType,
            ]);

            // Update the product's average cost
            $product->cost = round($newAvgCost, 4);
            $product->save();

            // Delegate stock increment + inventory movement log to existing restore()
            $this->restore(
                productId: $product->id,
                qty:       $qty,
                type:      'restock',
                reference: $restock,
                remarks:   $notes ?? __('Restocked :qty :unit @ :cost each.'), [
                    'qty'  => $qty,
                    'unit' => $unitType,
                    'cost' => number_format($unitCost, 2),
                ]),
                userId:    $userId,
            );

            return $restock;
        });
    }

    public function deduct(
        int $productId,
        int $qty,
        string $type = 'manual_adjustment',
        ?Model $reference = null,
        ?string $remarks = null,
        ?int $userId = null
    ): void
    {
        if ($qty <= 0) {
            return;
        }

        $product = Product::query()->lockForUpdate()->find($productId);

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

        $beforeStocks = (int) $product->stocks;
        $beforeSold = (int) ($product->sold ?? 0);

        $product->stocks -= $qty;
        $product->sold += $qty;
        $product->is_in_stock = $product->stocks > 0;
        $product->save();

        $this->logMovement(
            $product,
            $type,
            $qty,
            $beforeStocks,
            $beforeSold,
            (int) $product->stocks,
            (int) $product->sold,
            $userId,
            $reference,
            $remarks
        );
    }

    public function restore(
        int $productId,
        int $qty,
        string $type = 'manual_adjustment',
        ?Model $reference = null,
        ?string $remarks = null,
        ?int $userId = null
    ): void
    {
        if ($qty <= 0) {
            return;
        }

        $product = Product::query()->lockForUpdate()->find($productId);

        if (! $product) {
            return;
        }

        $beforeStocks = (int) $product->stocks;
        $beforeSold = (int) ($product->sold ?? 0);

        $product->stocks += $qty;
        $product->sold = max(0, $product->sold - $qty);
        $product->is_in_stock = true;
        $product->save();

        $this->logMovement(
            $product,
            $type,
            $qty,
            $beforeStocks,
            $beforeSold,
            (int) $product->stocks,
            (int) $product->sold,
            $userId,
            $reference,
            $remarks
        );
    }

    public function sync(
        int $productId,
        int $oldQty,
        int $newQty,
        string $type = 'manual_adjustment',
        ?Model $reference = null,
        ?string $remarks = null,
        ?int $userId = null
    ): void
    {
        $difference = $newQty - $oldQty;

        if ($difference > 0) {
            $this->deduct($productId, $difference, $type, $reference, $remarks, $userId);
        }

        if ($difference < 0) {
            $this->restore($productId, abs($difference), $type, $reference, $remarks, $userId);
        }
    }

    private function logMovement(
        Product $product,
        string $type,
        int $quantity,
        int $beforeStocks,
        int $beforeSold,
        int $afterStocks,
        int $afterSold,
        ?int $userId = null,
        ?Model $reference = null,
        ?string $remarks = null
    ): void {
        InventoryMovement::create([
            'product_id' => $product->id,
            'user_id' => $userId ?? Auth::id(),
            'type' => $type,
            'quantity' => $quantity,
            'before_stocks' => $beforeStocks,
            'before_sold' => $beforeSold,
            'after_stocks' => $afterStocks,
            'after_sold' => $afterSold,
            'reference_type' => $reference ? $reference->getMorphClass() : null,
            'reference_id' => $reference?->getKey(),
            'remarks' => $remarks,
        ]);
    }
}
