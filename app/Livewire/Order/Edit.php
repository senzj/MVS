<?php

namespace App\Livewire\Order;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Edit extends Component
{
    public Order $order;

    // Editable fields
    public string $status        = '';
    public string $payment_type  = '';
    public bool   $is_paid       = false;
    public string $order_type    = '';
    public $delivered_by         = null;
    public $customer_id          = null;

    // Editable order items
    public array $orderItems = [];
    public array $originalItemTotals = [];
    public $products = [];
    public string $productSearch = '';
    public bool $showConfirmModal = false;

    // Customer search / selection helpers
    public string $customerSearch  = '';
    public string $employeeSearch  = '';
    public ?int $selectedCustomerId = null;
    public bool $isCreatingNewCustomer = false;
    public string $customerName = '';
    public string $customerUnit = '';
    public string $customerAddress = '';
    public ?string $customerContact = null;

    // Statuses that lock editing
    const LOCKED_STATUSES = ['in_transit', 'delivered', 'completed', 'cancelled'];

    public function mount(Order $order): void
    {
        // Redirect away if locked
        if (in_array($order->status, self::LOCKED_STATUSES, true)) {
            session()->flash('error', __('Order #:receipt cannot be edited once it is :status.', ['receipt' => $order->receipt_number, 'status' => $order->status]));
            $this->redirect(route('orders'), navigate: true);
            return;
        }

        $this->order        = $order->load(['customer', 'employee', 'orderItems.product']);
        $this->status       = $order->status;
        $this->payment_type = $order->payment_type ?? 'cash';
        $this->is_paid      = (bool) $order->is_paid;
        $this->order_type   = $order->order_type;
        $this->delivered_by = $order->delivered_by;
        $this->customer_id  = $order->customer_id;
        $this->selectedCustomerId = $order->customer_id;

        // Populate customer fields if customer exists
        if ($this->customer_id) {
            $customer = Customer::query()->whereKey($this->customer_id)->first();
            if ($customer) {
                $this->customerName = $customer->name ?? '';
                $this->customerUnit = $customer->unit ?? '';
                $this->customerAddress = $customer->address ?? '';
                $this->customerContact = $customer->contact_number ?? null;
            }
        }

        $this->loadOrderItems();
        $this->products = Product::query()
            ->where('is_in_stock', true)
            ->where('stocks', '>', 0)
            ->orderBy('name', 'asc')
            ->get();
    }

    public function addOrderItem(): void
    {
        $this->orderItems[] = [
            'id' => null,
            'product_id' => null,
            'product_name' => 'Select product',
            'quantity' => 1,
            'price' => 0,
            'original_price' => 0,
            'is_free' => false,
            'total' => 0,
        ];
    }

    public function removeOrderItem(int $index): void
    {
        if (!isset($this->orderItems[$index])) return;

        $productId = $this->orderItems[$index]['product_id'] ?? null;

        unset($this->orderItems[$index]);
        $this->orderItems = array_values($this->orderItems);

        if (empty($this->orderItems)) {
            $this->addOrderItem();
        }
    }

