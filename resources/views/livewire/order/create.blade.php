@section('title', __('Create Order'))
<div class="w-full max-w-full overflow-hidden px-2 sm:px-4 pb-8"
    x-data="{
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
    <div class="mb-3">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                    <i class="fas fa-plus-circle mr-2"></i>{{ __('Create New Order') }}
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

            <a href="{{ route('orders') }}" class="flex items-center gap-1" wire:navigate>
                <button type="button" class="cursor-pointer inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300 transition dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                    <i class="fas fa-arrow-left"></i>
                    <span>{{ __('Back') }}</span>
                </button>
            </a>
        </div>
    </div>

    {{-- Order Form --}}
    <form wire:submit.prevent="openSaveConfirmation" class="space-y-6">

        {{-- Order Information Card --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="fas fa-file-invoice text-blue-500 mr-2"></i>
                {{ __('Order Information') }}
            </h3>

            {{-- Order Number --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <i class="fas fa-hashtag mr-1"></i>
                    {{ __('Order Number') }}
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
                        <i class="fas fa-route mr-1"></i>
                        {{ __('Order Type') }}
                        <span class="text-gray-500 normal-case font-normal">*</span>
                    </label>
                    <div class="flex items-center space-x-3">
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
                </div>

                {{-- Payment Type --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <i class="fas fa-credit-card mr-1"></i>
                        {{ __('Payment Method') }}
                        <span class="text-red-500 normal-case font-normal">*</span>
                    </label>
                    <select wire:model="paymentType"
                        data-field="paymentType"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 transition">
                        <option value="cash">{{ __('Cash') }}</option>
                        @php
                            $otherPaymentTypes = config('storeconfig.other_payment_types', []);
                        @endphp

                        @foreach($otherPaymentTypes as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Delivery Person (Only for Delivery Orders) --}}
            @if($orderType === 'deliver')
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <i class="fas fa-user-tie mr-1"></i>{{ __('Delivery Person') }}
                    <span class="text-red-500">*</span>
                </label>
                @include('livewire.partials.orders.form.employee.dropdown', [
                    'forceSelect' => false,
                ])
            </div>
            @endif
        </div>

        {{-- Customer Information Card (optional for walk-in orders) --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="fas fa-user text-blue-500 mr-2"></i>{{ __('Customer Information') }}
            </h3>
            @include('livewire.partials.orders.form.customer', ['order_type' => $orderType])
        </div>

        {{-- Order Items Card --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                    <i class="fas fa-shopping-cart text-blue-500 mr-2"></i>{{ __('Order Items') }}
                </h3>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="openProductForm()"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 active:scale-95 transition-all shadow-md shadow-emerald-500/20">
                        <i class="fas fa-box-open"></i>
                        <span>{{ __('Create Product') }}</span>
                    </button>
                    <button type="button" wire:click="addOrderItem"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 active:scale-95 transition-all shadow-md shadow-blue-500/20">
                        <i class="fas fa-plus"></i>
                        <span>{{ __('Add Item') }}</span>
                    </button>
                </div>
            </div>

            {{-- Product Form --}}
            @include('livewire.partials.orders.form.product.create')

            {{-- Order Items --}}
            <div class="space-y-4">
                @foreach($orderItems as $index => $item)
                    @php
                        // Collect product IDs from all other items to exclude from this item's dropdown
                        $excludeIds = [];
                        foreach ($orderItems as $otherIndex => $otherItem) {
                            if ($otherIndex !== $index && !empty($otherItem['product_id'])) {
                                $excludeIds[] = $otherItem['product_id'];
                            }
                        }
                    @endphp
                    @include('livewire.partials.orders.form.itemrow', [
                        'index' => $index,
                        'item' => $item,
                        'count' => count($orderItems),
                        'excludeProductIds' => $excludeIds,
                    ])
                @endforeach
            </div>

            {{-- Total Amount --}}
            <div class="mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-600">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50/80 dark:bg-zinc-900/40 p-4 space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ __('Discount') }}</h4>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Applied before the final total is calculated.') }}</p>
                            </div>
                            <a href="{{ route('settings.discounts') }}" wire:navigate class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                {{ __('Manage Discount presets') }}
                            </a>
                        </div>

                        <select wire:model.live="discountPresetId"
                            class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                                   bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100
                                   focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                            <option value="">{{ __('No Discount') }}</option>
                            @foreach($discountPresets as $preset)
                                <option value="{{ $preset['id'] }}">
                                    {{ $preset['name'] }}
                                    ({{ ucfirst($preset['type']) }}:
                                    {{ $preset['type'] === 'percentage' ? rtrim(rtrim(number_format((float) $preset['value'], 2, '.', ''), '0'), '.') . '%' : '₱' . number_format((float) $preset['value'], 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 space-y-3">
                        <div class="flex justify-between items-center text-sm text-zinc-700 dark:text-zinc-300">
                            <span class="font-semibold uppercase tracking-wide">{{ __('Subtotal') }}</span>
                            <span class="font-bold font-mono text-lg">₱{{ number_format($this->totalAmount, 2) }}</span>
                        </div>

                        @if($discountPresetId && $this->orderDiscountAmount > 0)
                            <div class="flex justify-between items-center text-sm text-zinc-700 dark:text-zinc-300">
                                <span class="font-semibold uppercase tracking-wide">{{ __('Discount') }}</span>
                                <span class="font-bold font-mono text-lg">{{ $this->orderDiscountDisplay }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between items-center border-t border-zinc-200 dark:border-zinc-700 pt-3">
                            <span class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                                <i class="fas fa-receipt mr-2"></i>{{ __('Total Amount') }}:
                            </span>
                            <span class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">₱{{ number_format($this->finalTotal, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex justify-center gap-3 w-full">
            <button type="button" onclick="window.history.back()" class="inline-flex items-center justify-center w-full gap-2 px-4 py-3 rounded-lg bg-zinc-100 dark:bg-zinc-700 text-sm font-semibold text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition">
                <i class="fas fa-times mr-1"></i>{{ __('Cancel') }}
            </button>

            <button type="submit" class="inline-flex items-center justify-center w-full gap-2 px-6 py-4 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 active:scale-95 transition-all shadow-md shadow-blue-500/20 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-save mr-1"></i>{{ __('Create Order') }}
            </button>
        </div>
    </form>

    {{-- Confirm Order Modal --}}
    @include('livewire.partials.orders.modal.order', [
        'confirmData' => $confirmData,
    ])

    @include('livewire.partials.loading-overlay', ['wireTarget' => 'createOrder,createProduct,selectProduct,selectCustomer,selectEmployee,addOrderItem,removeOrderItem,processPayment,openProductForm,closeProductForm,forceSelectEmployee'])
    @include('livewire.partials.form-error-handler')
</div>
