<?php

namespace App\Livewire\Order;

use App\Helpers\PaymentImageHelper;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Create extends Component
{
    // Order form data
    public $orderNumber;
    public $orderType; // hard coded variable 'deliver' or 'walk_in'
    public $paymentType; // hard coded variable 'cash' or 'gcash'
    public $selectedEmployeeId = null;
    public $selectedCustomerId = null;

    // GCash/Online Payment QR image path
    public $currentImage = null;

    // Customer info (editable)
    public $customerName = '';
    public $customerUnit = '';
    public $customerAddress = '';
    public $customerContact = '';

    // New customer state
    public $isCreatingNewCustomer = false;

    // Product items
    public $orderItems = [];

    // Remove modal states - Alpine.js will handle these
    public $currentItemIndex = null;

    // Payment modal states
    public $showPaymentModal = false;
    public $amountReceived = null;
    public $changeAmount = 0;
    public $processingPayment = false;

    // Search terms
    public $customerSearch = '';
    public $employeeSearch = '';
    public $productSearch = '';

    // Product creation state
    public $showProductForm = false;
    public $productTargetIndex = null;
    public $productName = '';
    public $productDescription = '';
    public $productCategory = 'other';
    public $productStocks = 1;
    public $productPrice = 0;

    // Collections for dropdowns
    public $customers = [];
    public $employees = [];
    public $products = [];
    public $errorFields = [];
    public bool $showConfirmModal = false;

    protected $rules = [
        'orderType' => 'required|in:deliver,walk_in',
        'paymentType' => 'required|in:cash,gcash',
        'selectedEmployeeId' => 'nullable|exists:employees,id',
        'selectedCustomerId' => 'nullable|exists:customers,id',
        'customerName' => 'nullable|string|max:255',
        'customerUnit' => 'nullable|string|max:255',
        'customerAddress' => 'nullable|string|max:255',
        'customerContact' => 'nullable|string|max:20',
        'orderItems' => 'required|array|min:1',
        'orderItems.*.product_id' => 'required|exists:products,id',
        'orderItems.*.quantity' => 'required|integer|min:1',
        'orderItems.*.price' => 'nullable|numeric|min:0',
        'orderItems.*.is_free' => 'nullable|boolean',
    ];

    public function mount()
    {
        $this->orderType = 'deliver'; // Explicitly set default
        $this->orderNumber = $this->generateOrderNumber();
        $this->addOrderItem();
        $this->loadData();

        // Set default order type
        $this->orderType = config('storeconfig.default_order_type', 'walk_in');

        // Set default payment type
        $this->paymentType = config('storeconfig.default_payment_type', 'cash');
    }

    public function loadData()
    {
        $this->customers = Customer::query()
            ->orderBy('id', 'desc')
            ->get();

        // Load employees with their in_transit status
        $this->employees = Employee::query()
            ->where('status', 'active')
            ->where('is_archived', false)
            ->orderBy('id', 'desc')
            ->get();

        // Only load products that are actually available
        $this->products = Product::query()
            ->where('is_in_stock', true)
            ->where('stocks', '>', 0)
            ->orderBy('id', 'desc')
            ->get();
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
                $rules['customerContact'] = 'required|string|max:20';
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

    // Check if employee is currently in transit
    public function isEmployeeInTransit(int $employeeId)
    {
        return Order::query()
            ->where('delivered_by', $employeeId)
            ->where('status', 'in_transit')
            ->exists();
    }

    public function generateOrderNumber()
    {
        // Format order numbers as: ORYYMMDDXXXXX (5-digit sequence)
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

    // New customer state management
    public function createNewCustomer()
    {
        $this->isCreatingNewCustomer = true;
        $this->selectedCustomerId = null;
        $this->customerName = '';
        $this->customerUnit = '';
        $this->customerAddress = '';
        $this->customerContact = '';
        $this->resetErrorBag(['selectedCustomerId', 'customerName', 'customerContact', 'customerAddress']);
    }

    public function cancelNewCustomer()
    {
        $this->isCreatingNewCustomer = false;
        $this->reset('customerName', 'customerUnit', 'customerAddress', 'customerContact');
        $this->resetErrorBag(['customerName', 'customerContact', 'customerAddress']);
    }

    // Simplified customer selection
    public function selectCustomer(int $customerId)
    {
        $customer = Customer::find($customerId);
        if ($customer) {
            $this->selectedCustomerId = $customer->id;
            $this->customerName = $customer->name;
            $this->customerUnit = $customer->unit ?? '';
            $this->customerAddress = $customer->address ?? '';
            $this->customerContact = $customer->contact_number ?? '';
            $this->isCreatingNewCustomer = false;
        }
        $this->customerSearch = '';
    }

    // Handle employee selection with confirmation for in-transit employees
    public function selectEmployeeWithCheck(int $employeeId)
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return;
        }

        $isInTransit = $this->isEmployeeInTransit($employeeId);

        if ($isInTransit) {
            // This will be handled by the frontend confirmation
            $this->dispatchBrowserEvent('confirm-employee-selection', [
                'employeeId' => $employeeId,
                'employeeName' => $employee->name
            ]);
        } else {
            // Employee is available, select immediately
            $this->selectEmployee($employeeId);
        }
    }

    // Simplified employee selection
    public function selectEmployee(int $employeeId)
    {
        $this->selectedEmployeeId = $employeeId;
        $this->employeeSearch = '';
    }

    // Force select employee (after confirmation)
    public function forceSelectEmployee(int $employeeId)
    {
        $this->selectEmployee($employeeId);
    }

    // Handle order type changes
    public function updatedOrderType()
    {
        // Clear selections when switching to walk-in
        if ($this->orderType === 'walk_in') {
            $this->selectedEmployeeId = null;
            $this->selectedCustomerId = null;
            $this->customerName = '';
            $this->customerUnit = '';
            $this->customerAddress = '';
            $this->customerContact = '';
        }

        // Clear any validation errors
        $this->resetErrorBag();
    }

    // Debug method - you can call this from browser console: $wire.debugOrderType()
    public function debugOrderType()
    {
        return [
            'orderType' => $this->orderType,
            'selectedEmployeeId' => $this->selectedEmployeeId,
            'selectedCustomerId' => $this->selectedCustomerId,
        ];
    }

    // Product selection with item index
    public function selectProduct(int $productId, int $itemIndex)
    {
        // Block selecting unavailable products (safety)
        $product = Product::query()
            ->where('id', $productId)
            ->where('is_in_stock', true)
            ->where('stocks', '>', 0)
            ->first();

        if ($product && isset($this->orderItems[$itemIndex])) {
            $this->orderItems[$itemIndex]['product_id'] = $product->id;
            $this->orderItems[$itemIndex]['product_name'] = $product->name;
            $this->orderItems[$itemIndex]['stocks'] = (int) $product->stocks;
            $this->orderItems[$itemIndex]['price'] = (float) $product->price;

            // Ensure quantity never exceeds available stock
            $currentQty = (int) ($this->orderItems[$itemIndex]['quantity'] ?? 1);
            $this->orderItems[$itemIndex]['quantity'] = min($currentQty > 0 ? $currentQty : 1, (int) $product->stocks);

            // Recalculate when product changes
            $this->calculateItemTotal($itemIndex);
        }
        $this->productSearch = '';
    }

    // Handle real-time updates for order items
    public function updatedOrderItems($value, $key)
    {
        // $key is like "0.quantity" or "1.product_id"
        $parts = explode('.', $key);
        $index = (int) $parts[0];
        $field = $parts[1] ?? '';

        // If product_id was updated, hydrate name/price and clamp to stock
        if ($field === 'product_id') {
            $index = (int) explode('.', $key)[0];
            $productId = $this->orderItems[$index]['product_id'];

            if ($productId) {
                $product = Product::find($productId);
                $this->orderItems[$index]['stocks'] = (int) $product->stocks;
                if ($product && $product->is_in_stock && $product->stocks > 0) {
                    $this->orderItems[$index]['product_name'] = $product->name;
                    $this->orderItems[$index]['price'] = $product->price;
                $this->orderItems[$index]['stocks'] = 0;

                    $qty = (int) ($this->orderItems[$index]['quantity'] ?? 1);
                    $this->orderItems[$index]['quantity'] = min(max($qty, 1), (int) $product->stocks);

                    $this->calculateItemTotal($index);
                } else {
                    // Clear if product became unavailable
                    $this->orderItems[$index]['product_id'] = null;
                    $this->orderItems[$index]['product_name'] = '';
                    $this->orderItems[$index]['price'] = 0;
                    $this->orderItems[$index]['total'] = 0;
                    $this->addError("orderItems.$index.product_id", 'Product is out of stock.');
                }
            } else {
                $this->orderItems[$index]['product_name'] = '';
                $this->orderItems[$index]['price'] = 0;
                $this->orderItems[$index]['total'] = 0;
            }
        }

        // If quantity was updated, clamp to available stock
        if ($field === 'quantity') {
            $index = (int) explode('.', $key)[0];
            $productId = $this->orderItems[$index]['product_id'] ?? null;

            if ($productId) {
                $product = Product::find($productId);
                $qty = (int) ($this->orderItems[$index]['quantity'] ?? 0);

                if ($product) {
                    $max = max((int) $product->stocks, 0);
                    $this->orderItems[$index]['quantity'] = max(min($qty, $max), 1);
                } else {
                    $this->orderItems[$index]['quantity'] = 1;
                }
            } else {
                $this->orderItems[$index]['quantity'] = max((int) ($this->orderItems[$index]['quantity'] ?? 1), 1);
            }

            $this->calculateItemTotal($index);
        }

        // Keep total in sync when free flag changes (price stays the same)
        if ($field === 'is_free') {
            $this->calculateItemTotal($index);
        }

        // Keep total in sync if unit price is manually edited
        if ($field === 'price') {
            $price = (float) ($this->orderItems[$index]['price'] ?? 0);
            $this->orderItems[$index]['price'] = max($price, 0);
            $this->calculateItemTotal($index);
        }
    }

    public function calculateItemTotal(int $index)
    {
        $isFree = (bool) ($this->orderItems[$index]['is_free'] ?? false);

        if ($isFree) {
            // If marked as no charge, set total to 0 (won't be added to order total)
            $this->orderItems[$index]['total'] = 0;
        } else {
            // Otherwise calculate normally
            $quantity = (int) ($this->orderItems[$index]['quantity'] ?? 0);
            $price = (float) ($this->orderItems[$index]['price'] ?? 0);
            $this->orderItems[$index]['total'] = $quantity * $price;
        }
    }

    // Order Items Management
    public function addOrderItem()
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

    public function removeOrderItem(int $index)
    {
        unset($this->orderItems[$index]);
        $this->orderItems = array_values($this->orderItems);

        // Ensure at least one item exists
        if (empty($this->orderItems)) {
            $this->addOrderItem();
        }
    }

    public function updateQuantity(int $index, int $quantity)
    {
        if ($quantity > 0) {
            $this->orderItems[$index]['quantity'] = $quantity;
            $this->calculateItemTotal($index);
        }
    }

    // Computed Properties
    public function getFilteredCustomersProperty()
    {
        $query = Customer::query();

        // Exclude only customers with an ongoing order (allow completed/cancelled)
        $excludedIds = Order::query()
            ->whereIn('status', ['pending', 'in_transit', 'delivered'])
            ->pluck('customer_id')
            ->filter()
            ->unique()
            ->toArray();

        if (!empty($excludedIds)) {
            $query->whereNotIn('id', $excludedIds);
        }

        // Also exclude the currently selected one from the dropdown
        if ($this->selectedCustomerId) {
            $query->where('id', '!=', (int) $this->selectedCustomerId);
        }

        // Search
        $term = trim($this->customerSearch ?? '');
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('address', 'like', "%{$term}%")
                  ->orWhere('contact_number', 'like', "%{$term}%");
            });
        }

        return $query->orderBy('name','asc')->get();
    }

    public function getFilteredEmployeesProperty()
    {
        if (empty($this->employeeSearch)) {
            return collect($this->employees);
        }

        return collect($this->employees)->filter(function ($employee) {
            return stripos($employee->name, $this->employeeSearch) !== false;
        });
    }

    public function getFilteredProductsProperty()
    {
        // Base list already limited to in-stock in loadData()
        $list = collect($this->products)
            ->filter(fn ($p) => $p->is_in_stock && $p->stocks > 0);

        if (empty($this->productSearch)) {
            return $list;
        }

        return $list->filter(function ($product) {
            return stripos($product->name, $this->productSearch) !== false ||
                   stripos($product->description ?? '', $this->productSearch) !== false;
        });
    }

    public function getTotalAmountProperty()
    {
        return collect($this->orderItems)->sum(function ($item) {
            // Skip items marked as no charge
            if ($item['is_free'] ?? false) {
                return 0;
            }
            return ((int) ($item['quantity'] ?? 0)) * ((float) ($item['price'] ?? 0));
        });
    }

    public function openProductForm(?int $itemIndex = null)
    {
        $this->showProductForm = true;
        $this->productTargetIndex = $itemIndex;
        $this->resetProductForm();
    }

    public function closeProductForm()
    {
        $this->showProductForm = false;
        $this->productTargetIndex = null;
        $this->resetProductForm();
        $this->resetErrorBag(['productName', 'productDescription', 'productCategory', 'productStocks', 'productPrice']);
    }

    public function resetProductForm()
    {
        $this->productName = '';
        $this->productDescription = '';
        $this->productCategory = 'other';
        $this->productStocks = 1;
        $this->productPrice = 0;
    }

    public function createProduct()
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

    public function getSelectedCustomerProperty()
    {
        return $this->selectedCustomerId ? Customer::find($this->selectedCustomerId) : null;
    }

    public function getSelectedEmployeeProperty()
    {
        return $this->selectedEmployeeId ? Employee::find($this->selectedEmployeeId) : null;
    }

    // Payment Modal Methods
    public function openPaymentModal()
    {
        $this->showPaymentModal = true;
        $this->amountReceived = 0;
        $this->changeAmount = 0;
        $this->processingPayment = false;
        $this->currentImage = PaymentImageHelper::getPaymentImageUrl();
    }

    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->amountReceived = 0;
        $this->changeAmount = 0;
        $this->processingPayment = false;
        $this->resetErrorBag(['amountReceived']);
    }

    public function updatedAmountReceived()
    {
        $totalAmount = $this->getTotalAmountProperty();

        // Don't calculate or show errors while the field is being cleared/typed
        if ($this->amountReceived === '' || $this->amountReceived === null) {
            $this->changeAmount = 0;
            $this->resetErrorBag(['amountReceived']);
            return;
        }

        $received = is_numeric($this->amountReceived) ? (float) $this->amountReceived : 0;
        $this->changeAmount = max(0, $received - $totalAmount);

        // Clear the error once they've typed a valid value
        if ($received >= $totalAmount) {
            $this->resetErrorBag(['amountReceived']);
        }
    }

    public function processPayment()
    {
        $this->processingPayment = true;

        if ($this->paymentType === 'cash') {
            $totalAmount = $this->getTotalAmountProperty();

            $this->validate([
                'amountReceived' => [
                    'required',
                    'numeric',
                    'min:' . $totalAmount,
                ]
            ], [
                'amountReceived.required' => 'Please enter the amount received.',
                'amountReceived.numeric'  => 'Amount must be a valid number.',
                'amountReceived.min'      => 'Amount received must be at least ₱' . number_format($totalAmount, 2),
            ]);
        }

        try {
            $this->createOrder();
            $this->closePaymentModal();
        } catch (ValidationException $e) {
            $this->errorFields = array_keys($e->errors());
            $this->dispatch('form-validation-failed', errorFields: $this->errorFields);
            return;
        }
    }

    // Validation method for submit button state
    public function canSubmit()
    {
        // Check if at least one product is selected
        $hasProducts = collect($this->orderItems)->some(fn($item) => !empty($item['product_id']));
        if (!$hasProducts) {
            return false;
        }

        // For delivery orders, check additional requirements
        if ($this->orderType === 'deliver') {
            // Must have delivery person selected
            if (!$this->selectedEmployeeId) {
                return false;
            }

            // Must have customer - either selected or being created
            if (!$this->isCreatingNewCustomer && !$this->selectedCustomerId) {
                return false;
            }

            // If creating new customer, must have all required fields
            if ($this->isCreatingNewCustomer) {
                if (empty($this->customerName) || empty($this->customerAddress) || empty($this->customerContact)) {
                    return false;
                }
            }
        }

        return true;
    }

    // Modified Form Submission
    public function createOrder()
    {
        // Debug: Log the current orderType value
        // Log::info('Creating order with orderType: ' . $this->orderType);

        if (!$this->validateSubmissionRequirements()) {
            return;
        }

        // For walk-in orders, open payment modal first before creating the order
        if ($this->orderType === 'walk_in' && !$this->processingPayment) {
            $this->openPaymentModal();
            return;
        }

        // Pre-check stocks before starting the transaction
        foreach ($this->orderItems as $i => $item) {
            if (!($item['product_id'] ?? null)) {
                $this->dispatch('form-validation-failed', errorFields: ["orderItems.{$i}.product_id"]);
                return;
            }

            $product = Product::find($item['product_id']);

            if (!$product || !$product->is_in_stock || $product->stocks <= 0) {
                $this->dispatch('form-validation-failed', errorFields: ["orderItems.{$i}.product_id"]);
                return;
            }

            $qty = (int) ($item['quantity'] ?? 0);

            if ($qty < 1) {
                $this->dispatch('form-validation-failed', errorFields: ["orderItems.{$i}.quantity"]);
                return;
            }

            if ($qty > (int) $product->stocks) {
                $this->dispatch('form-validation-failed', errorFields: ["orderItems.{$i}.quantity"]);
                return;
            }
        }

        DB::transaction(function () {
            $totalAmount = $this->getTotalAmountProperty();

            // Handle customer creation or selection for delivery orders
            $customerIdForOrder = null;
            if ($this->orderType === 'deliver') {
                if ($this->isCreatingNewCustomer) {
                    // Create a new customer
                    $newCustomer = Customer::create([
                        'name' => $this->customerName,
                        'unit' => ucwords($this->customerUnit),
                        'address' => ucwords($this->customerAddress),
                        'contact_number' => trim($this->customerContact) !== '' ? trim($this->customerContact) : null,
                        'created_by' => Auth::id(),
                    ]);
                    $customerIdForOrder = $newCustomer->id;
                } else {
                    // Use existing customer and update their details
                    $customerIdForOrder = $this->selectedCustomerId;
                    if ($this->selectedCustomerId) {
                        $customer = Customer::find($this->selectedCustomerId);
                        $customer->update([
                            'name' => $this->customerName,
                            'unit' => ucwords($this->customerUnit),
                            'address' => ucwords($this->customerAddress),
                            'contact_number' => trim($this->customerContact) !== '' ? trim($this->customerContact) : null,
                        ]);
                    }
                }
            }

            // Create the order
            $order = Order::create([
                'customer_id' => $customerIdForOrder,
                'created_by' => Auth::id(),
                'delivered_by' => $this->orderType === 'deliver' ? $this->selectedEmployeeId : null,
                'order_total' => $totalAmount,
                'order_type' => $this->orderType,
                'payment_type' => $this->paymentType,
                'status' => $this->orderType === 'walk_in' ? 'completed' : 'pending',
                'is_paid' => $this->orderType === 'walk_in' ? true : false,
                'receipt_number' => $this->orderNumber,

                // Store payment details for walk-in orders
                'amount_received' => $this->orderType === 'walk_in' ? $this->amountReceived : null,
                'change_amount' => $this->orderType === 'walk_in' ? $this->changeAmount : null,
            ]);

            // Update customer information if changed (only for delivery orders)
            if ($this->orderType === 'deliver' && $this->selectedCustomerId && !$this->isCreatingNewCustomer) {
                $customer = Customer::find($this->selectedCustomerId);
                $customer->update([
                    'name' => $this->customerName,
                    'unit' => ucwords($this->customerUnit),
                    'address' => ucwords($this->customerAddress),
                    'contact_number' => trim($this->customerContact) !== '' ? trim($this->customerContact) : null,
                ]);
            }

            // Create order items and atomically update product stock/sold
            foreach ($this->orderItems as $item) {
                if (!($item['product_id'] ?? null)) {
                    continue;
                }

                // Lock the product row to avoid overselling
                $product = Product::query()
                    ->where('id', $item['product_id'])
                    ->lockForUpdate()
                    ->first();

                // Re-validate inside the transaction
                $qty = (int) $item['quantity'];
                if (!$product || !$product->is_in_stock || $product->stocks < $qty) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "orderItems" => "Insufficient stock for product ID {$item['product_id']}.",
                    ]);
                }

                // Create line item
                $isFree = (bool) ($item['is_free'] ?? false);
                $unitPrice = $isFree ? 0 : max((float) ($item['price'] ?? 0), 0);
                $lineTotal = $qty * $unitPrice;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                ]);

                // 3) Deduct stocks, increment sold, and update flag
                $product->stocks = (int) $product->stocks - $qty;
                $product->sold = (int) ($product->sold ?? 0) + $qty;
                $product->is_in_stock = $product->stocks > 0;
                $product->save();
            }
        });

        session()->flash('success', __('Order created successfully!'));
        return redirect()->route('orders');
    }

    public function render()
    {
        return view('livewire.order.create');
    }
}
