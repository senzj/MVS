@section('title', 'Sales Record')

<div class="w-full max-w-full overflow-x-hidden px-2 sm:px-4 pb-8">
    <div class="flex flex-col gap-3 mb-5 pt-2 sm:pt-0">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <i class="fas fa-file-invoice text-green-500"></i>
                    {{ __('Record Sales') }}
                </h2>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('orders') }}" wire:navigate
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-zinc-100 dark:bg-zinc-700 text-sm font-semibold text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition">
                    <i class="fas fa-arrow-left"></i>
                    <span>{{ __('Back to Dashboard') }}</span>
                </a>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="openSaveConfirmation" class="space-y-6">
        <div class="grid grid-cols-1">
            <div class="xl:col-span-2 space-y-6">
                <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-5">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                            <i class="fas fa-receipt text-blue-500"></i>
                            {{ __('Sale Information') }}
                        </h3>
                        <span class="text-xs font-mono px-2 py-1 rounded-full bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400">
                            {{ $receiptNumber }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                                {{ __('Order Number') }}
                            </label>
                            <div class="w-full px-3 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 font-mono">
                                {{ $receiptNumber }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                                {{ __('Date & Time') }}
                            </label>
                            <input type="datetime-local" wire:model="saleDate"
                                class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                            @error('saleDate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                                {{ __('Order Type') }}
                            </label>

                            <div class="flex items-center space-x-3 px-1 py-1">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox"
                                           class="sr-only peer"
                                           :checked="$wire.orderType === 'deliver'"
                                           @change="$wire.set('orderType', $event.target.checked ? 'deliver' : 'walk_in')">
                                    <div class="relative w-16 h-8 bg-orange-400 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-8 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-7 after:w-7 after:transition-all peer-checked:bg-blue-600 transition-colors duration-300"></div>
                                </label>

                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300 transition-all duration-300 flex items-center">
                                    <i class="mr-1 transition-all duration-300"
                                       :class="$wire.orderType === 'deliver' ? 'fas fa-truck text-blue-500' : 'fas fa-walking text-orange-500'"></i>
                                    <span x-text="$wire.orderType === 'deliver' ? '{{ __('Delivery') }}' : '{{ __('Walk-In') }}'"></span>
                                </span>
                            </div>

                            @error('orderType') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                                {{ __('Payment Method') }}
                            </label>
                            <select wire:model="paymentType"
                                class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                                <option value="cash">{{ __('Cash') }}</option>
                                <option value="gcash">{{ __('GCash') }}</option>
                            </select>
                            @error('paymentType') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                                {{ __('Payment Status') }}
                            </label>
                            <select wire:model="isPaid"
                                class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                                <option value="1">{{ __('Paid') }}</option>
                                <option value="0">{{ __('Unpaid') }}</option>
                            </select>
                            @error('isPaid') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-1">
                            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                                {{ __('Order Status') }}
                            </label>
                            <select wire:model="status"
                                class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                                <option value="completed">{{ __('Completed') }}</option>
                                <option value="pending">{{ __('Pending') }}</option>
                                <option value="preparing">{{ __('Preparing') }}</option>
                                <option value="in_transit">{{ __('In transit') }}</option>
                                <option value="delivered">{{ __('Delivered') }}</option>
                                <option value="cancelled">{{ __('Cancelled') }}</option>
                            </select>
                            @error('status') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        @if($orderType === 'deliver')
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                                    {{ __('Delivery Person') }}
                                </label>

                                <div x-data="{
                                        open: false,
                                        dropUp: false,
                                        toggle() { this.open = !this.open; if (this.open) this.$nextTick(() => this.reposition()); },
                                        reposition() {
                                            const t = this.$refs.trigger, p = this.$refs.panel; if (!t || !p) return;
                                            const rect = t.getBoundingClientRect();
                                            const panelHeight = Math.min((p.scrollHeight || 0), 320);
                                            const spaceBelow = window.innerHeight - rect.bottom;
                                            const spaceAbove = rect.top;
                                            this.dropUp = spaceBelow < panelHeight && spaceAbove > spaceBelow;
                                        }
                                    }"
                                    x-init="window.addEventListener('resize', () => open && reposition()); window.addEventListener('scroll', () => open && reposition(), true);"
                                    class="relative">

                                    <button type="button"
                                            x-ref="trigger"
                                            @click="toggle()"
                                            class="cursor-pointer w-full px-3 py-2 border border-zinc-200 dark:border-zinc-600 rounded-xl bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 flex items-center justify-between">
                                        <span class="truncate">
                                            {{ optional($this->selectedEmployee)->name ?? __('Select delivery person') }}
                                        </span>
                                        <i class="fas fa-chevron-down ml-2 text-sm"></i>
                                    </button>

                                    <div x-show="open"
                                        x-ref="panel"
                                        @click.outside="open = false"
                                        @keydown.escape.window="open = false"
                                        x-cloak
                                        :class="dropUp ? 'bottom-full mb-1' : 'top-full mt-1'"
                                        class="absolute z-20 w-full bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-xl shadow-lg">

                                        <div class="sticky top-0 z-10 p-2 bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-600">
                                            <input type="text" wire:model.live.debounce.300ms="employeeSearch" placeholder="{{ __('Search delivery person...') }}"
                                                class="w-full pl-3 pr-9 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                                        </div>

                                        <ul class="max-h-80 overflow-y-auto p-2">
                                            @forelse($this->filteredEmployees as $employee)
                                                @php
                                                    $isInTransit = $this->isEmployeeInTransit($employee->id);
                                                @endphp
                                                <li class="mb-1 last:mb-0">
                                                    <button type="button" @click="$wire.selectEmployee({{ $employee->id }}); open = false;"
                                                        class="w-full text-left px-3 py-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition flex items-center gap-2">
                                                        <span class="truncate">{{ $employee->name }}</span>
                                                        @if($isInTransit)
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                                                {{ __('In Transit') }}
                                                            </span>
                                                        @endif
                                                        <span class="ml-auto text-xs text-zinc-400">#{{ $employee->id }}</span>
                                                    </button>
                                                </li>
                                            @empty
                                                <li class="text-xs text-zinc-500 p-3">{{ __('No delivery personnel found.') }}</li>
                                            @endforelse
                                        </ul>
                                    </div>
                                </div>

                                @if($this->selectedEmployee)
                                    @php
                                        $selectedIsInTransit = $this->isEmployeeInTransit($this->selectedEmployee->id);
                                    @endphp
                                    <p class="text-sm mt-2 flex items-center">
                                        <i class="fas fa-check mr-1 text-green-600 dark:text-green-400"></i>
                                        <span class="text-green-600 dark:text-green-400">{{ __('Selected') }}: {{ $this->selectedEmployee->name }}</span>
                                        @if($selectedIsInTransit)
                                            <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                                <i class="fas fa-shipping-fast mr-1"></i>{{ __('In Transit') }}
                                            </span>
                                        @endif
                                    </p>
                                @endif

                                @error('selectedEmployeeId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>
                </div>

                @if($orderType === 'deliver')
                <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-5">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                            <i class="fas fa-users text-blue-500"></i>
                            {{ __('Customer Information') }}
                        </h3>
                    </div>

                    <div>
                        <div x-data="{
                                open: false,
                                dropUp: false,
                                toggle() { this.open = !this.open; if (this.open) this.$nextTick(() => this.reposition()); },
                                reposition() {
                                    const t = this.$refs.trigger, p = this.$refs.panel; if (!t || !p) return;
                                    const rect = t.getBoundingClientRect();
                                    const panelHeight = Math.min((p.scrollHeight || 0), 320);
                                    const spaceBelow = window.innerHeight - rect.bottom;
                                    const spaceAbove = rect.top;
                                    this.dropUp = spaceBelow < panelHeight && spaceAbove > spaceBelow;
                                }
                            }"
                            x-init="window.addEventListener('resize', () => open && reposition()); window.addEventListener('scroll', () => open && reposition(), true);"
                            class="relative">

                            <button type="button"
                                    x-ref="trigger"
                                    @click="toggle()"
                                    class="cursor-pointer w-full px-3 py-2 border border-zinc-200 dark:border-zinc-600 rounded-xl bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 flex items-center justify-between">
                                <span class="truncate">
                                    @if($isCreatingNewCustomer)
                                        <i class="fas fa-user-plus mr-2 text-green-500"></i>{{ __('Creating New Customer') }}
                                    @else
                                        {{ optional($this->filteredCustomers->firstWhere('id', $selectedCustomerId))->name ?? __('Select Customer') }}
                                    @endif
                                </span>
                                <i class="fas fa-chevron-down ml-2 text-sm"></i>
                            </button>

                            <div x-show="open"
                                x-ref="panel"
                                @click.outside="open = false"
                                @keydown.escape.window="open = false"
                                x-cloak
                                :class="dropUp ? 'bottom-full mb-1' : 'top-full mt-1'"
                                class="absolute z-20 w-full bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-xl shadow-lg">

                                <div class="sticky top-0 z-10 p-2 bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-600">
                                    <div class="relative">
                                        <input type="text" wire:model.live.debounce.300ms="customerSearch" placeholder="{{ __('Search customers...') }}"
                                            class="w-full pl-3 pr-9 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                                    </div>
                                </div>

                                <ul class="max-h-80 overflow-y-auto p-2">
                                    <li class="mb-2 last:mb-0">
                                        <button type="button" @click="$wire.createNewCustomer(); open = false;"
                                            class="w-full text-left px-3 py-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition flex items-center gap-2">
                                            <i class="fas fa-user-plus text-green-600"></i>
                                            <span class="text-sm font-medium">{{ __('Create New Customer') }}</span>
                                        </button>
                                    </li>

                                    @forelse($this->filteredCustomers as $customer)
                                        <li class="mb-1 last:mb-0">
                                            <button type="button" @click="$wire.selectCustomer({{ $customer->id }}); open = false;"
                                                class="w-full text-left px-3 py-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition flex items-center gap-2">
                                                <span class="truncate">{{ $customer->name }}</span>
                                                <span class="ml-auto text-xs text-zinc-400">#{{ $customer->id }}</span>
                                            </button>
                                        </li>
                                    @empty
                                        <li class="text-xs text-zinc-500 p-3">{{ __('No customers found.') }}</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>

                        <div class="mt-3 rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50/70 dark:bg-zinc-700/30 p-4">
                            @if($selectedCustomerId || $isCreatingNewCustomer)
                                @if($isCreatingNewCustomer)
                                    <div class="flex items-center justify-between gap-3 mb-3">
                                        <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Create Customer') }}</h4>
                                        <button type="button" wire:click="cancelNewCustomer"
                                            class="text-xs font-semibold text-red-500 hover:text-red-600 transition">
                                            {{ __('Cancel') }}
                                        </button>
                                    </div>
                                @endif

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Name') }}</label>
                                        <input type="text" wire:model="customerName"
                                            class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                                        @error('customerName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Contact Number') }}</label>
                                        <input type="tel" wire:model="customerContact" maxlength="11"
                                            class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                                        @error('customerContact') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Unit') }}</label>
                                        <input type="text" wire:model="customerUnit"
                                            class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Address') }}</label>
                                        <input type="text" wire:model="customerAddress"
                                            class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                                    </div>
                                </div>
                            @else
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    <i class="fas fa-info-circle text-blue-500"></i>
                                    {{ __('No Customer Selected.') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-5">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                            <i class="fas fa-shopping-cart text-blue-500"></i>
                            {{ __('Order Items') }}
                        </h3>

                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" wire:click="openProductForm()"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 active:scale-95 transition-all shadow-md shadow-emerald-500/20">
                                <i class="fas fa-box-open"></i>
                                <span>{{ __('Create Product') }}</span>
                            </button>

                            <button type="button" wire:click="addOrderItem"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 active:scale-95 transition-all shadow-md shadow-blue-500/20">
                                <i class="fas fa-plus"></i>
                                <span>{{ __('Add Item') }}</span>
                            </button>
                        </div>
                    </div>

                    @if($showProductForm)
                        <div class="mb-5 rounded-2xl border border-blue-200 dark:border-blue-900/40 bg-blue-50/70 dark:bg-blue-900/10 p-4">
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <div>
                                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Create Product') }}</h4>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ __('The new product will be added to this sales record after it is created.') }}
                                    </p>
                                </div>
                                <button type="button" wire:click="closeProductForm"
                                    class="text-xs font-semibold text-red-500 hover:text-red-600 transition">
                                    {{ __('Cancel') }}
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Product Name') }}</label>
                                    <input type="text" wire:model="productName"
                                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                                    @error('productName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Category') }}</label>
                                    <select wire:model="productCategory"
                                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                                        @foreach(\App\Models\Product::getCategories() as $key => $category)
                                            <option value="{{ $key }}">{{ $category }}</option>
                                        @endforeach
                                    </select>
                                    @error('productCategory') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Price') }}</label>
                                    <input type="number" step="0.01" min="0" wire:model="productPrice"
                                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                                    @error('productPrice') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Stock Quantity') }}</label>
                                    <input type="number" min="0" wire:model="productStocks"
                                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                                    @error('productStocks') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Description') }}</label>
                                    <textarea wire:model="productDescription" rows="3"
                                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition"></textarea>
                                </div>
                            </div>

                            <div class="flex justify-end mt-4">
                                <button type="button" wire:click="createProduct"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                                    <i class="fas fa-save"></i>
                                    <span>{{ __('Create Product') }}</span>
                                </button>
                            </div>
                        </div>
                    @endif

                    <div class="space-y-4">
                        @foreach($orderItems as $index => $item)
                            <div wire:key="sales-item-{{ $index }}" class="rounded-2xl border border-zinc-200 dark:border-zinc-700 p-4">
                                <div class="flex items-center justify-between gap-3 mb-3">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 text-xs font-bold">
                                            {{ $index + 1 }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 truncate">
                                                {{ $item['product_name'] ?: __('Item Product') }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        @if(count($orderItems) > 1)
                                            <button type="button" wire:click="removeOrderItem({{ $index }})"
                                                class="text-xs font-semibold text-red-500 hover:text-red-600 transition">
                                                {{ __('Remove') }}
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                                    <div class="md:col-span-6">
                                        <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Select product') }}</label>

                                        <div x-data="{ open: false, dropUp: false, toggle() { this.open = !this.open; if (this.open) this.$nextTick(() => this.reposition()); }, reposition() { const t = this.$refs.trigger, p = this.$refs.panel; if (!t || !p) return; const rect = t.getBoundingClientRect(); const panelHeight = Math.min((p.scrollHeight || 0), 320); const spaceBelow = window.innerHeight - rect.bottom; const spaceAbove = rect.top; this.dropUp = spaceBelow < panelHeight && spaceAbove > spaceBelow; } }" x-init="window.addEventListener('resize', () => open && reposition()); window.addEventListener('scroll', () => open && reposition(), true);" class="relative">
                                            <button type="button" x-ref="trigger" @click="toggle()" class="cursor-pointer w-full px-3 py-2 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-left text-zinc-900 dark:text-zinc-100 flex items-center justify-between">
                                                <span class="truncate">{{ $item['product_name'] ?: __('Select product') }}</span>
                                                <i class="fas fa-chevron-down ml-2 text-sm"></i>
                                            </button>

                                            <div x-show="open" x-ref="panel" @click.outside="open = false" @keydown.escape.window="open = false" x-cloak :class="dropUp ? 'bottom-full mb-1' : 'top-full mt-1'" class="absolute z-20 w-full bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-xl shadow-lg">
                                                <div class="sticky top-0 z-10 p-2 bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-600">
                                                    <input type="text" wire:model.live.debounce.300ms="productSearch" placeholder="{{ __('Search products') }}" class="w-full pl-3 pr-9 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                                                </div>

                                                <ul class="max-h-72 overflow-y-auto p-2">
                                                    <li class="mb-2 last:mb-0">
                                                        <button type="button" @click="$wire.openProductForm({{ $index }}); open = false;" class="w-full text-left px-3 py-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition flex items-center gap-2">
                                                            <i class="fas fa-box-open text-emerald-600"></i>
                                                            <span class="text-sm font-medium">{{ __('Create Product') }}</span>
                                                        </button>
                                                    </li>

                                                    @forelse($this->filteredProducts as $product)
                                                        <li class="mb-1 last:mb-0">
                                                            <button type="button" @click="$wire.selectProduct({{ $product->id }}, {{ $index }}); open = false;" class="w-full text-left px-3 py-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition flex items-center gap-2">
                                                                <span class="truncate">{{ $product->name }} - ₱{{ number_format((float) $product->price, 2) }}</span>
                                                                <span class="ml-auto text-xs text-zinc-400">#{{ $product->id }}</span>
                                                            </button>
                                                        </li>
                                                    @empty
                                                        <li class="text-xs text-zinc-500 p-3">{{ __('No products found.') }}</li>
                                                    @endforelse
                                                </ul>
                                            </div>
                                        </div>
                                        @error('orderItems.' . $index . '.product_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="md:col-span-2">
                                            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                                            {{ __('Quantity') }}
                                        </label>
                                        <input type="number" min="1" wire:model.live="orderItems.{{ $index }}.quantity"
                                            class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                                        @error('orderItems.' . $index . '.quantity') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="md:col-span-2">
                                            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                                            {{ __('Price') }}
                                        </label>
                                        <input type="number" min="0" step="0.01" wire:model.live="orderItems.{{ $index }}.price"
                                            class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                                        @error('orderItems.' . $index . '.price') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="md:col-span-2">
                                            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                                            {{ __('Total') }}
                                        </label>
                                        <div class="w-full px-3 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-100 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 font-semibold">
                                            ₱{{ number_format((float) ($item['total'] ?? 0), 2) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 flex justify-between items-center rounded-2xl bg-zinc-50 dark:bg-zinc-700/30 border border-zinc-200 dark:border-zinc-700 p-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Total Amount') }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Multiple items will be recorded under one sales entry.') }}</p>
                        </div>
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                            ₱{{ number_format($this->totalAmount, 2) }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-full flex justify-end gap-3 mt-5">
                <a href="{{ route('orders') }}" wire:navigate
                    class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-zinc-100 dark:bg-zinc-700 text-sm font-semibold text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition">
                    <i class="fas fa-times"></i>
                    <span>{{ __('Cancel') }}</span>
                </a>

                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 px-6 py-4 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 active:scale-95 transition-all shadow-md shadow-blue-500/20 disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-save"></i>
                    <span>{{ __('Save Sales Record') }}</span>
                </button>
            </div>
        </div>
    </form>

    <div x-data="{ show: @entangle('showConfirmModal') }"
        x-show="show"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4 bg-black/50 transition-opacity">
            <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-xl max-w-2xl w-full mx-auto"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95">

                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Review Sales Record') }}</h3>
                        <button type="button" wire:click="closeSaveConfirmation"
                            class="cursor-pointer text-zinc-400 hover:text-zinc-600 transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        @php
                            $reviewDateTime = \Carbon\Carbon::parse($saleDate)
                                ->locale(app()->getLocale())
                                ->isoFormat('LLLL');

                            $paymentLabel = $paymentType === 'cash' ? __('Cash') : __('GCash');

                            $statusLabel = match ($status) {
                                'completed' => __('Completed'),
                                'pending' => __('Pending'),
                                'preparing' => __('Preparing'),
                                'in_transit' => __('In transit'),
                                'delivered' => __('Delivered'),
                                'cancelled' => __('Cancelled'),
                                default => ucfirst(str_replace('_', ' ', $status)),
                            };
                        @endphp
                        <div><span class="font-semibold">{{ __('Order Number') }}:</span> {{ $receiptNumber }}</div>
                        <div><span class="font-semibold">{{ __('Date & Time') }}:</span> {{ $reviewDateTime }}</div>
                        <div><span class="font-semibold">{{ __('Order Type') }}:</span> {{ $orderType === 'deliver' ? __('Delivery') : __('Walk-In') }}</div>
                        <div><span class="font-semibold">{{ __('Payment Method') }}:</span> {{ $paymentLabel }}</div>
                        <div><span class="font-semibold">{{ __('Payment Status') }}:</span> {{ $isPaid ? __('Paid') : __('Unpaid') }}</div>
                        <div><span class="font-semibold">{{ __('Order Status') }}:</span> {{ $statusLabel }}</div>
                    </div>

                    @if($orderType === 'deliver')
                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-3 text-sm">
                            <p class="font-semibold mb-1">{{ __('Delivery') }}</p>
                            <p>{{ __('Delivered By') }}: {{ optional($this->selectedEmployee)->name ?? __('Not selected') }}</p>
                        </div>
                    @endif

                    @if($orderType === 'deliver')
                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-3 text-sm">
                            <p class="font-semibold mb-1">{{ __('Customer') }}</p>
                            <p>{{ __('Name') }}: {{ $customerName ?: __('N/A') }}</p>
                            <p>{{ __('Contact Number') }}: {{ $customerContact ?: __('N/A') }}</p>
                            <p>{{ __('Unit') }}: {{ $customerUnit ?: __('N/A') }}</p>
                            <p>{{ __('Address') }}: {{ $customerAddress ?: __('N/A') }}</p>
                        </div>
                    @endif

                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-3 text-sm">
                        <p class="font-semibold mb-2">{{ __('Items') }}</p>
                        <div class="space-y-1">
                            @foreach($orderItems as $item)
                                @if(!empty($item['product_id']))
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="truncate">{{ $item['product_name'] ?: __('Item Product') }} x{{ (int) ($item['quantity'] ?? 0) }}</span>
                                        <span>₱{{ number_format((float) ($item['total'] ?? 0), 2) }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        <div class="border-t border-zinc-200 dark:border-zinc-700 mt-2 pt-2 flex items-center justify-between font-semibold">
                            <span>{{ __('Total Amount') }}</span>
                            <span>₱{{ number_format($this->totalAmount, 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                    <div class="flex gap-3">
                        <button type="button" wire:click="closeSaveConfirmation"
                            class="cursor-pointer flex-1 px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50">
                            {{ __('Cancel') }}
                        </button>
                        <button type="button" wire:click="saveSalesRecord"
                            wire:loading.attr="disabled"
                            wire:target="saveSalesRecord"
                            class="cursor-pointer flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="saveSalesRecord">{{ __('Confirm') }}</span>
                            <span wire:loading wire:target="saveSalesRecord" class="inline-flex items-center gap-2">
                                <i class="fas fa-spinner fa-spin"></i>{{ __('Saving...') }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</div>
