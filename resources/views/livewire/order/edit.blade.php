@section('title', __('Edit Orders'))

<div class="w-full max-w-full overflow-x-hidden">

    {{-- HEADER --}}
    <div class="flex items-start justify-between gap-3 py-2">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <i class="fas fa-file-pen text-blue-500"></i>
                {{ __('Edit Orders') }}
            </h2>

            <div class="inline-flex items-center gap-2 px-2 py-1 text-gray-800 dark:text-gray-300 text-sm"
                x-data="{
                    locale: '{{ app()->getLocale() }}',
                    nowMs: Date.now(),
                    get intlLocale() { return this.locale === 'cn' ? 'zh-CN' : this.locale; },
                    tick() { this.nowMs = Date.now(); },
                    start() { this.tick(); setInterval(() => this.tick(), 1000); },
                    get formattedDate() {
                        return new Intl.DateTimeFormat(this.intlLocale, { weekday: 'long', year:'numeric', month:'long', day:'numeric' }).format(this.nowMs);
                    },
                    get formattedTime() {
                        return new Intl.DateTimeFormat(this.intlLocale, { hour:'numeric', minute:'2-digit', second:'2-digit', hour12: true }).format(this.nowMs);
                    }
                }"
                x-init="start()">
                <span class="hidden sm:inline" x-text="formattedDate"></span>
                <span class="hidden sm:inline">•</span>
                <span x-text="formattedTime"></span>
            </div>
        </div>

        <button wire:click="cancel"
            class="cursor-pointer inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300 transition dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
            <i class="fas fa-arrow-left"></i>
            <span class="hidden sm:inline">{{ __('Back to Dashboard') }}</span>
        </button>
    </div>

    <div class="grid grid-cols-1 gap-5">

        {{-- Order Information --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-5 space-y-4">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="fas fa-file-invoice text-blue-500 mr-2"></i>{{ __('Order Information') }}
            </h3>

            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500 dark:text-zinc-400"><i class="fas fa-hashtag mr-1"></i>{{ __('Order ID') }}</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">#{{ $order->id }}</dd>
                </div>

                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500 dark:text-zinc-400"><i class="fas fa-receipt mr-1"></i>{{ __('Receipt Number') }}</dt>
                    <dd class="font-mono text-zinc-900 dark:text-zinc-100">{{ $order->receipt_number }}</dd>
                </div>

                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500 dark:text-zinc-400"><i class="fas fa-calendar mr-1"></i>{{ __('Date & Time') }}</dt>
                    <dd class="text-zinc-900 dark:text-zinc-100">
                        @php $loc = app()->getLocale() === 'cn' ? 'zh_CN' : app()->getLocale(); @endphp
                        {{ $order->created_at->locale($loc)->isoFormat('MMM D, YYYY · hh:mm A') }}
                    </dd>
                </div>
            </dl>
        </div>

        {{-- ORDER STATUS & PAYMENT --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-5 space-y-4">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="fas fa-file-invoice text-blue-500 mr-2"></i>{{ __('Order Status') }}
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">

                {{-- Status --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                        <i class="fas fa-circle-dot mr-1"></i>{{ __('Status') }}
                    </label>
                    <select wire:model="status"
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                                bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition cursor-pointer">
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="preparing">{{ __('Preparing') }}</option>
                    </select>
                </div>

                {{-- Order Type --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                        <i class="fas fa-route mr-1"></i>{{ __('Order Type') }}
                    </label>
                    <select wire:model.live="order_type"
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                                bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition cursor-pointer">
                        <option value="deliver">{{ __('Delivery') }}</option>
                        <option value="walk_in">{{ __('Walk-In') }}</option>
                    </select>
                </div>

                {{-- Payment Method --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                        <i class="fas fa-credit-card mr-1"></i>{{ __('Payment Method') }}
                    </label>
                    <select wire:model="payment_type"
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                                bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition cursor-pointer">
                        <option value="cash">{{ __('Cash') }}</option>
                        <option value="gcash">{{ __('GCash / Online') }}</option>
                    </select>
                </div>

                {{-- Payment Status --}}
                <div class="flex items-end pb-1">
                    <label class="inline-flex items-center gap-3 cursor-pointer select-none group">
                        <div class="relative">
                            <input type="checkbox" wire:model="is_paid" class="sr-only peer">
                            <div class="w-12 h-6 rounded-full bg-zinc-300 dark:bg-zinc-600 peer-checked:bg-green-500 transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-6"></div>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-0.5">{{ __('Payment Status') }}</p>
                            <p class="text-sm font-semibold"
                                :class="$wire.is_paid ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                {{ $is_paid ? __('Paid') : __('Unpaid') }}
                            </p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        {{-- DELIVERY PERSON --}}
        @if($order_type === 'deliver')
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-5 space-y-3">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="fas fa-user-tie text-blue-500 mr-2"></i>{{ __('Delivery Person') }}
            </h3>

            {{-- Search and Employee List --}}
            <div class="mb-2">
                {{-- Search --}}
                <div class="relative">
                    <input type="text"
                            wire:model.live.debounce.200ms="employeeSearch"
                            placeholder="{{ __('Search delivery person...') }}"
                            class="w-full pl-9 pr-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                                    bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                    focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm pointer-events-none"></i>
                </div>

                {{-- Employee list --}}
                @if(trim($employeeSearch) !== '')
                    <div class="max-h-52 overflow-y-auto rounded-xl border border-zinc-100 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-700">
                        @forelse($employees as $emp)
                            <button type="button" wire:click="selectEmployee({{ $emp->id }})"
                                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors text-left cursor-pointer
                                        {{ $delivered_by == $emp->id ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                <div class="w-8 h-8 rounded-full bg-indigo-400 flex items-center justify-center shrink-0">
                                    <span class="text-white text-xs font-bold">{{ strtoupper(substr($emp->name, 0, 1)) }}</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">{{ $emp->name }}</p>
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ $emp->contact_number ?: 'No contact' }}</p>
                                </div>
                                @if($delivered_by == $emp->id)
                                    <i class="fas fa-check text-blue-500 ml-auto shrink-0"></i>
                                @endif
                            </button>
                        @empty
                            <div class="px-4 py-6 text-center text-zinc-400 dark:text-zinc-500 text-sm">
                                <i class="fas fa-user-slash mb-2 block text-2xl opacity-40"></i>
                                {{ __('No employees found.') }}
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>

            {{-- Currently selected --}}
            @if($selectedEmployee)
                <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800">
                    <div class="w-9 h-9 rounded-full bg-indigo-500 flex items-center justify-center shrink-0 shadow-sm">
                        <span class="text-white text-sm font-bold">{{ strtoupper(substr($selectedEmployee->name, 0, 1)) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-indigo-900 dark:text-indigo-100 truncate">{{ $selectedEmployee->name }}</p>
                        @if($selectedEmployee->contact_number)
                            <p class="text-xs text-indigo-600 dark:text-indigo-400">{{ $selectedEmployee->contact_number }}</p>
                        @endif
                    </div>
                    <button type="button" wire:click="clearEmployee"
                        class="shrink-0 w-7 h-7 flex items-center justify-center rounded-full text-indigo-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors cursor-pointer">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            @else
                <p class="text-sm text-zinc-400 dark:text-zinc-500 italic">{{ __('No delivery person assigned') }}</p>
            @endif
        </div>
        @endif

        {{-- CUSTOMER --}}
        @if($order_type === 'deliver')
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-5 space-y-3">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="fas fa-user text-blue-500 mr-2"></i>{{ __('Customer Information') }}
            </h3>

            {{-- Search and Customer List --}}
            <div class="mb-2">
                {{-- Search --}}
                <div class="relative">
                    <input type="text"
                            wire:model.live.debounce.200ms="customerSearch"
                            placeholder="{{ __('Search customers...') }}"
                            class="w-full pl-9 pr-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                                    bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                    focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm pointer-events-none"></i>
                </div>

                {{-- Customer list --}}
                @if(trim($customerSearch) !== '')
                    <div class="max-h-52 overflow-y-auto rounded-xl border border-zinc-100 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-700">
                        @forelse($customers as $cust)
                            <button type="button" wire:click="selectCustomer({{ $cust->id }})"
                                class="w-full flex items-start gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors text-left cursor-pointer
                                        {{ $customer_id == $cust->id ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                <div class="w-8 h-8 rounded-full bg-blue-400 flex items-center justify-center shrink-0 mt-0.5">
                                    <span class="text-white text-xs font-bold">{{ strtoupper(substr($cust->name, 0, 1)) }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">{{ $cust->name }}</p>
                                    @if($cust->unit || $cust->address)
                                        <p class="text-xs text-zinc-400 dark:text-zinc-500 truncate">
                                            {{ implode(', ', array_filter([$cust->unit, $cust->address])) }}
                                        </p>
                                    @endif
                                    @if($cust->contact_number)
                                        <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ $cust->contact_number }}</p>
                                    @endif
                                </div>
                                @if($customer_id == $cust->id)
                                    <i class="fas fa-check text-blue-500 ml-auto shrink-0 mt-1"></i>
                                @endif
                            </button>
                        @empty
                            <div class="px-4 py-6 text-center text-zinc-400 dark:text-zinc-500 text-sm">
                                <i class="fas fa-user-slash mb-2 block text-2xl opacity-40"></i>
                                {{ __('No customers found.') }}
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>
            {{-- Currently selected --}}
            @if($selectedCustomer)
                <div class="flex items-start gap-3 px-4 py-3 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                    <div class="w-9 h-9 rounded-full bg-blue-500 flex items-center justify-center shrink-0 shadow-sm mt-0.5">
                        <span class="text-white text-sm font-bold">{{ strtoupper(substr($selectedCustomer->name, 0, 1)) }}</span>
                    </div>
                    <div class="flex-1 min-w-0 space-y-0.5">
                        <p class="text-sm font-semibold text-blue-900 dark:text-blue-100">{{ $selectedCustomer->name }}</p>
                        @if($selectedCustomer->unit || $selectedCustomer->address)
                            <p class="text-xs text-blue-600 dark:text-blue-400 truncate">
                                <i class="fas fa-map-marker-alt mr-1"></i>
                                {{ implode(', ', array_filter([$selectedCustomer->unit, $selectedCustomer->address])) }}
                            </p>
                        @endif
                        @if($selectedCustomer->contact_number)
                            <p class="text-xs text-blue-600 dark:text-blue-400">
                                <i class="fas fa-phone mr-1"></i>{{ $selectedCustomer->contact_number }}
                            </p>
                        @endif
                    </div>
                    <button type="button" wire:click="clearCustomer"
                        class="shrink-0 w-7 h-7 flex items-center justify-center rounded-full text-blue-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors cursor-pointer mt-0.5">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            @else
                <p class="text-sm text-zinc-400 dark:text-zinc-500 italic">{{ __('No customer assigned') }}</p>
            @endif
        </div>
        @endif

        {{-- Order Items --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                    <i class="fas fa-shopping-cart text-blue-500 mr-2"></i>{{ __('Order Items') }}
                </h3>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="addOrderItem"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 active:scale-95 transition-all shadow-md shadow-blue-500/20">
                        <i class="fas fa-plus"></i>{{ __('Add Item') }}
                    </button>
                </div>
            </div>

            @if($orderItems)
                <div class="space-y-3">
                    @foreach($orderItems as $index => $item)
                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 space-y-3">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 text-xs font-bold">
                                        {{ $index + 1 }}
                                    </span>
                                    <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 truncate">
                                        {{ $item['product_name'] ?: __('Item Product') }}
                                    </p>
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

                            <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Product') }}</label>
                                    @include('livewire.partials.orders.form.products', ['index' => $index, 'item' => $item])
                                </div>

                                {{-- Quantity --}}
                                <div class="md:col-span-1">
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Quantity / per kilo') }}</label>
                                    <input type="number"
                                        min="1"
                                        wire:model.live="orderItems.{{ $index }}.quantity"
                                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 transition">
                                </div>

                                {{-- Unit Price --}}
                                <div class="md:col-span-1">
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Unit Price') }}</label>
                                    <input type="number"
                                        min="0"
                                        step="0.01"
                                        wire:model.live="orderItems.{{ $index }}.price"
                                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 transition">
                                </div>

                                {{-- Total + No Charge --}}
                                <div class="md:col-span-1">
                                    {{-- Total --}}
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Total') }}</label>
                                    <div class="px-3 py-2 bg-zinc-100 dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-zinc-100">
                                        ₱{{ number_format($item['total'] ?? 0, 2) }}
                                    </div>

                                    {{-- No Charge --}}
                                    <div class="mt-4 flex items-center gap-2">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" class="sr-only peer"
                                                :checked="$wire.orderItems.{{ $index }}.is_free"
                                                @change="$wire.set('orderItems.{{ $index }}.is_free', $event.target.checked)">
                                            <div class="relative w-12 h-6 bg-zinc-500 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-6 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600 transition-colors duration-300"></div>
                                        </label>
                                        <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('No Charge') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Total Amount --}}
                <div class="mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-600">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                            <i class="fas fa-receipt mr-2"></i>{{ __('Total Amount') }}:
                        </span>
                        <span class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">₱{{ number_format($this->editedTotal, 2) }}</span>
                    </div>
                </div>
            @endif
        </div>

        {{-- Save / Cancel buttons --}}
        <div class="flex justify-center gap-3 w-full">
            <button type="button" wire:click="cancel" class="inline-flex items-center justify-center w-full gap-2 px-4 py-3 rounded-lg bg-zinc-100 dark:bg-zinc-700 text-sm font-semibold text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition">
                <i class="fas fa-times mr-1"></i>{{ __('Cancel') }}
            </button>

            <button type="button" wire:click="openSaveConfirmation"
                wire:loading.attr="disabled"
                wire:target="openSaveConfirmation"
                class="cursor-pointer w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-blue-600 text-white text-sm font-semibold
                        hover:bg-blue-700 active:scale-95 transition-all shadow-md shadow-blue-500/20 disabled:opacity-60">
                <span wire:loading.remove wire:target="openSaveConfirmation">
                    <i class="fas fa-save mr-1"></i>{{ __('Save Changes') }}
                </span>
                <span wire:loading wire:target="openSaveConfirmation" class="flex items-center gap-2">
                    <i class="fas fa-spinner fa-spin mr-1"></i> {{ __('Saving') }}...

                </span>
            </button>
        </div>
    </div>

    {{-- Confirm Modal --}}
    @include('livewire.partials.orders.modal.confirm', [
        'confirmData' => [
            'receiptNumber' => $order->receipt_number,
            'reviewDateTime' => $order->created_at->locale(app()->getLocale() === 'cn' ? 'zh_CN' : app()->getLocale())->isoFormat('LLLL'),
            'orderType' => $order_type === 'deliver' ? __('Delivery') : __('Walk-In'),
            'paymentLabel' => $payment_type === 'cash' ? __('Cash') : __('GCash / Online'),
            'paymentStatusLabel' => $is_paid ? __('Paid') : __('Unpaid'),
            'statusLabel' => match ($status) {
                'completed' => __('Completed'),
                'pending' => __('Pending'),
                'preparing' => __('Preparing'),
                'in_transit' => __('In transit'),
                'delivered' => __('Delivered'),
                'cancelled' => __('Cancelled'),
                default => ucfirst(str_replace('_', ' ', $status)),
            },
            'deliveredBy' => optional($selectedEmployee)->name,
            'customerName' => optional($selectedCustomer)->name,
            'customerContact' => optional($selectedCustomer)->contact_number,
            'customerUnit' => optional($selectedCustomer)->unit,
            'customerAddress' => optional($selectedCustomer)->address,
            'items' => $orderItems,
            'totalAmount' => $this->editedTotal,
        ],
    ])

    {{-- Full-screen loading overlay for all actions --}}
    @include('livewire.partials.loading-overlay', ['wireTarget' => 'save,selectProduct,selectProductFromDropdown,addOrderItem,removeOrderItem'])
</div>
