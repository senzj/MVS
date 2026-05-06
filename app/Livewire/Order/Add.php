<?php

namespace App\Livewire\Order;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Add extends Component
{
    public string $receiptNumber = '';
    public string $saleDate = '';
    public string $orderType = 'walk_in';
    public string $paymentType = 'cash';
    public bool $isPaid = true;
    public string $status = 'completed';
    public ?int $selectedEmployeeId = null;

    public ?int $selectedCustomerId = null;
    public bool $isCreatingNewCustomer = false;
    public string $customerName = '';
    public string $customerUnit = '';
    public string $customerAddress = '';
    public string $customerContact = '';

    public array $customers = [];
    public array $employees = [];
    public array $products = [];
    public array $orderItems = [];
    public array $errorFields = [];

    public string $customerSearch = '';
    public string $employeeSearch = '';
    public string $productSearch = '';
    public bool $showConfirmModal = false;

    public bool $showProductForm = false;
    public ?int $productTargetIndex = null;
    public string $productName = '';
    public string $productDescription = '';
    public string $productCategory = 'other';
    public string|int $productStocks = 1;
    public string|float $productPrice = 0;

    protected $rules = [
        'receiptNumber' => 'required|string|max:255|unique:orders,receipt_number',
        'saleDate' => 'required|date',
        'orderType' => 'required|in:deliver,walk_in',
        'paymentType' => 'required|in:cash,gcash',
        'isPaid' => 'boolean',
        'status' => 'required|in:pending,preparing,in_transit,delivered,completed,cancelled',
        'selectedEmployeeId' => 'nullable|exists:employees,id',
        'selectedCustomerId' => 'nullable|exists:customers,id',
        'customerName' => 'nullable|string|max:255',
        'customerUnit' => 'nullable|string|max:255',
        'customerAddress' => 'nullable|string|max:255',
        'customerContact' => 'nullable|string|max:20',
        'orderItems' => 'required|array|min:1',
        'orderItems.*.product_id' => 'required|exists:products,id',
        'orderItems.*.quantity' => 'required|integer|min:1',
        'orderItems.*.price' => 'required|numeric|min:0',
    ];

    public function mount(): void
    {
        $this->receiptNumber = $this->generateReceiptNumber();
        $this->saleDate = now()->format('Y-m-d\TH:i');
        $this->addOrderItem();
        $this->loadData();

        // Set default order type from store config (env)
        $this->orderType = config('storeconfig.default_order_type', 'walk_in');
    }

    public function loadData(): void
    {
        $this->customers = Customer::query()->orderBy('name', 'asc')->get()->all();
        $this->employees = Employee::query()
            ->where('status', 'active')
            ->where('is_archived', false)
            ->orderBy('name', 'asc')
            ->get()
            ->all();
        $this->products = Product::query()->orderBy('name', 'asc')->get()->all();
    }

    public function selectEmployee(int $employeeId): void
    {
        $employee = Employee::query()
            ->where('status', 'active')
            ->where('is_archived', false)
            ->whereKey($employeeId)
            ->first();

        if (!$employee) {
            return;
        }

        $this->selectedEmployeeId = $employee->id;
        $this->employeeSearch = '';
    }

    public function updatedOrderType($value): void
    {
        if ($value === 'walk_in') {
            $this->selectedEmployeeId = null;
            $this->selectedCustomerId = null;
            $this->isCreatingNewCustomer = false;
            $this->customerName = '';
            $this->customerUnit = '';
            $this->customerAddress = '';
            $this->customerContact = '';
            $this->resetErrorBag(['selectedEmployeeId']);
        }
    }

    public function getFilteredEmployeesProperty()
    {
        $query = Employee::query()
            ->where('status', 'active')
            ->where('is_archived', false);

        $term = trim($this->employeeSearch);
        if ($term !== '') {
            $query->where('name', 'like', "%{$term}%");
        }

        return $query->orderBy('name', 'asc')->take(30)->get();
    }

    public function getSelectedEmployeeProperty()
    {
        return $this->selectedEmployeeId
            ? Employee::query()->whereKey($this->selectedEmployeeId)->first()
            : null;
    }

    public function isEmployeeInTransit(int $employeeId): bool
    {
        return Order::query()
            ->where('delivered_by', $employeeId)
            ->where('status', 'in_transit')
            ->exists();
    }

    public function openSaveConfirmation(): void
    {
        if (!$this->validateSubmissionRequirements()) {
            $this->showConfirmModal = false;
            return;
        }

        $this->showConfirmModal = true;
    }

    public function closeSaveConfirmation(): void
    {
        $this->showConfirmModal = false;
    }