    public function openSaveConfirmation(): void
    {
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

    public function selectProduct(int $productId, int $itemIndex): void
    {
        $product = Product::query()->where('id', $productId)->first();
        if (!$product || !isset($this->orderItems[$itemIndex])) return;

        $this->orderItems[$itemIndex]['product_id'] = $product->id;
        $this->orderItems[$itemIndex]['product_name'] = $product->name;
        $this->orderItems[$itemIndex]['stocks'] = $product->stocks;

        $this->orderItems[$itemIndex]['price'] = (float) $product->price;
        $this->orderItems[$itemIndex]['original_price'] = (float) $product->price;

        $currentQty = (int) ($this->orderItems[$itemIndex]['quantity'] ?? 1);
        $this->orderItems[$itemIndex]['quantity'] = min(max($currentQty,1), (int) $product->stocks);

        $this->calculateItemTotal($itemIndex);
    }

    // Wrapper so the dropdown can call selectProduct(productId, index) like Create
    public function selectProductFromDropdown(int $productId, int $itemIndex): void
    {
        $this->selectProduct($productId, $itemIndex);
    }

    public function getFilteredProductsProperty()
    {
        $list = collect($this->products)->filter(fn ($p) => ($p->is_in_stock ?? true) && (($p->stocks ?? 0) > 0));

        if (empty($this->productSearch)) {
            return $list;
        }

        return $list->filter(function ($product) {
            return stripos($product->name, $this->productSearch) !== false ||
                   stripos($product->description ?? '', $this->productSearch) !== false;
        });
    }

    // ── Computed ───────────────────────────────────────────────────────────────

    public function getFilteredCustomersProperty()
    {
        $q = Customer::query();
        $term = trim($this->customerSearch);
        if ($term !== '') {
            $q->where(function ($x) use ($term) {
                $x->where('name', 'like', "%{$term}%")
                  ->orWhere('address', 'like', "%{$term}%")
                  ->orWhere('contact_number', 'like', "%{$term}%");
            });
        }
        return $q->orderBy('name', 'asc')->take(30)->get();
    }

    public function getFilteredEmployeesProperty()
    {
        $q = Employee::query()
            ->where('status', 'active')
            ->where('is_archived', false);
        $term = trim($this->employeeSearch);
        if ($term !== '') {
            $q->where('name', 'like', "%{$term}%");
        }
        return $q->orderBy('name', 'asc')->take(30)->get();
    }

    public function isEmployeeInTransit(int $employeeId): bool
    {
        return Order::query()
            ->where('delivered_by', $employeeId)
            ->where('status', 'in_transit')
            ->exists();
    }

    public function getIsLockedProperty(): bool
    {
        return in_array($this->order->status, self::LOCKED_STATUSES, true);
    }

    private function loadOrderItems(): void
    {
        $this->orderItems = $this->order->orderItems
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name ?? 'Product #' . $item->product_id,
                    'quantity' => (int) $item->quantity,
                    'price' => (float) $item->unit_price,
                    'stocks' => $item->product?->stocks ?? 0,
                    'original_price' => (float) $item->unit_price,
                    'is_free' => (float) $item->total_price <= 0,
                    'total' => (float) $item->total_price,
                ];
            })
            ->values()
            ->all();

        $this->originalItemTotals = $this->quantityTotalsByProduct($this->orderItems);
    }

    private function quantityTotalsByProduct(array $items): array
    {
        $totals = [];

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);

            if ($productId <= 0) {
                continue;
            }

            $totals[$productId] = ($totals[$productId] ?? 0) + max(1, (int) ($item['quantity'] ?? 0));
        }

        ksort($totals);

        return $totals;
    }

    public function calculateItemTotal(int $index): void
    {
        if (!isset($this->orderItems[$index])) return;

        $isFree = !empty($this->orderItems[$index]['is_free']);

        $quantity = max(1, (int) ($this->orderItems[$index]['quantity'] ?? 1));

        if ($isFree) {
            $this->orderItems[$index]['total'] = 0;
            return;
        }

        $price = max(0, (float) ($this->orderItems[$index]['price'] ?? 0));

        $this->orderItems[$index]['quantity'] = $quantity;
        $this->orderItems[$index]['price'] = $price;
        $this->orderItems[$index]['total'] = $quantity * $price;
    }

    public function updatedOrderItems($value, $key): void
    {
        [$index, $field] = array_pad(explode('.', $key, 2), 2, null);
        $index = (int) $index;

        if (!isset($this->orderItems[$index]) || !$field) {
            return;
        }

        if ($field === 'is_free') {
            if (!empty($this->orderItems[$index]['is_free'])) {
                $this->orderItems[$index]['total'] = 0;
            } else {
                $this->orderItems[$index]['price'] = (float) ($this->orderItems[$index]['original_price'] ?? 0);
                $this->orderItems[$index]['total'] = (int) ($this->orderItems[$index]['quantity'] ?? 1) * (float) ($this->orderItems[$index]['price'] ?? 0);
            }
        }

        if ($field === 'price') {
            $this->orderItems[$index]['price'] = max(0, (float) ($this->orderItems[$index]['price'] ?? 0));
            $this->orderItems[$index]['original_price'] = $this->orderItems[$index]['price'];
        }

        $this->calculateItemTotal($index);
    }

    private function reconcileInventory(array $newItems): void
    {
        $newTotals = $this->quantityTotalsByProduct($newItems);
        $oldTotals = $this->originalItemTotals;
        $productIds = array_unique(array_merge(array_keys($oldTotals), array_keys($newTotals)));
        sort($productIds);

        foreach ($productIds as $productId) {
            $delta = (int) ($newTotals[$productId] ?? 0) - (int) ($oldTotals[$productId] ?? 0);

            if ($delta === 0) {
                continue;
            }

            $product = Product::query()->where('id', $productId)->lockForUpdate()->first();
            if (!$product) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'orderItems' => "Product ID {$productId} could not be found.",
                ]);
            }

            if ($delta > 0 && (int) $product->stocks < $delta) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'orderItems' => "Insufficient stock for {$product->name}. Only {$product->stocks} left.",
                ]);
            }

            $product->stocks = max(0, (int) $product->stocks - $delta);
            $product->sold = max(0, (int) ($product->sold ?? 0) + $delta);
            $product->is_in_stock = $product->stocks > 0;
            $product->save();
        }
    }

    private function recalculateOrderTotal(): float
    {
        return collect($this->orderItems)->sum(fn ($item) => (float) ($item['total'] ?? 0));
    }

    public function getEditedTotalProperty(): float
    {
        return $this->recalculateOrderTotal();
    }

    // ── Actions ────────────────────────────────────────────────────────────────

    public function selectCustomer(int $id): void
    {
        $customer = Customer::query()->whereKey($id)->first();

        if (!$customer) return;

        $this->customer_id = $customer->id;
        $this->selectedCustomerId = $customer->id;
        $this->customerName = $customer->name ?? '';
        $this->customerUnit = $customer->unit ?? '';
        $this->customerAddress = $customer->address ?? '';
        $this->customerContact = $customer->contact_number ?? null;
        $this->isCreatingNewCustomer = false;
        $this->customerSearch = '';
    }

    public function clearCustomer(): void
    {
        $this->customer_id = null;
        $this->selectedCustomerId = null;
        $this->customerName = '';
        $this->customerUnit = '';
        $this->customerAddress = '';
        $this->customerContact = null;
        $this->isCreatingNewCustomer = false;
    }

    public function createNewCustomer(): void
    {
        $this->isCreatingNewCustomer = true;
        $this->selectedCustomerId = null;
        $this->customerName = '';
        $this->customerUnit = '';
        $this->customerAddress = '';
        $this->customerContact = null;
        $this->resetErrorBag(['selectedCustomerId', 'customerName', 'customerUnit', 'customerAddress', 'customerContact']);
    }

    public function cancelNewCustomer(): void
    {
        $this->isCreatingNewCustomer = false;
        $this->customerName = '';
        $this->customerUnit = '';
        $this->customerAddress = '';
        $this->customerContact = null;
        $this->resetErrorBag(['customerName', 'customerUnit', 'customerAddress', 'customerContact']);
    }

    public function updatedSelectedCustomerId($value): void
    {
        if (!empty($value)) {
            $this->selectCustomer((int) $value);
        }
    }

    public function getSelectedCustomerProperty()
    {
        return $this->selectedCustomerId ? Customer::query()->whereKey($this->selectedCustomerId)->first() : null;
    }

    public function selectEmployee(int $id): void
    {
        $this->delivered_by   = $id;
        $this->employeeSearch = '';
    }

    public function clearEmployee(): void
    {
        $this->delivered_by = null;
    }

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
            'orderItems' => 'required|array|min:1',
            'orderItems.*.product_id' => 'required|exists:products,id',
            'orderItems.*.quantity' => 'required|integer|min:1',
            'orderItems.*.price' => 'required|numeric|min:0',
            'orderItems.*.is_free' => 'nullable|boolean',
        ]);

        foreach ($this->orderItems as $index => $item) {
            $this->calculateItemTotal($index);
        }

        DB::transaction(function () {
            $old = $this->order->status;
            $new = $this->status;

            $newItems = collect($this->orderItems)
                ->map(function ($item) {
                    return [
                        'product_id' => (int) $item['product_id'],
                        'quantity' => max(1, (int) $item['quantity']),
                        'price' => max(0, (float) $item['price']),
                        'is_free' => (bool) ($item['is_free'] ?? false),
                        'total' => max(0, (float) ($item['total'] ?? 0)),
                    ];
                })
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
                    'order_id' => $this->order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
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
                'order_total'  => $this->recalculateOrderTotal(),
            ]);
        });

        session()->flash('success', __('Order #:receipt updated.', ['receipt' => $this->order->receipt_number]));
        $this->redirect(route('orders'), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('orders'), navigate: true);
    }

    // ── Inventory helpers ──────────────────────────────────────────────────────

    private function restoreOriginalInventory(): void
    {
        foreach ($this->originalItemTotals as $productId => $quantity) {
            $product = Product::query()->where('id', $productId)->lockForUpdate()->first();
            if (!$product) continue;

            $product->stocks    = (int) $product->stocks + (int) $quantity;
            $product->sold      = max(0, (int) ($product->sold ?? 0) - (int) $quantity);
            $product->is_in_stock = $product->stocks > 0;
            $product->save();
        }
    }

    // ── Render ─────────────────────────────────────────────────────────────────

    public function render()
    {
        $employees = $this->filteredEmployees;
        $customers = $this->filteredCustomers;

        $selectedCustomer = $this->customer_id
            ? Customer::query()->whereKey($this->customer_id)->first()
            : null;

        $selectedEmployee = $this->delivered_by
            ? Employee::query()->whereKey($this->delivered_by)->first()
            : null;

        return view('livewire.order.edit', compact(
            'employees',
            'customers',
            'selectedCustomer',
            'selectedEmployee',
        ));
    }
}
