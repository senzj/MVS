@section('title', __('Create Order'))
<div class="container"
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
                        <option value="gcash">{{ __('GCash') }}</option>
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

        {{-- Customer Information Card (Only for Delivery Orders) --}}
        @if($orderType === 'deliver')
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="fas fa-user text-blue-500 mr-2"></i>{{ __('Customer Information') }}
            </h3>
            @include('livewire.partials.orders.form.customer')
        </div>
        @endif

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
                    <div class="border border-zinc-200 dark:border-zinc-600 rounded-lg p-4">

                        {{-- Item header --}}
                        <div class="flex items-center justify-between gap-3 mb-3">
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

                        {{-- Item fields --}}
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                            {{-- Product --}}
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    {{ __('Product') }}
                                    <span class="text-red-500 normal-case font-normal">*</span>
                                </label>
                                @include('livewire.partials.orders.form.product.dropdown', ['index' => $index, 'item' => $item])
                            </div>

                            {{-- Quantity --}}
                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    {{ __('Quantity / per kilo') }}
                                    <span class="text-red-500 normal-case font-normal">*</span>
                                </label>
                                <input type="number"
                                    wire:model.live.debounce.300ms="orderItems.{{ $index }}.quantity"
                                    data-field="orderItems.{{ $index }}.quantity"
                                    min="1"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 transition">
                            </div>

                            {{-- Unit Price --}}
                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    {{ __('Unit Price') }}
                                    <span class="text-gray-500 normal-case font-normal">*</span>
                                </label>
                                <input type="number"
                                    wire:model.blur="orderItems.{{ $index }}.price"
                                    data-field="orderItems.{{ $index }}.price"
                                    min="0" step="0.01"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 transition">
                            </div>

                            {{-- Total + No Charge --}}
                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Total') }}</label>
                                <div class="px-3 py-2 bg-zinc-100 dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-zinc-100">
                                    ₱{{ number_format((float) ($item['total'] ?? 0), 2) }}
                                </div>

                                {{-- No Charge Toggle --}}
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
                    <span class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">₱{{ number_format($this->totalAmount, 2) }}</span>
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


    {{-- Payment modal for walkin --}}
    @include('livewire.partials.orders.modal.payment')

    {{-- Confirm Order Modal --}}
    @include('livewire.partials.orders.modal.order', [
        'confirmData' => [
            'receiptNumber' => $orderNumber,
            'reviewDateTime' => now()->locale(app()->getLocale())->isoFormat('LLLL'),
            'orderType' => $orderType === 'deliver' ? __('Delivery') : __('Walk-In'),
            'paymentLabel' => $paymentType === 'cash' ? __('Cash') : __('GCash'),
            'paymentStatusLabel' => __('Unpaid'),
            'statusLabel' => $orderType === 'deliver' ? __('Pending') : __('Completed'),
            'deliveredBy' => optional($this->selectedEmployee)->name,
            'customerName' => $customerName,
            'customerContact' => $customerContact,
            'customerUnit' => $customerUnit,
            'customerAddress' => $customerAddress,
            'items' => $orderItems,
            'totalAmount' => $this->totalAmount,
        ],
    ])

    @include('livewire.partials.loading-overlay', ['wireTarget' => 'createOrder,createProduct,selectProduct,selectCustomer,selectEmployee,addOrderItem,removeOrderItem,processPayment,openProductForm,closeProductForm,forceSelectEmployee'])
    @include('livewire.partials.form-error-handler')
</div>
