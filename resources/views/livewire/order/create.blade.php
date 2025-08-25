@section('title', 'Create Orders')
<div class="container mx-auto p-4 max-w-6xl" x-data="{ 
    showCustomerModal: false, 
    showEmployeeModal: false, 
    showProductModal: false,
    currentItemIndex: null,
    openProductModal(index) {
        this.currentItemIndex = index;
        this.showProductModal = true;
    },
    closeProductModal() {
        this.showProductModal = false;
        this.currentItemIndex = null;
    }
}">
    
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
            <a href="{{ route('orders') }}" class="flex items-center gap-1" wire:navigate>
                <button type="button" class="cursor-pointer inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300 transition dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Orders</span>
                </button>
            </a>
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

            {{-- Order Type & Payment Type Row --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                {{-- Order Type Toggle --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <i class="fas fa-route mr-1"></i>Order Type
                    </label>
                    <div class="flex items-center space-x-3">

                        {{-- Order Type toggle --}}
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" 
                                   class="sr-only peer"
                                   :checked="$wire.orderType === 'deliver'"
                                   @change="$wire.set('orderType', $event.target.checked ? 'deliver' : 'walk_in')">
                            <div class="relative w-16 h-8 bg-orange-400 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-8 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-7 after:w-7 after:transition-all peer-checked:bg-blue-600 transition-colors duration-300"></div>
                        </label>

                        {{-- Order Type Label --}}
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300 transition-all duration-300 flex items-center">
                            <i class="mr-1 transition-all duration-300"
                            :class="$wire.orderType === 'deliver' ? 'fas fa-truck text-blue-500' : 'fas fa-walking text-orange-500'"></i>
                            <span x-text="$wire.orderType === 'deliver' ? 'Delivery' : 'Walk-In'"></span>
                        </span>
                    </div>
                    @error('orderType') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                </div>

                {{-- Payment Type --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <i class="fas fa-credit-card mr-1"></i>Payment Type
                    </label>
                    <select wire:model="paymentType" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                        <option value="cash">Cash</option>
                        <option value="gcash">GCash</option>
                    </select>
                    @error('paymentType') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Delivery Person (Only for Delivery Orders) --}}
            @if($orderType === 'deliver')
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <i class="fas fa-user-tie mr-1"></i>Delivery Person
                </label>

                <div x-data="{
                        open: false,
                        dropUp: false,
                        toggle() {
                            this.open = !this.open;
                            if (this.open) this.$nextTick(() => this.reposition());
                        },
                        reposition() {
                            const t = this.$refs.trigger, p = this.$refs.panel;
                            if (!t || !p) return;
                            const rect = t.getBoundingClientRect();
                            const panelHeight = Math.min((p.scrollHeight || 0), 320);
                            const spaceBelow = window.innerHeight - rect.bottom;
                            const spaceAbove = rect.top;
                            this.dropUp = spaceBelow < panelHeight && spaceAbove > spaceBelow;
                        }
                    }"
                    x-init="
                        window.addEventListener('resize', () => open && reposition());
                        window.addEventListener('scroll', () => open && reposition(), true);
                    "
                    class="relative">
                    <button type="button"
                            x-ref="trigger"
                            @click="toggle()"
                            class="cursor-pointer w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 flex items-center justify-between">
                        <span class="truncate">
                            {{ optional($this->selectedEmployee)->name ?? 'Select delivery person' }}
                        </span>
                        <i class="fas fa-chevron-down ml-2 text-sm"></i>
                    </button>

                    <div x-show="open"
                        x-ref="panel"
                        @click.outside="open = false"
                        @keydown.escape.window="open = false"
                        x-cloak
                        :class="dropUp ? 'bottom-full mb-1' : 'top-full mt-1'"
                        class="absolute z-20 w-full bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-lg shadow-lg">
                        <!-- Sticky floating search -->
                        <div class="sticky top-0 z-10 p-2 bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-600">
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400"></i>
                                <input type="text"
                                    wire:model.live="employeeSearch"
                                    placeholder="Search employees..."
                                    class="w-full pl-9 pr-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <ul class="max-h-80 overflow-y-auto p-2">
                            @forelse(($this->filteredEmployees ?? $employees) as $employee)
                                @php
                                    $isInTransit = $this->isEmployeeInTransit($employee->id);
                                @endphp
                                <li class="mb-2 last:mb-0">
                                    <div 
                                        x-data="{ inTransit: {{ $isInTransit ? 'true' : 'false' }}, employeeId: {{ $employee->id }}, employeeName: @js($employee->name) }"
                                        @click="
                                            if (inTransit) {
                                                if (confirm(`Delivery Person ${employeeName} is currently delivering. Assign anyway?`)) {
                                                    $wire.forceSelectEmployee(employeeId);
                                                    open = false;
                                                }
                                            } else {
                                                $wire.selectEmployee(employeeId);
                                                open = false;
                                            }
                                        "
                                        class="p-3 border border-zinc-200 dark:border-zinc-600 rounded-lg cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700 {{ $isInTransit ? 'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800' : '' }}"
                                    >
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100 flex items-center justify-between">
                                            <span>
                                                <i class="fas fa-user-tie mr-1"></i>{{ $employee->name }}
                                            </span>
                                            @if($isInTransit)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                                    <i class="fas fa-shipping-fast mr-1"></i>In Transit
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="p-6 text-center text-zinc-500 dark:text-zinc-400">
                                    <i class="fas fa-user-slash mr-2"></i>No Available Delivery Person. Try to create one.
                                </li>
                            @endforelse
                        </ul>

                    </div>
                </div>

                @if($this->selectedEmployee)
                    @php
                        $selectedIsInTransit = $this->isEmployeeInTransit($this->selectedEmployee->id);
                    @endphp
                    <p class="text-sm mt-1 flex items-center">
                        <i class="fas fa-check mr-1 text-green-600 dark:text-green-400"></i>
                        <span class="text-green-600 dark:text-green-400">Selected: {{ $this->selectedEmployee->name }}</span>
                        @if($selectedIsInTransit)
                            <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                <i class="fas fa-shipping-fast mr-1"></i>In Transit
                            </span>
                        @endif
                    </p>
                @endif
                @error('selectedEmployeeId')
                    <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span>
                @enderror
            </div>
            @endif

        </div>

        {{-- Customer Information Card (Only for Delivery Orders) --}}
        @if($orderType === 'deliver')
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="fas fa-user mr-2"></i>Customer Information
            </h3>

            {{-- Customer Selection --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <i class="fas fa-users mr-1"></i>Customer
                </label>

                <div x-data="{
                        open: false,
                        dropUp: false,
                        toggle() {
                            this.open = !this.open;
                            if (this.open) this.$nextTick(() => this.reposition());
                        },
                        reposition() {
                            const t = this.$refs.trigger, p = this.$refs.panel;
                            if (!t || !p) return;
                            const rect = t.getBoundingClientRect();
                            const panelHeight = Math.min((p.scrollHeight || 0), 320);
                            const spaceBelow = window.innerHeight - rect.bottom;
                            const spaceAbove = rect.top;
                            this.dropUp = spaceBelow < panelHeight && spaceAbove > spaceBelow;
                        }
                    }"
                    x-init="
                        window.addEventListener('resize', () => open && reposition());
                        window.addEventListener('scroll', () => open && reposition(), true);
                    " class="relative">

                    <button type="button"
                            x-ref="trigger"
                            @click="toggle()"
                            class="cursor-pointer w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 flex items-center justify-between">
                        <span class="truncate">
                            {{ optional($this->selectedCustomer)->name ?? 'Select a customer' }}
                        </span>
                        <i class="fas fa-chevron-down ml-2 text-sm"></i>
                    </button>

                    <div x-show="open"
                        x-ref="panel"
                        @click.outside="open = false"
                        @keydown.escape.window="open = false"
                        x-cloak
                        :class="dropUp ? 'bottom-full mb-1' : 'top-full mt-1'"
                        class="absolute z-20 w-full bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-lg shadow-lg">
                        
                        <!-- Sticky floating search -->
                        <div class="sticky top-0 z-10 p-2 bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-600">
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400"></i>
                                <input type="text"
                                    wire:model.live="customerSearch"
                                    placeholder="Search customers"
                                    class="w-full pl-9 pr-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        
                        <ul class="max-h-80 overflow-y-auto p-2">
                            @forelse($this->filteredCustomers as $customer)
                                <li class="mb-2 last:mb-0">
                                    <div wire:click="selectCustomer({{ $customer->id }})"
                                        @click="open = false"
                                        class="p-3 border border-zinc-200 dark:border-zinc-600 rounded-lg cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                            <i class="fas fa-user mr-1"></i>{{ $customer->name }}
                                        </div>
                                        <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                            <i class="fas fa-map-marker-alt mr-1"></i>{{ ($customer->unit ? $customer->unit . ', ' : '') . ($customer->address ?? 'No address') }}
                                        </div>
                                        <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                            <i class="fas fa-phone mr-1"></i>{{ $customer->contact_number ?? 'No contact' }}
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="p-6 text-center text-zinc-500 dark:text-zinc-400">
                                    <i class="fas fa-user-slash mr-2"></i>No Available Customer. Try to create one.
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                @error('selectedCustomerId')
                    <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span>
                @enderror
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
        @endif

        {{-- Order Items Card --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                    <i class="fas fa-shopping-cart mr-2"></i>Order Items
                </h3>
                <button type="button" wire:click="addOrderItem" class="cursor-pointer inline-flex items-center gap-1 px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
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
                                <button type="button" wire:click="removeOrderItem({{ $index }})" class="cursor-pointer text-red-600 hover:text-red-800">
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

                                <!-- Custom dropdown -->
                                <div x-data="{
                                        open: false,
                                        dropUp: false,
                                        toggle() {
                                            this.open = !this.open;
                                            if (this.open) this.$nextTick(() => this.reposition());
                                        },
                                        reposition() {
                                            const t = this.$refs.trigger, p = this.$refs.panel;
                                            if (!t || !p) return;
                                            const rect = t.getBoundingClientRect();
                                            const panelHeight = Math.min((p.scrollHeight || 0), 320); // ~max height incl. search
                                            const spaceBelow = window.innerHeight - rect.bottom;
                                            const spaceAbove = rect.top;
                                            this.dropUp = spaceBelow < panelHeight && spaceAbove > spaceBelow;
                                        }
                                    }"
                                    x-init="
                                        window.addEventListener('resize', () => open && reposition());
                                        window.addEventListener('scroll', () => open && reposition(), true);
                                    " class="relative">
                                    
                                    <button type="button"
                                            x-ref="trigger"
                                            @click="toggle()"
                                            class="cursor-pointer w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 flex items-center justify-between">
                                        <span class="ml-1">
                                            {{ blank($item['product_name'] ?? null) ? 'Select a product' : $item['product_name'] }}
                                        </span>
                                        <span class="ml-1 font-semibold">
                                            @if(isset($item['price'])) ₱{{ number_format($item['price'], 2) }} @endif
                                            <i class="fas fa-chevron-down ml-1"></i>
                                        </span>
                                    </button>

                                    <div x-show="open"
                                         x-ref="panel"
                                         @click.outside="open = false"
                                         @keydown.escape.window="open = false"
                                         x-cloak
                                         :class="dropUp ? 'bottom-full mb-1' : 'top-full mt-1'"
                                         class="absolute z-20 w-full bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-lg shadow-lg">
                                        
                                        <div class="sticky top-0 z-10 p-2 bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-600">
                                            <div class="relative">
                                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400"></i>
                                                <input type="text"
                                                    wire:model.live="productSearch"
                                                    placeholder="Search products..."
                                                    class="w-full pl-9 pr-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            </div>
                                        </div>
                                        
                                        <ul class="max-h-60 overflow-y-auto">
                                            @forelse($this->filteredProducts ?? $products as $product)
                                                <li class="px-3 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-700 cursor-pointer"
                                                    wire:click="selectProduct({{ $product->id }}, {{ $index }})"
                                                    @click="open = false">
                                                    <div class="flex items-start justify-between w-full">
                                                        <div class="ml-2 font-semibold">{{ $product->name }}</div>
                                                        <div class="text-right">
                                                            <div class="font-mono">₱{{ number_format($product->price, 2) }}</div>
                                                            <div class="text-xs text-zinc-600 dark:text-zinc-400">
                                                                <i class="fas fa-boxes mr-1"></i>Stock: {{ $product->stocks ?? 0 }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>
                                            @empty
                                                <li class="px-3 py-2 text-center text-zinc-500 dark:text-zinc-400">
                                                    <i class="fas fa-box-open mr-2"></i>No Available Product. Try to create one.
                                                </li>
                                            @endforelse
                                        </ul>
                                    </div>
                                </div>

                                @error("orderItems.{$index}.product_id")
                                    <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Quantity --}}
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    <i class="fas fa-sort-numeric-up mr-1"></i>Quantity
                                </label>
                                <input type="number" 
                                    wire:model.live="orderItems.{{ $index }}.quantity" 
                                    min="1" 
                                    
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
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
            <button type="button" onclick="window.history.back()" class="cursor-pointer px-6 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                <i class="fas fa-times mr-1"></i>Cancel
            </button>
            <button type="submit" class="cursor-pointer px-6 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-save mr-1"></i>Create Order
            </button>
        </div>
    </form>
</div>
