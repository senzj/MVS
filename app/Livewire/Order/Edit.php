<?php
/**
 * Edit.php  (Edit Order)
 * ======================
 * Key changes:
 *  1. Uses HasOrderForm trait — removes duplicated helpers.
 *  2. updatedOrderItems() delegates to trait.
 *  3. selectEmployee() / selectCustomer() now from trait (supports both
 *     $selectedEmployeeId and $delivered_by property names).
 *  4. Customer validation dispatches clear event on success.
 */

namespace App\Livewire\Order;

use App\Livewire\Concerns\HasOrderForm;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Edit extends Component
{
    use HasOrderForm;

    public Order $order;

    // ── Editable fields ────────────────────────────────────────────
    public string $status       = '';
    public string $payment_type = '';
    public bool   $is_paid      = false;
    public string $order_type   = '';

    // Trait resolves selectedEmployee via $delivered_by OR $selectedEmployeeId
    public $delivered_by        = null;
    public $customer_id         = null;

    // HasOrderForm expects these:
    public ?int   $selectedEmployeeId    = null;
    public ?int   $selectedCustomerId    = null;
    public bool   $isCreatingNewCustomer = false;
    public string $customerName          = '';
    public string $customerUnit          = '';
    public string $customerAddress       = '';
    public string $customerContact       = '';
    public string $customerSearch        = '';
    public string $employeeSearch        = '';
    public string $productSearch         = '';
    public array  $orderItems            = [];
    public bool   $showConfirmModal      = false;
    public string $orderType             = '';  // alias for trait's orderType-aware methods

    // Product form (not used in edit, but trait requires the properties)
    public bool        $showProductForm    = false;
    public ?int        $productTargetIndex = null;
    public string      $productName        = '';
    public string      $productDescription = '';
    public string      $productCategory    = 'other';
    public string|int  $productStocks      = 1;
    public string|float $productPrice      = 0;

    private array $originalItemTotals = [];

    private function lockedStatuses(): array
    {
        return array_values(array_filter(
            (array) config('storeconfig.order_edit_lock_status'),
            fn ($status) => $status !== 'in_transit'
        ));
    }

    public function mount(Order $order): void
    {
        $lockedStatuses = $this->lockedStatuses();

        if (in_array($order->status, $lockedStatuses, true)) {
            session()->flash('error', __('Order #:receipt cannot be edited once it is :status.', [
                'receipt' => $order->receipt_number,
                'status'  => $order->status,
            ]));
            $this->redirect(route('orders'), navigate: true);
            return;
        }

        $this->order        = $order->load(['customer', 'employee', 'orderItems.product']);
        $this->status       = $order->status;
        $this->payment_type = $order->payment_type ?? 'cash';
        $this->is_paid      = (bool) $order->is_paid;
        $this->order_type   = $order->order_type;
        $this->orderType    = $order->order_type;   // keep trait alias in sync
        $this->delivered_by = $order->delivered_by;
        $this->customer_id  = $order->customer_id;
        $this->selectedCustomerId = $order->customer_id;

        if ($this->customer_id) {
            $customer = Customer::query()->whereKey($this->customer_id)->first();
            if ($customer) {
                $this->customerName    = $customer->name          ?? '';
                $this->customerUnit    = $customer->unit          ?? '';
                $this->customerAddress = $customer->address       ?? '';
                $this->customerContact = $customer->contact_number ?? '';
            }
        }

        $this->loadOrderItems();
    }

    // ── Lifecycle ──────────────────────────────────────────────────

    public function updatedOrderItems($value, $key): void
    {
        $this->handleUpdatedOrderItem($value, $key);
    }

    public function updatedOrderType($value): void
    {
        $this->order_type = $value;
        if ($value === 'walk_in') {
            $this->delivered_by       = null;
            $this->customer_id        = null;
            $this->selectedCustomerId = null;
            $this->dispatch('customer-validation-clear');
        }
    }

    // Override trait's selectEmployee to also set $delivered_by
    public function selectEmployee(int $employeeId): void
    {
        $employee = Employee::query()
            ->where('status',      'active')
            ->where('is_archived', false)
            ->whereKey($employeeId)
            ->first();

        if (! $employee) return;

        $this->delivered_by   = $employee->id;
        $this->employeeSearch = '';
        $this->resetErrorBag(['delivered_by']);
    }

    // Override trait's selectCustomer to also set $customer_id
    public function selectCustomer(int $customerId): void
    {
        $customer = Customer::query()->whereKey($customerId)->first();
        if (! $customer) return;

        $this->customer_id         = $customer->id;
        $this->selectedCustomerId  = $customer->id;
        $this->customerName        = $customer->name          ?? '';
        $this->customerUnit        = $customer->unit          ?? '';
        $this->customerAddress     = $customer->address       ?? '';
        $this->customerContact     = $customer->contact_number ?? '';
        $this->isCreatingNewCustomer = false;
        $this->customerSearch      = '';
        $this->resetErrorBag(['customer_id', 'selectedCustomerId', 'customerName', 'customerAddress', 'customerContact']);
        $this->dispatch('customer-validation-clear');
    }

    // ── Modal ──────────────────────────────────────────────────────

    public function openSaveConfirmation(): void
    {
        $this->dispatch('customer-validation-clear');
        $this->showConfirmModal = true;
    }

    public function closeSaveConfirmation(): void
    {
        $this->showConfirmModal = false;
    }

    public function saveSalesRecord(): void
    {
        $this->showConfirmModal = false;
        $this->save();
    }

    // ── Computed ───────────────────────────────────────────────────

    public function getIsLockedProperty(): bool
    {
        $lockedStatuses = $this->lockedStatuses();

        return in_array($this->order->status, $lockedStatuses, true);
    }

    public function getEditedTotalProperty(): float
    {
        return (float) collect($this->orderItems)->sum(fn ($item) => (float) ($item['total'] ?? 0));
    }

    // ── Items ──────────────────────────────────────────────────────

    private function loadOrderItems(): void
    {
        $this->orderItems = $this->order->orderItems
            ->map(fn ($item) => [
                'id'             => $item->id,
                'product_id'     => $item->product_id,
                'product_name'   => $item->product?->name ?? 'Product #' . $item->product_id,
                'quantity'       => (int) $item->quantity,
                'price'          => (float) $item->unit_price,
                'stocks'         => $item->product?->stocks ?? 0,
                'original_price' => (float) $item->unit_price,
                'is_free'        => (float) $item->total_price <= 0,
                'total'          => (float) $item->total_price,
            ])
            ->values()
            ->all();

        $this->originalItemTotals = $this->quantityTotalsByProduct($this->orderItems);
    }

    private function quantityTotalsByProduct(array $items): array
    {
        $totals = [];
        foreach ($items as $item) {
            $id = (int) ($item['product_id'] ?? 0);
            if ($id > 0) {
                $totals[$id] = ($totals[$id] ?? 0) + max(1, (int) ($item['quantity'] ?? 0));
            }
        }
        ksort($totals);
        return $totals;
    }

    // ── Save ───────────────────────────────────────────────────────

    public function save(): void
    {
        if ($this->isLocked) {
            session()->flash('error', __('This order cannot be edited.'));
            return;
        }

        $this->validate([
            'status'       => 'required|in:pending,preparing,in_transit,delivered,completed,cancelled',
            'payment_type' => 'required|in:cash,gcash',
            'is_paid'      => 'boolean',
            'order_type'   => 'required|in:walk_in,deliver',
            'delivered_by' => 'nullable|exists:employees,id',
            'customer_id'  => 'nullable|exists:customers,id',
            'orderItems'              => 'required|array|min:1',
            'orderItems.*.product_id' => 'required|exists:products,id',
            'orderItems.*.quantity'   => 'required|integer|min:1',
            'orderItems.*.price'      => 'required|numeric|min:0',
            'orderItems.*.is_free'    => 'nullable|boolean',
        ]);

        foreach (array_keys($this->orderItems) as $index) {
            $this->calculateItemTotal($index);
        }

        DB::transaction(function () {
            $old      = $this->order->status;
            $new      = $this->status;
            $newItems = collect($this->orderItems)
                ->map(fn ($item) => [
                    'product_id' => (int) $item['product_id'],
                    'quantity'   => max(1, (int) $item['quantity']),
                    'price'      => max(0, (float) $item['price']),
                    'is_free'    => (bool) ($item['is_free'] ?? false),
                    'total'      => max(0, (float) ($item['total'] ?? 0)),
                ])
                ->values()
                ->all();

            if ($new === 'cancelled' && $old !== 'cancelled') {
                $this->restoreOriginalInventory();
            } else {
                $this->reconcileInventory($newItems);
            }

            $this->order->orderItems()->delete();

            foreach ($newItems as $item) {
                OrderItem::create([
                    'order_id'    => $this->order->id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['price'],
                    'total_price' => $item['total'],
                ]);
            }

            $this->order->update([
                'status'       => $new,
                'payment_type' => $this->payment_type,
                'is_paid'      => $this->is_paid,
                'order_type'   => $this->order_type,
                'delivered_by' => $this->delivered_by ?: null,
                'customer_id'  => $this->customer_id  ?: null,
                'order_total'  => $this->editedTotal,
            ]);
        });

        session()->flash('success', __('Order #:receipt updated.', ['receipt' => $this->order->receipt_number]));
        $this->redirect(route('orders'), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('orders'), navigate: true);
    }

    // ── Inventory helpers ──────────────────────────────────────────

    private function reconcileInventory(array $newItems): void
    {
        $newTotals  = $this->quantityTotalsByProduct($newItems);
        $oldTotals  = $this->originalItemTotals;
        $productIds = array_unique(array_merge(array_keys($oldTotals), array_keys($newTotals)));

        foreach ($productIds as $productId) {
            $delta   = (int) ($newTotals[$productId] ?? 0) - (int) ($oldTotals[$productId] ?? 0);
            if ($delta === 0) continue;

            $product = Product::query()->where('id', $productId)->lockForUpdate()->first();

            if (! $product) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'orderItems' => "Product ID {$productId} not found.",
                ]);
            }

            if ($delta > 0 && (int) $product->stocks < $delta) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'orderItems' => "Insufficient stock for {$product->name}. Only {$product->stocks} left.",
                ]);
            }

            $product->stocks    = max(0, (int) $product->stocks - $delta);
            $product->sold      = max(0, (int) ($product->sold ?? 0) + $delta);
            $product->is_in_stock = $product->stocks > 0;
            $product->save();
        }
    }

    private function restoreOriginalInventory(): void
    {
        foreach ($this->originalItemTotals as $productId => $quantity) {
            $product = Product::query()->where('id', $productId)->lockForUpdate()->first();
            if (! $product) continue;

            $product->stocks      = (int) $product->stocks + (int) $quantity;
            $product->sold        = max(0, (int) ($product->sold ?? 0) - (int) $quantity);
            $product->is_in_stock = $product->stocks > 0;
            $product->save();
        }
    }

    // ── Render ─────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.order.edit', [
            'selectedCustomer' => $this->customer_id
                ? Customer::query()->whereKey($this->customer_id)->first()
                : null,
            'selectedEmployee' => $this->delivered_by
                ? Employee::query()->whereKey($this->delivered_by)->first()
                : null,
        ]);
    }
}
