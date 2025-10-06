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
                        
                        {{-- Sticky floating search --}}
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

                        {{-- Sticky floating search --}}
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

                                {{-- Custom dropdown --}}
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
                                    <i class="fas fa-sort-numeric-up mr-1"></i>Quantity / Per Kilo
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


    {{-- Modal Payment --}}
    <div x-data="{ show: @entangle('showPaymentModal') }" 
        x-show="show" 
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;">
        
        {{-- Modal --}}
        <div class="flex items-center justify-center min-h-screen p-4 bg-black/50 transition-opacity">
            <div class="relative bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full mx-auto"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95">
                
                {{-- Modal Heade --}}
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                            Process Payment
                        </h3>
                        <button wire:click="closePaymentModal" 
                                class="cursor-pointer text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Modal Body --}}
                <div class="px-6 py-4">

                    {{-- Payment Type Display --}}
                    <div class="mb-4 flex justify-between">
                        <label class="block text-sm font-medium text-zinc-900 dark:text-zinc-100 mb-2">
                            Payment Method: 
                            <span class="font-semibold text-green-400">
                                {{ $paymentType === 'cash' ? 'Cash' : 'GCash' }}
                            </span>
                        </label>
                        
                        {{-- tooltip button --}}
                        @if ($paymentType == 'gcash')
                            <div class="relative group">
                                {{-- Tooltip trigger button --}}
                                <button type="button" class="cursor-pointer flex items-center justify-center w-6 h-6 text-gray-400 hover:text-gray-600 dark:text-zinc-400 dark:hover:text-zinc-300 transition-colors duration-200">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                
                                {{-- Tooltip content --}}
                                <div class="absolute top-full left-0 mb-2 w-70 p-3 bg-gray-900 dark:bg-zinc-700 text-white text-xs rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-50">
                                    
                                    {{-- Tooltip content --}}
                                    <p class="font-medium mb-2">GCash Payment Steps:</p>
                                    <ol class="list-decimal list-inside space-y-1">
                                        <li>Open your GCash app</li>
                                        <li>Scan the QR code below</li>
                                        <li>Confirm the amount: ₱{{ number_format($this->totalAmount, 2) }}</li>
                                        <li>Complete the payment</li>
                                        <li>Click "Complete Order" button</li>
                                    </ol>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Order Summary --}}
                    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <h4 class="font-medium text-zinc-900 dark:text-zinc-100 mb-2">Order Summary</h4>
                        <div class="space-y-1 text-sm">
                            @foreach($orderItems as $item)
                                @if($item['product_id'])
                                    <div class="flex justify-between">
                                        <span>{{ $item['product_name'] }} ({{ $item['quantity'] }}x)</span>
                                        <span>₱{{ number_format($item['total'], 2) }}</span>
                                    </div>
                                @endif
                            @endforeach
                            <div class="border-t pt-2 mt-2 font-semibold">
                                <div class="flex justify-between">
                                    <span>Total Amount:</span>
                                    <span>₱{{ number_format($this->totalAmount, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Cash Payment Form --}}
                    @if($paymentType === 'cash')
                        <div class="space-y-4">
                            <div>
                                <label for="amountReceived" class="block text-sm font-medium text-zinc-900 dark:text-zinc-100 mb-1">
                                    Amount Received
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-600">₱</span>
                                    <input type="number" 
                                        id="amountReceived"
                                        wire:model.live="amountReceived"
                                        step="0.01" 
                                        min="0"
                                        class="w-full pl-8 pr-3 py-2 rounded-lg focus:ring border border-gray-500"
                                        placeholder="0.00">
                                </div>
                                @error('amountReceived')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Change Amount --}}
                            <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-green-800">Change:</span>
                                    <span class="text-lg font-bold text-green-900">
                                        ₱{{ number_format($changeAmount, 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- GCash Payment --}}
                    @if($paymentType === 'gcash')
                        <div class="text-center space-y-2">

                            {{-- Fixed-size container for image --}}
                            <div class="mx-auto bg-gray-100 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center overflow-hidden
                                {{ $currentImage ? '' : 'w-32 h-32' }}">
                                
                                @if ($currentImage)
                                    <img src="{{ $currentImage }}"
                                        alt="GCash QR Code"
                                        class="max-w-full max-h-70 object-contain rounded-lg" />
                                @else
                                    <span class="text-gray-400 text-sm">No Image</span>
                                @endif
                            </div>

                            {{-- Caption below --}}
                            <div>
                                <p class="text-sm text-gray-500 mt-1">
                                    Scan to pay ₱{{ number_format($this->totalAmount, 2) }}.
                                </p>
                            </div>

                        </div>
                    @endif
                </div>
                
                {{--  Modal Footer --}}
                <div class="px-6 py-4 border-t border-gray-200">
                    <div class="flex space-x-3">
                        <button wire:click="closePaymentModal" 
                                class="cursor-pointer flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-blue-500">
                            Cancel
                        </button>
                        
                        <button wire:click="processPayment"
                                wire:loading.attr="disabled"
                                wire:target="processPayment"
                                @if($paymentType === 'cash' && $changeAmount < 0) disabled @endif
                                class="cursor-pointer flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            
                            <span wire:loading.remove wire:target="processPayment">
                                Complete Order
                            </span>
                            <span wire:loading wire:target="processPayment" class="flex items-center justify-center">
                                <i class="fas fa-spinner fa-spin mr-2"></i>
                                Processing...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Alpine.js cloaking styles if not already present --}}
    <style>
        [x-cloak] { display: none !important; }
    </style>

</div>