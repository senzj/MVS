<?php

namespace App\Livewire\Order;

use Livewire\Component;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Create extends Component
{
    // Order form data
    public $orderNumber;
    public $paymentType = 'cash';
    public $selectedEmployeeId = null;
    public $selectedCustomerId = null;

    // Customer info (editable)
    public $customerName = '';
    public $customerUnit = '';
    public $customerAddress = '';
    public $customerContact = '';
    
    // Product items
    public $orderItems = [];
    
    // Remove modal states - Alpine.js will handle these
    public $currentItemIndex = null;
    
    // Search terms
    public $customerSearch = '';
    public $employeeSearch = '';
    public $productSearch = '';
    
    // Collections for dropdowns
    public $customers = [];
    public $employees = [];
    public $products = [];
    
    protected $rules = [
        'paymentType' => 'required|in:cash,gcash',
        'selectedEmployeeId' => 'required|exists:employees,id',
        'selectedCustomerId' => 'required|exists:customers,id',
        'customerName' => 'required|string|max:255',
        'customerUnit' => 'nullable|string|max:255',
        'customerAddress' => 'nullable|string|max:255',
        'customerContact' => 'nullable|string|max:20',
        'orderItems' => 'required|array|min:1',
        'orderItems.*.product_id' => 'required|exists:products,id',
        'orderItems.*.quantity' => 'required|integer|min:1',
    ];

    public function mount()
    {
        $this->orderNumber = $this->generateOrderNumber();
        $this->addOrderItem();
        $this->loadData();
    }

    public function loadData()
    {
        $this->customers = Customer::orderBy('id')->get();
        $this->employees = Employee::orderBy('id')->get();

        // Only load products that are actually available
        $this->products = Product::where('is_in_stock', true)
            ->where('stocks', '>', 0)
            ->orderBy('id')
            ->get();
    }

    public function generateOrderNumber()
    {
        // Get the last order ID from the database
        $lastOrder = Order::latest('id')->first();
        $nextId = $lastOrder ? $lastOrder->id + 1 : 1;
        
        // Format: OR-YEAR-XXXXX (5 digits with leading zeros)
        return 'OR-' . now()->format('Y') . '-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
    }

    // Simplified customer selection
    public function selectCustomer($customerId)
    {
        $customer = Customer::find($customerId);
        if ($customer) {
            $this->selectedCustomerId = $customer->id;
            $this->customerName = $customer->name;
            $this->customerUnit = $customer->unit ?? '';
            $this->customerAddress = $customer->address ?? '';
            $this->customerContact = $customer->contact_number ?? '';
        }
        $this->customerSearch = '';
    }

    // Simplified employee selection
    public function selectEmployee($employeeId)
    {
        $this->selectedEmployeeId = $employeeId;
        $this->employeeSearch = '';
    }

    // Product selection with item index
    public function selectProduct($productId, $itemIndex)
    {
        // Block selecting unavailable products (safety)
        $product = Product::where('id', $productId)
            ->where('is_in_stock', true)
            ->where('stocks', '>', 0)
            ->first();

        if ($product && isset($this->orderItems[$itemIndex])) {
            $this->orderItems[$itemIndex]['product_id'] = $product->id;
            $this->orderItems[$itemIndex]['product_name'] = $product->name;
            $this->orderItems[$itemIndex]['price'] = $product->price;

            // Ensure quantity never exceeds available stock
            $currentQty = (int) ($this->orderItems[$itemIndex]['quantity'] ?? 1);
            $this->orderItems[$itemIndex]['quantity'] = min($currentQty > 0 ? $currentQty : 1, (int) $product->stocks);

            // Recalculate when product changes
            $this->calculateItemTotal($itemIndex);
        }
        $this->productSearch = '';
    }

    // Handle real-time updates for order items
    public function updatedOrderItems($value, $name)
    {
        // If product_id was updated, hydrate name/price and clamp to stock
        if (strpos($name, '.product_id') !== false) {
            $index = (int) explode('.', $name)[0];
            $productId = $this->orderItems[$index]['product_id'];

            if ($productId) {
                $product = Product::find($productId);
                if ($product && $product->is_in_stock && $product->stocks > 0) {
                    $this->orderItems[$index]['product_name'] = $product->name;
                    $this->orderItems[$index]['price'] = $product->price;

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
        if (strpos($name, '.quantity') !== false) {
            $index = (int) explode('.', $name)[0];
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
    }

    public function calculateItemTotal($index)
    {
        $quantity = (int) ($this->orderItems[$index]['quantity'] ?? 0);
        $price = (float) ($this->orderItems[$index]['price'] ?? 0);
        $this->orderItems[$index]['total'] = $quantity * $price;
    }

    // Order Items Management
    public function addOrderItem()
    {
        $this->orderItems[] = [
            'product_id' => null,
            'product_name' => '',
            'quantity' => 1,
            'price' => 0,
            'total' => 0,
        ];
    }

    public function removeOrderItem($index)
    {
        unset($this->orderItems[$index]);
        $this->orderItems = array_values($this->orderItems);
        
        // Ensure at least one item exists
        if (empty($this->orderItems)) {
            $this->addOrderItem();
        }
    }

    public function updateQuantity($index, $quantity)
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

        // Exclude all customers that already appear on any order
        $excludedIds = Order::query()
            ->whereNotNull('customer_id')
            ->pluck('customer_id')
            ->unique()
            ->toArray();

        if (!empty($excludedIds)) {
            $query->whereNotIn('id', $excludedIds);
        }

        // Also exclude the currently selected customer from the dropdown
        if ($this->selectedCustomerId) {
            $query->where('id', '!=', (int) $this->selectedCustomerId);
        }

        // Apply search
        $term = trim($this->customerSearch ?? '');
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('address', 'like', "%{$term}%")
                  ->orWhere('contact_number', 'like', "%{$term}%");
            });
        }

        return $query->orderBy('name')->get();
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
            return ((int) ($item['quantity'] ?? 0)) * ((float) ($item['price'] ?? 0));
        });
    }

    public function getSelectedCustomerProperty()
    {
        return $this->selectedCustomerId ? Customer::find($this->selectedCustomerId) : null;
    }

    public function getSelectedEmployeeProperty()
    {
        return $this->selectedEmployeeId ? Employee::find($this->selectedEmployeeId) : null;
    }

    // Form Submission
    public function createOrder()
    {
        $this->validate();

        // Pre-check stocks before starting the transaction
        foreach ($this->orderItems as $i => $item) {
            if (!($item['product_id'] ?? null)) {
                $this->addError("orderItems.$i.product_id", 'Please select a product.');
                return;
            }
            $product = Product::find($item['product_id']);
            if (!$product || !$product->is_in_stock || $product->stocks <= 0) {
                $this->addError("orderItems.$i.product_id", 'Product is out of stock.');
                return;
            }
            $qty = (int) ($item['quantity'] ?? 0);
            if ($qty < 1) {
                $this->addError("orderItems.$i.quantity", 'Quantity must be at least 1.');
                return;
            }
            if ($qty > (int) $product->stocks) {
                $this->addError("orderItems.$i.quantity", "Only {$product->stocks} in stock.");
                return;
            }
        }

        DB::transaction(function () {
            $totalAmount = $this->getTotalAmountProperty();

            // Create the order
            $order = Order::create([
                'customer_id' => $this->selectedCustomerId,
                'created_by' => Auth::id(),
                'delivered_by' => $this->selectedEmployeeId,
                'order_total' => $totalAmount,
                'payment_type' => $this->paymentType,
                'status' => 'pending',
                'is_paid' => false,
                'receipt_number' => $this->orderNumber,
            ]);

            // Update customer information if changed
            if ($this->selectedCustomerId) {
                $customer = Customer::find($this->selectedCustomerId);
                $customer->update([
                    'name' => $this->customerName,
                    'unit' => ucwords($this->customerUnit),
                    'address' => ucwords($this->customerAddress),
                    'contact_number' => $this->customerContact,
                ]);
            }

            // Create order items and atomically update product stock/sold
            foreach ($this->orderItems as $item) {
                if (!($item['product_id'] ?? null)) {
                    continue;
                }

                // Lock the product row to avoid overselling
                $product = Product::where('id', $item['product_id'])->lockForUpdate()->first();

                // Re-validate inside the transaction
                $qty = (int) $item['quantity'];
                if (!$product || !$product->is_in_stock || $product->stocks < $qty) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "orderItems" => "Insufficient stock for product ID {$item['product_id']}.",
                    ]);
                }

                // Create line item
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_price' => $item['price'],
                    'total_price' => $item['total'],
                ]);

                // 3) Deduct stocks, increment sold, and update flag
                $product->stocks = (int) $product->stocks - $qty;
                $product->sold = (int) ($product->sold ?? 0) + $qty;
                $product->is_in_stock = $product->stocks > 0;
                $product->save();
            }
        });

        session()->flash('success', 'Order created successfully!');
        return redirect()->route('orders');
    }

    public function render()
    {
        return view('livewire.order.create');
    }
}
