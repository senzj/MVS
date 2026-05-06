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
                    <span>{{ __('Back to Dashboard') }}</span>
                </button>
            </a>
        </div>
    </div>

    {{-- Order Form --}}
    <form wire:submit.prevent="openSaveConfirmation" class="space-y-6">

        {{-- Order Information Card --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                <i class="fas fa-file-invoice text-blue-500 mr-2"></i>{{ __('Order Information') }}
            </h3>

            {{-- Order Number --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <i class="fas fa-hashtag mr-1"></i>{{ __('Order Number') }}
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
                        <i class="fas fa-route mr-1"></i>{{ __('Order Type') }}
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
                        <i class="fas fa-credit-card mr-1"></i>{{ __('Payment Method') }}
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
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <i class="fas fa-user-tie mr-1"></i>{{ __('Delivery Person') }}
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
                            data-field="selectedEmployeeId"
                            class="cursor-pointer w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 flex items-center justify-between transition">
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
                        class="absolute z-20 w-full bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-lg shadow-lg">

                        <div class="sticky top-0 z-10 p-2 bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-600">
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400"></i>
                                <input type="text"
                                    wire:model.live="employeeSearch"
                                    placeholder="{{ __('Search delivery person...') }}"
                                    class="w-full pl-9 pr-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <ul class="max-h-80 overflow-y-auto p-2">
                            @forelse(($this->filteredEmployees ?? $employees) as $employee)
                                @php $isInTransit = $this->isEmployeeInTransit($employee->id); @endphp
                                <li class="mb-2 last:mb-0">
                                    <div x-data="{ inTransit: {{ $isInTransit ? 'true' : 'false' }}, employeeId: {{ $employee->id }}, employeeName: @js($employee->name) }"
                                        @click="
                                            const tmpl = @js(__('Delivery Person :name is currently delivering. Assign anyway?'));
                                            if (inTransit) {
                                                if (confirm(tmpl.replace(':name', employeeName))) {
                                                    $wire.forceSelectEmployee(employeeId);
                                                    open = false;
                                                }
                                            } else {
                                                $wire.selectEmployee(employeeId);
                                                open = false;
                                            }
                                        "
                                        class="p-3 border border-zinc-200 dark:border-zinc-600 rounded-lg cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700 {{ $isInTransit ? 'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800' : '' }}">

                                        <div class="font-medium text-zinc-900 dark:text-zinc-100 flex items-center justify-between">
                                            <span>
                                                <i class="fas fa-user-tie mr-1"></i>{{ $employee->name }}
                                            </span>
                                            @if($isInTransit)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                                    <i class="fas fa-shipping-fast mr-1"></i>{{ __('In transit') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="p-6 text-center text-zinc-500 dark:text-zinc-400">
                                    <i class="fas fa-user-slash mr-2"></i>{{ __('No Available Delivery Person. Try to create one.') }}
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                @if($this->selectedEmployee)
                    @php $selectedIsInTransit = $this->isEmployeeInTransit($this->selectedEmployee->id); @endphp
                    <p class="text-sm mt-1 flex items-center">
                        <i class="fas fa-check mr-1 text-green-600 dark:text-green-400"></i>
                        <span class="text-green-600 dark:text-green-400">{{ __('Selected') }}: {{ $this->selectedEmployee->name }}</span>
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
            @if($showProductForm)
                <div class="mb-5 rounded-lg border border-blue-200 dark:border-blue-900/40 bg-blue-50/70 dark:bg-blue-900/10 p-4">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <div>
                            <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Create Product') }}</h4>
                        </div>
                        <button type="button" wire:click="closeProductForm" class="text-xs font-semibold text-red-500 hover:text-red-600 transition">{{ __('Cancel') }}</button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Product Name') }}</label>
                            <input type="text" wire:model="productName" class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Category') }}</label>
                            <select wire:model="productCategory" class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                                @foreach(\App\Models\Product::getCategories() as $key => $category)
                                    <option value="{{ $key }}">{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Price') }}</label>
                            <input type="number" step="0.01" min="0" wire:model="productPrice" class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Stock Quantity') }}</label>
                            <input type="number" min="0" wire:model="productStocks" class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Description') }}</label>
                            <textarea wire:model="productDescription" rows="3" class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end mt-4">
                        <button type="button" wire:click="createProduct" wire:loading.attr="disabled" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                            <i class="fas fa-save"></i>
                            <span>{{ __('Create Product') }}</span>
                        </button>
                    </div>
                </div>
            @endif

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
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Product') }}</label>
                                @include('livewire.partials.orders.form.products', ['index' => $index, 'item' => $item])
                            </div>

                            {{-- Quantity --}}
                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Quantity / per kilo') }}</label>
                                <input type="number"
                                    wire:model.live="orderItems.{{ $index }}.quantity"
                                    data-field="orderItems.{{ $index }}.quantity"
                                    min="1"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 transition">
                            </div>

                            {{-- Unit Price --}}
                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Unit Price') }}</label>
                                <input type="number"
                                    wire:model.live="orderItems.{{ $index }}.price"
                                    data-field="orderItems.{{ $index }}.price"
                                    min="0" step="0.01"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 transition">
                            </div>

                            {{-- Total + No Charge --}}
                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Total') }}</label>
                                <div class="px-3 py-2 bg-zinc-100 dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-zinc-100">
                                    ₱{{ number_format($item['is_free'] ? 0 : (($item['quantity'] ?? 0) * ($item['price'] ?? 0)), 2) }}
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
    @include('livewire.partials.orders.modal.confirm', [
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
