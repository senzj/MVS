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
    
    // Modal states
    public $showCustomerModal = false;
    public $showEmployeeModal = false;
    public $showProductModal = false;
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
        $this->customers = Customer::orderBy('name')->get();
        $this->employees = Employee::orderBy('name')->get();
        $this->products = Product::orderBy('name')->get();
    }

    public function generateOrderNumber()
    {
        // Get the last order ID from the database
        $lastOrder = Order::latest('id')->first();
        $nextId = $lastOrder ? $lastOrder->id + 1 : 1;
        
        // Format: OR-YEAR-XXXXX (5 digits with leading zeros)
        return 'OR-' . now()->format('Y') . '-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
    }

    // Customer Modal Methods
    public function openCustomerModal()
    {
        $this->customerSearch = '';
        $this->showCustomerModal = true;
    }

    public function closeCustomerModal()
    {
        $this->showCustomerModal = false;
        $this->customerSearch = '';
    }

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
        $this->closeCustomerModal();
    }

    // Employee Modal Methods
    public function openEmployeeModal()
    {
        $this->employeeSearch = '';
        $this->showEmployeeModal = true;
    }

    public function closeEmployeeModal()
    {
        $this->showEmployeeModal = false;
        $this->employeeSearch = '';
    }

    public function selectEmployee($employeeId)
    {
        $this->selectedEmployeeId = $employeeId;
        $this->closeEmployeeModal();
    }

    // Product Modal Methods
    public function openProductModal($itemIndex)
    {
        $this->currentItemIndex = $itemIndex;
        $this->productSearch = '';
        $this->showProductModal = true;
    }

    public function closeProductModal()
    {
        $this->showProductModal = false;
        $this->productSearch = '';
        $this->currentItemIndex = null;
    }

    public function selectProduct($productId)
    {
        if ($this->currentItemIndex !== null) {
            $product = Product::find($productId);
            if ($product) {
                $this->orderItems[$this->currentItemIndex]['product_id'] = $product->id;
                $this->orderItems[$this->currentItemIndex]['product_name'] = $product->name;
                $this->orderItems[$this->currentItemIndex]['price'] = $product->price;
                
                // Recalculate when product changes
                $this->calculateItemTotal($this->currentItemIndex);
            }
        }
        $this->closeProductModal();
    }

    // Handle real-time updates for order items
    public function updatedOrderItems($value, $name)
    {
        // Check if a product_id was updated
        if (strpos($name, '.product_id') !== false) {
            $index = (int) explode('.', $name)[0];
            $productId = $this->orderItems[$index]['product_id'];
            
            if ($productId) {
                $product = Product::find($productId);
                if ($product) {
                    $this->orderItems[$index]['product_name'] = $product->name;
                    $this->orderItems[$index]['price'] = $product->price;
                    $this->calculateItemTotal($index);
                }
            } else {
                // Clear product data if no product selected
                $this->orderItems[$index]['product_name'] = '';
                $this->orderItems[$index]['price'] = 0;
                $this->orderItems[$index]['total'] = 0;
            }
        }
        
        // Check if quantity was updated
        if (strpos($name, '.quantity') !== false) {
            $index = (int) explode('.', $name)[0];
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
        if (empty($this->customerSearch)) {
            return collect($this->customers);
        }
        
        return collect($this->customers)->filter(function ($customer) {
            return stripos($customer->name, $this->customerSearch) !== false ||
                   stripos($customer->address ?? '', $this->customerSearch) !== false ||
                   stripos($customer->contact_number ?? '', $this->customerSearch) !== false;
        });
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
        if (empty($this->productSearch)) {
            return collect($this->products);
        }
        
        return collect($this->products)->filter(function ($product) {
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

        DB::transaction(function () {
            // Create the order
            $order = Order::create([
                'customer_id' => $this->selectedCustomerId,
                'user_id' => Auth::id(),
                'delivery_id' => $this->selectedEmployeeId,
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

            // Create order items
            foreach ($this->orderItems as $item) {
                if ($item['product_id'] && $item['quantity'] > 0) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                    ]);
                }
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
