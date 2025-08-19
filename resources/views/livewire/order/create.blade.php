@section('title', 'Create Orders')
<div class="container mx-auto p-4 max-w-6xl">
    
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                    <i class="fas fa-plus-circle mr-2"></i>Create New Order
                </h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    <i class="fas fa-calendar mr-1"></i>Date: {{ now()->toFormattedDateString() }} | <i class="fas fa-clock mr-1"></i>{{ now()->format('H:i:s') }}
                </p>
            </div>
            <button type="button" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300 transition dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                <a href="{{ route('orders') }}" class="flex items-center gap-1" wire:navigate>
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Dashboard</span>
                </a>
            </button>
        </div>
    </div>

    {{-- Order Form --}}
    <form wire:submit.prevent="createOrder" class="space-y-6">
        
        {{-- Order Information Card --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="fas fa-file-invoice mr-2"></i>Order Information
            </h3>
            
            {{-- Order Number --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <i class="fas fa-hashtag mr-1"></i>Order Number
                </label>
                <div class="w-full px-3 py-2 bg-zinc-100 dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-zinc-100 font-mono">
                    {{ $orderNumber }}
                </div>
            </div>

            {{-- Payment Type --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <i class="fas fa-credit-card mr-1"></i>Payment Type
                </label>
                <select wire:model="paymentType" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                    <option value="cash">Cash</option>
                    <option value="gcash">GCash</option>
                </select>
                @error('paymentType') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
            </div>

            {{-- Delivery Person --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <i class="fas fa-user-tie mr-1"></i>Delivery Person
                </label>
                <div class="flex gap-2">
                    <select wire:model="selectedEmployeeId" class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                        <option value="">Select delivery person...</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" wire:click="openEmployeeModal" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                @if($this->selectedEmployee)
                    <p class="text-sm text-green-600 dark:text-green-400 mt-1">
                        <i class="fas fa-check mr-1"></i>Selected: {{ $this->selectedEmployee->name }}
                    </p>
                @endif
                @error('selectedEmployeeId') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- Customer Information Card --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="fas fa-user mr-2"></i>Customer Information
            </h3>
            
            {{-- Customer Selection --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <i class="fas fa-users mr-1"></i>Select Customer
                </label>
                <div class="flex gap-2">
                    <select wire:model="selectedCustomerId" wire:change="selectCustomer($event.target.value)" class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                        <option value="">Select customer...</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" wire:click="openCustomerModal" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                @error('selectedCustomerId') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
            </div>

            {{-- Customer Details (Editable) --}}
            @if($selectedCustomerId)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            <i class="fas fa-phone mr-1"></i>Contact Number
                        </label>
                        <input type="text" wire:model="customerContact" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                        @error('customerContact') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            <i class="fas fa-building mr-1"></i>Unit
                        </label>
                        <input type="text" wire:model="customerUnit" placeholder="e.g., Unit 123" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                        @error('customerUnit') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            <i class="fas fa-map-marker-alt mr-1"></i>Address
                        </label>
                        <input type="text" wire:model="customerAddress" placeholder="e.g., 123 Main Street" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                        @error('customerAddress') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                    </div>
                </div>
            @endif
        </div>

        {{-- Order Items Card --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                    <i class="fas fa-shopping-cart mr-2"></i>Order Items
                </h3>
                <button type="button" wire:click="addOrderItem" class="inline-flex items-center gap-1 px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <i class="fas fa-plus"></i>
                    Add Item
                </button>
            </div>

            <div class="space-y-4">
                @foreach($orderItems as $index => $item)
                    <div class="border border-zinc-200 dark:border-zinc-600 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                <i class="fas fa-box mr-1"></i>Item {{ $index + 1 }}
                            </h4>
                            @if(count($orderItems) > 1)
                                <button type="button" wire:click="removeOrderItem({{ $index }})" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i>
                                </button>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            {{-- Product Selection --}}
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    <i class="fas fa-tag mr-1"></i>Product
                                </label>
                                <div class="flex gap-2">
                                    <select wire:model.live="orderItems.{{ $index }}.product_id" class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                                        <option value="">Select product...</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }} - ₱{{ number_format($product->price, 2) }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" wire:click="openProductModal({{ $index }})" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                @error("orderItems.{$index}.product_id") <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                            </div>

                            {{-- Quantity --}}
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    <i class="fas fa-sort-numeric-up mr-1"></i>Quantity
                                </label>
                                <input type="number" wire:model.live="orderItems.{{ $index }}.quantity" min="1" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                                @error("orderItems.{$index}.quantity") <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                            </div>

                            {{-- Total --}}
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    <i class="fas fa-calculator mr-1"></i>Total
                                </label>
                                <div class="px-3 py-2 bg-zinc-100 dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-zinc-100">
                                    ₱{{ number_format(($item['quantity'] ?? 0) * ($item['price'] ?? 0), 2) }}
                                </div>
                            </div>
                        </div>

                        @if(!empty($item['product_name']))
                            <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                                <i class="fas fa-info-circle mr-1"></i>Selected: {{ $item['product_name'] }} (₱{{ number_format($item['price'], 2) }} each)
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Order Total --}}
            <div class="mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-600">
                <div class="flex justify-between items-center">
                    <span class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                        <i class="fas fa-receipt mr-2"></i>Total Amount:
                    </span>
                    <span class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">₱{{ number_format($this->totalAmount, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="flex justify-end gap-3">
            <button type="button" onclick="window.history.back()" class="px-6 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                <i class="fas fa-times mr-1"></i>Cancel
            </button>
            <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-save mr-1"></i>Create Order
            </button>
        </div>
    </form>

    {{-- Customer Search Modal --}}
    @if($showCustomerModal)
        <div class="fixed inset-0 bg-zinc-500/80 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
                <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                        <i class="fas fa-search mr-2"></i>Search & Select Customer
                    </h3>
                    <button wire:click="closeCustomerModal" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div class="p-4">
                    <input type="text" wire:model.live="customerSearch" placeholder="Search customers..." class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 mb-4">
                    
                    <div class="max-h-96 overflow-y-auto">
                        @foreach($this->filteredCustomers as $customer)
                            <div wire:click="selectCustomer({{ $customer->id }})" class="p-3 border border-zinc-200 dark:border-zinc-600 rounded-lg cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700 mb-2">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                    <i class="fas fa-user mr-1"></i>{{ $customer->name }}
                                </div>
                                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                    <i class="fas fa-map-marker-alt mr-1"></i>{{ $customer->unit . ', ' . $customer->address ?? 'No address' }}
                                </div>
                                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                    <i class="fas fa-phone mr-1"></i>{{ $customer->contact_number ?? 'No contact' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Employee Search Modal --}}
    @if($showEmployeeModal)
        <div class="fixed inset-0 bg-zinc-500/80 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
                <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                        <i class="fas fa-search mr-2"></i>Select Delivery Person
                    </h3>
                    <button wire:click="closeEmployeeModal" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div class="p-4">
                    <input type="text" wire:model.live="employeeSearch" placeholder="Search employees..." class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 mb-4">
                    
                    <div class="max-h-96 overflow-y-auto">
                        @foreach($this->filteredEmployees as $employee)
                            <div wire:click="selectEmployee({{ $employee->id }})" class="p-3 border border-zinc-200 dark:border-zinc-600 rounded-lg cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700 mb-2">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                    <i class="fas fa-user-tie mr-1"></i>{{ $employee->name }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Product Search Modal --}}
    @if($showProductModal)
        <div class="fixed inset-0 bg-zinc-500/80 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
                <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                        <i class="fas fa-search mr-2"></i>Select Product
                    </h3>
                    <button wire:click="closeProductModal" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div class="p-4">
                    <input type="text" wire:model.live="productSearch" placeholder="Search products..." class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 mb-4">
                    
                    <div class="max-h-96 overflow-y-auto">
                        @foreach($this->filteredProducts as $product)
                            <div wire:click="selectProduct({{ $product->id }})" class="p-3 border border-zinc-200 dark:border-zinc-600 rounded-lg cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700 mb-2">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                            <i class="fas fa-tag mr-1"></i>{{ $product->name }}
                                        </div>
                                        <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $product->description ?? 'No description' }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-zinc-900 dark:text-zinc-100">₱{{ number_format($product->price, 2) }}</div>
                                        <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                            <i class="fas fa-boxes mr-1"></i>Stock: {{ $product->stock ?? 0 }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
