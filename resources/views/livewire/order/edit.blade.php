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
                        <i class="fas fa-circle-dot mr-1"></i>
                        {{ __('Status') }}
                        <span class="text-red-500 normal-case font-normal">*</span>
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
                        <i class="fas fa-route mr-1"></i>
                        {{ __('Order Type') }}
                        <span class="text-red-500 normal-case font-normal">*</span>
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
                        <i class="fas fa-credit-card mr-1"></i>
                        {{ __('Payment Method') }}
                        <span class="text-red-500 normal-case font-normal">*</span>
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
                    <div>
                        <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">
                            {{ __('Payment Status') }}
                            <span class="text-red-500 normal-case font-normal">*</span>
                        </p>

                        <div class="flex flex-col items-center">
                            <p class="text-sm font-semibold mb-2"
                                :class="$wire.is_paid ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                {{ $is_paid ? __('Paid') : __('Unpaid') }}
                            </p>

                            <label class="inline-flex items-center gap-3 cursor-pointer select-none group">
                                <div class="relative">
                                    <input type="checkbox" wire:model="is_paid" class="sr-only peer">
                                    <div class="w-12 h-6 rounded-full bg-zinc-300 dark:bg-zinc-600 peer-checked:bg-green-500 transition-colors"></div>
                                    <div class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-6"></div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Delivery Person --}}
                @if ($order_type === 'deliver')
                    <div class="mt-4 col-span-1 sm:col-span-4">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            <i class="fas fa-user-tie mr-1"></i>
                            {{ __('Delivery Person') }}
                            <span class="text-red-500 normal-case font-normal">*</span>
                        </label>

                        <div x-data="{
                                open: false,
                                dropUp: false,
                                toggle() { this.open = !this.open; if (this.open) this.$nextTick(() => this.reposition()); },
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
                            x-init="window.addEventListener('resize', () => open && reposition()); window.addEventListener('scroll', () => open && reposition(), true);"
                            class="relative">

                            <button type="button"
                                    x-ref="trigger"
                                    @click="toggle()"
                                    data-field="selectedEmployeeId"
                                    class="cursor-pointer w-full px-3 py-2 border border-zinc-200 dark:border-zinc-600 rounded-lg bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 flex items-center justify-between transition">
                                <span class="truncate">{{ optional($selectedEmployee)->name ?? __('Select delivery person') }}</span>
                                <i class="fas fa-chevron-down ml-2 text-sm"></i>
                            </button>

                            <div x-show="open" x-ref="panel" @click.outside="open = false" @keydown.escape.window="open = false" x-cloak
                                :class="dropUp ? 'bottom-full mb-1' : 'top-full mt-1'"
                                class="absolute z-20 w-full bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-lg shadow-lg">
                                <div class="sticky top-0 z-10 p-2 bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-600">
                                    <input type="text" wire:model.live.debounce.300ms="employeeSearch"
                                        placeholder="{{ __('Search delivery person...') }}"
                                        class="w-full pl-3 pr-9 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                                </div>
                                <ul class="max-h-80 overflow-y-auto p-2">
                                    @forelse($this->filteredEmployees as $employee)
                                        @php $isInTransit = $this->isEmployeeInTransit($employee->id); @endphp
                                        <li class="mb-1 last:mb-0">
                                            <button type="button" @click="$wire.selectEmployee({{ $employee->id }}); open = false;"
                                                class="w-full text-left px-3 py-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition flex items-center gap-2">
                                                <span class="truncate">{{ $employee->name }}</span>
                                                @if($isInTransit)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">{{ __('In Transit') }}</span>
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

                        @if($selectedEmployee)
                            @php $selectedIsInTransit = $this->isEmployeeInTransit($selectedEmployee->id); @endphp
                            <p class="text-sm mt-2 flex items-center">
                                <i class="fas fa-check mr-1 text-green-600 dark:text-green-400"></i>
                                <span class="text-green-600 dark:text-green-400">{{ __('Selected') }}: {{ $selectedEmployee->name }}</span>
                                @if($selectedIsInTransit)
                                    <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                        <i class="fas fa-shipping-fast mr-1"></i>{{ __('In Transit') }}
                                    </span>
                                @endif
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- CUSTOMER --}}
        @if($order_type === 'deliver')
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-5 space-y-3">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="fas fa-user text-blue-500 mr-2"></i>{{ __('Customer Information') }}
            </h3>

            {{-- customer dropdown and search --}}
            @include('livewire.partials.orders.form.customer')
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
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                        {{ __('Product') }}
                                        <span class="text-red-500 normal-case font-normal">*</span>
                                    </label>
                                    @include('livewire.partials.orders.form.products', ['index' => $index, 'item' => $item])
                                </div>

                                {{-- Quantity --}}
                                <div class="md:col-span-1">
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                        {{ __('Quantity / per kilo') }}
                                        <span class="text-red-500 normal-case font-normal">*</span>
                                    </label>
                                    <input type="number"
                                        min="1"
                                        wire:model.live="orderItems.{{ $index }}.quantity"
                                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 transition">
                                </div>

                                {{-- Unit Price --}}
                                <div class="md:col-span-1">
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                        {{ __('Unit Price') }}
                                        <span class="text-gray-500 normal-case font-normal">*</span>
                                    </label>
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