    public function saveSalesRecord(): void
    {
        $this->showConfirmModal = false;
        $this->createOrder();
    }

    protected function getSubmissionRules(): array
    {
        $rules = $this->rules;

        if ($this->orderType === 'deliver') {
            $rules['selectedEmployeeId'] = 'required|exists:employees,id';

            if ($this->isCreatingNewCustomer) {
                $rules['customerName'] = 'required|string|max:255';
                $rules['customerContact'] = 'nullable|string|max:20';
                $rules['customerAddress'] = 'required|string|max:255';
                $rules['selectedCustomerId'] = 'nullable';
            } else {
                $rules['selectedCustomerId'] = 'required|exists:customers,id';
            }
        }

        return $rules;
    }

    protected function validateSubmissionRequirements(): bool
    {
        $rules = $this->getSubmissionRules();

        try {
            $this->validate($rules);
        } catch (ValidationException $e) {
            $this->errorFields = array_keys($e->errors());
            $this->dispatch('form-validation-failed', errorFields: $this->errorFields);
            return false;
        }

        return true;
    }

    public function generateReceiptNumber(): string
    {
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

        return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    public function createNewCustomer(): void
    {
        $this->isCreatingNewCustomer = true;
        $this->selectedCustomerId = null;
        $this->customerName = '';
        $this->customerUnit = '';
        $this->customerAddress = '';
        $this->customerContact = '';
        $this->resetErrorBag(['selectedCustomerId', 'customerName', 'customerUnit', 'customerAddress', 'customerContact']);
    }

    public function cancelNewCustomer(): void
    {
        $this->isCreatingNewCustomer = false;
        $this->customerName = '';
        $this->customerUnit = '';
        $this->customerAddress = '';
        $this->customerContact = '';
        $this->resetErrorBag(['customerName', 'customerUnit', 'customerAddress', 'customerContact']);
    }

    public function selectCustomer(int $customerId): void
    {
        $customer = Customer::query()->whereKey($customerId)->first();

        if (!$customer) {
            return;
        }

        $this->selectedCustomerId = $customer->id;
        $this->customerName = $customer->name ?? '';
        $this->customerUnit = $customer->unit ?? '';
        $this->customerAddress = $customer->address ?? '';
        $this->customerContact = $customer->contact_number ?? null;
        $this->isCreatingNewCustomer = false;
    }

    public function updatedSelectedCustomerId($value): void
    {
        if (!empty($value)) {
            $this->selectCustomer((int) $value);
        }
    }

    public function getFilteredCustomersProperty()
    {
        $query = Customer::query();

        $term = trim($this->customerSearch);
        if ($term !== '') {
            $query->where(function ($subQuery) use ($term) {
                $subQuery->where('name', 'like', "%{$term}%")
                    ->orWhere('unit', 'like', "%{$term}%")
                    ->orWhere('address', 'like', "%{$term}%")
                    ->orWhere('contact_number', 'like', "%{$term}%");
            });
        }

        return $query->orderBy('name', 'asc')->take(30)->get();
    }

    public function getSelectedCustomerProperty()
    {
        return $this->selectedCustomerId ? Customer::query()->whereKey($this->selectedCustomerId)->first() : null;
    }

    public function getFilteredProductsProperty()
    {
        $query = Product::query();

        $term = trim($this->productSearch);
        if ($term !== '') {
            $query->where(function ($subQuery) use ($term) {
                $subQuery->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('category', 'like', "%{$term}%");
            });
        }

        return $query->orderBy('name', 'asc')->take(50)->get();
    }

    public function addOrderItem(): void
    {
        $this->orderItems[] = [
            'product_id' => null,
            'product_name' => '',
            'stocks' => 0,
            'quantity' => 1,
            'price' => 0,
            'total' => 0,
            'is_free' => false,
        ];
    }

    public function removeOrderItem(int $index): void
    {
        if (!isset($this->orderItems[$index])) {
            return;
        }

        unset($this->orderItems[$index]);
        $this->orderItems = array_values($this->orderItems);

        if (empty($this->orderItems)) {
            $this->addOrderItem();
        }
    }

    public function selectProduct(int $productId, int $itemIndex): void
    {
        $product = Product::query()->whereKey($productId)->first();

        if (!$product || !isset($this->orderItems[$itemIndex])) {
            return;
        }

        $this->orderItems[$itemIndex]['product_id'] = $product->id;
        $this->orderItems[$itemIndex]['product_name'] = $product->name;
        $this->orderItems[$itemIndex]['stocks'] = (int) $product->stocks;
        $this->orderItems[$itemIndex]['price'] = (float) $product->price;

        $this->calculateItemTotal($itemIndex);
    }

    public function updatedOrderItems($value, $key): void
    {
        [$index, $field] = array_pad(explode('.', $key, 2), 2, null);
        $index = (int) $index;

        if (!isset($this->orderItems[$index]) || !$field) {
            return;
        }

        if ($field === 'product_id') {
            $productId = (int) ($this->orderItems[$index]['product_id'] ?? 0);
            $product = $productId ? Product::query()->whereKey($productId)->first() : null;

            if ($product) {
                $this->orderItems[$index]['product_name'] = $product->name;
                $this->orderItems[$index]['stocks'] = (int) $product->stocks;
                $this->orderItems[$index]['price'] = (float) $product->price;
            } else {
                $this->orderItems[$index]['product_name'] = '';
                $this->orderItems[$index]['stocks'] = 0;
                $this->orderItems[$index]['price'] = 0;
            }
        }

        if ($field === 'quantity') {
            $this->orderItems[$index]['quantity'] = max(1, (int) ($this->orderItems[$index]['quantity'] ?? 1));
        }

        if ($field === 'price') {
            $this->orderItems[$index]['price'] = max(0, (float) ($this->orderItems[$index]['price'] ?? 0));
        }

        // Keep total in sync when free flag changes (price stays the same)
        if ($field === 'is_free') {
            $this->calculateItemTotal($index);
        }

        $this->calculateItemTotal($index);
    }

    public function calculateItemTotal(int $index): void
    {
        if (!isset($this->orderItems[$index])) {
            return;
        }

        $isFree = (bool) ($this->orderItems[$index]['is_free'] ?? false);

        if ($isFree) {
            // If marked as no charge, set total to 0 (won't be added to order total)
            $this->orderItems[$index]['total'] = 0;
        } else {
            // Otherwise calculate normally
            $quantity = max(1, (int) ($this->orderItems[$index]['quantity'] ?? 1));
            $price = max(0, (float) ($this->orderItems[$index]['price'] ?? 0));
            $this->orderItems[$index]['total'] = $quantity * $price;
        }
    }

    public function getTotalAmountProperty(): float
    {
        return (float) collect($this->orderItems)->sum(function ($item) {
            // Skip items marked as no charge
            if ($item['is_free'] ?? false) {
                return 0;
            }
            return max(1, (int) ($item['quantity'] ?? 1)) * max(0, (float) ($item['price'] ?? 0));
        });
    }

    public function canSubmit(): bool
    {
        // Check if at least one product is selected
        $hasValidItems = collect($this->orderItems)->some(function ($item) {
            return !empty($item['product_id']);
        });

        if (!$hasValidItems) {
            return false;
        }

        // If delivery order, check for required fields
        if ($this->orderType === 'deliver') {
            // Must have delivery person
            if (!$this->selectedEmployeeId) {
                return false;
            }

            // Must have customer (either selected or creating new)
            if (!$this->isCreatingNewCustomer && !$this->selectedCustomerId) {
                return false;
            }

            // If creating new customer, must have name, address, and contact (contact can be nullable but not empty if provided)
            if ($this->isCreatingNewCustomer) {
                if (empty(trim($this->customerName)) || empty(trim($this->customerAddress))) {
                    return false;
                }
                if (!empty(trim($this->customerContact)) && strlen(trim($this->customerContact)) < 11) {
                    return false;
                }
            }
        }

        return true;
    }

    public function openProductForm(?int $itemIndex = null): void
    {
        $this->showProductForm = true;
        $this->productTargetIndex = $itemIndex;
        $this->resetProductForm();
    }

    public function closeProductForm(): void
    {
        $this->showProductForm = false;
        $this->productTargetIndex = null;
        $this->resetProductForm();
        $this->resetErrorBag(['productName', 'productDescription', 'productCategory', 'productStocks', 'productPrice']);
    }

    public function resetProductForm(): void
    {
        $this->productName = '';
        $this->productDescription = '';
        $this->productCategory = 'other';
        $this->productStocks = 1;
        $this->productPrice = 0;
    }

    public function createProduct(): void
    {
        $this->validate([
            'productName' => 'required|string|max:255',
            'productDescription' => 'nullable|string',
            'productCategory' => 'required|string|max:255',
            'productStocks' => 'required|integer|min:0',
            'productPrice' => 'required|numeric|min:0',
        ]);

        $product = Product::create([
            'name' => ucwords(trim($this->productName)),
            'description' => trim((string) $this->productDescription),
            'stocks' => (int) $this->productStocks,
            'sold' => 0,
            'is_in_stock' => (int) $this->productStocks > 0,
            'category' => $this->productCategory,
            'price' => $this->productPrice,
        ]);

        $this->loadData();

        if ($this->productTargetIndex !== null && isset($this->orderItems[$this->productTargetIndex])) {
            $this->selectProduct($product->id, $this->productTargetIndex);
        }

        $this->closeProductForm();
        $this->dispatch('show-success', ['message' => 'Product created successfully!']);
    }

    public function createOrder()
    {
        $rules = $this->getSubmissionRules();

        // Check that at least one item has a valid product selected
        $hasValidItems = collect($this->orderItems)->some(function ($item) {
            return !empty($item['product_id']);
        });

        if (!$hasValidItems) {
            $this->showConfirmModal = false;
            $fields = collect(array_keys($this->orderItems))
                ->map(fn($i) => "orderItems.{$i}.product_id")
                ->values()
                ->all();
            $this->dispatch('form-validation-failed', errorFields: $fields);
            return;
        }

        if ($this->isCreatingNewCustomer && $this->orderType !== 'deliver') {
            $rules['customerName'] = 'required|string|max:255';
            $rules['selectedCustomerId'] = 'nullable';
        }

        try {
            $this->validate($rules);
        } catch (ValidationException $e) {
            $this->errorFields = array_keys($e->errors());
            $this->showConfirmModal = false;
            $this->dispatch('form-validation-failed', errorFields: $this->errorFields);
            return;
        }

        DB::transaction(function () {
            $customerId = $this->selectedCustomerId;

            if ($this->isCreatingNewCustomer) {
                $customer = Customer::create([
                    'name' => ucwords(trim($this->customerName)),
                    'unit' => ucwords(trim($this->customerUnit)),
                    'address' => ucwords(trim($this->customerAddress)),
                    'contact_number' => trim($this->customerContact) !== '' ? trim($this->customerContact) : null,
                ]);

                $customerId = $customer->id;
            } elseif ($this->selectedCustomerId) {
                $customer = Customer::query()->whereKey($this->selectedCustomerId)->first();

                if ($customer) {
                    $customer->update([
                        'name' => ucwords(trim($this->customerName)),
                        'unit' => ucwords(trim($this->customerUnit)),
                        'address' => ucwords(trim($this->customerAddress)),
                        'contact_number' => trim($this->customerContact) !== '' ? trim($this->customerContact) : null,
                    ]);
                }
            }

            $order = Order::create([
                'customer_id' => $customerId,
                'created_by' => Auth::id(),
                'delivered_by' => $this->orderType === 'deliver' ? $this->selectedEmployeeId : null,
                'order_total' => $this->getTotalAmountProperty(),
                'order_type' => $this->orderType,
                'payment_type' => $this->paymentType,
                'status' => $this->status,
                'is_paid' => $this->isPaid,
                'receipt_number' => $this->receiptNumber,
            ]);

            $saleDate = Carbon::parse($this->saleDate);

            DB::table('orders')
                ->where('id', $order->id)
                ->update([
                    'created_at' => $saleDate,
                    'updated_at' => $saleDate,
                ]);

            foreach ($this->orderItems as $item) {
                if (!($item['product_id'] ?? null)) {
                    continue;
                }

                $quantity = max(1, (int) ($item['quantity'] ?? 1));
                $unitPrice = max(0, (float) ($item['price'] ?? 0));

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => (int) $item['product_id'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $quantity * $unitPrice,
                ]);
            }
        });

        $this->resetFormAfterSave();
        $this->showConfirmModal = false;
        $this->dispatch('show-success', ['message' => 'Sales record created successfully!']);
    }

    protected function resetFormAfterSave(): void
    {
        $this->reset([
            'saleDate',
            'orderType',
            'paymentType',
            'isPaid',
            'status',
            'selectedEmployeeId',
            'selectedCustomerId',
            'isCreatingNewCustomer',
            'customerName',
            'customerUnit',
            'customerAddress',
            'customerContact',
            'orderItems',
            'customerSearch',
            'employeeSearch',
            'productSearch',
            'showConfirmModal',
            'showProductForm',
            'productTargetIndex',
            'productName',
            'productDescription',
            'productCategory',
            'productStocks',
            'productPrice',
        ]);

        $this->receiptNumber = $this->generateReceiptNumber();
        $this->saleDate = now()->format('Y-m-d\\TH:i');
        $this->orderType = config('storeconfig.default_order_type', 'walk_in');
        $this->paymentType = 'cash';
        $this->isPaid = true;
        $this->status = 'completed';
        $this->productCategory = 'other';
        $this->productStocks = 1;
        $this->productPrice = 0;

        $this->addOrderItem();
        $this->loadData();
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.order.add');
    }
}
