@section('title', __('Record Sales'))

<div class="w-full max-w-full overflow-hidden px-2 sm:px-4 pb-8">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between mb-5 gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <i class="fas fa-file-invoice text-green-500"></i>{{ __('Record Sales') }}
            </h2>
            @include('livewire.partials.clock')
        </div>
        <a href="{{ route('orders') }}" wire:navigate
            class="cursor-pointer inline-flex items-center gap-2 px-3 py-2 rounded-lg
                   bg-gray-200 text-gray-800 hover:bg-gray-300 transition
                   dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 text-sm font-medium">
            <i class="fas fa-arrow-left"></i>{{ __('Back') }}
        </a>
    </div>

    <form wire:submit.prevent="openSaveConfirmation" class="space-y-5">

        {{-- Sale Information --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm p-5">
            <h3 class="text-sm font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-4">
                <i class="fas fa-receipt text-blue-500 mr-2"></i>{{ __('Sale Information') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- Order number (read-only) --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <i class="fas fa-hashtag mr-1"></i>{{ __('Order Number') }}
                    </label>
                    <div class="px-3 py-2.5 rounded-lg border border-zinc-200 dark:border-zinc-600
                                bg-zinc-100 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 font-mono text-sm">
                        {{ $receiptNumber }}
                    </div>
                </div>

                {{-- Date & Time --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <i class="fas fa-calendar mr-1"></i>{{ __('Date & Time') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="datetime-local" wire:model="saleDate" data-field="saleDate"
                        class="w-full px-3 py-2.5 text-sm rounded-lg border border-zinc-200 dark:border-zinc-600
                               bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                </div>

                {{-- Order Type toggle --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <i class="fas fa-shopping-bag mr-1"></i>{{ __('Order Type') }}
                    </label>
                    <div class="flex items-center gap-3 px-1 py-1">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer"
                                :checked="$wire.orderType === 'deliver'"
                                @change="$wire.set('orderType', $event.target.checked ? 'deliver' : 'walk_in')">
                            <div class="relative w-16 h-8 bg-orange-400 rounded-full transition-colors duration-300
                                        peer-checked:bg-blue-600
                                        after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                        after:bg-white after:border after:border-gray-300 after:rounded-full
                                        after:h-7 after:w-7 after:transition-all
                                        peer-checked:after:translate-x-8 peer-checked:after:border-white"></div>
                        </label>
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300 flex items-center gap-1">
                            <i :class="$wire.orderType === 'deliver' ? 'fas fa-truck text-blue-500' : 'fas fa-walking text-orange-500'"></i>
                            <span x-text="$wire.orderType === 'deliver' ? '{{ __('Delivery') }}' : '{{ __('Walk-In') }}'"></span>
                        </span>
                    </div>
                </div>

                {{-- Payment Method --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <i class="fas fa-money-bill-wave mr-1"></i>{{ __('Payment Method') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.defer="paymentType" data-field="paymentType"
                        class="w-full px-3 py-2.5 text-sm rounded-lg border border-zinc-200 dark:border-zinc-600
                               bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                        <option value="cash">{{ __('Cash') }}</option>
                        @php
                            $otherPaymentTypes = config('storeconfig.other_payment_types', []);
                        @endphp

                        @foreach($otherPaymentTypes as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Payment Status (replaces old is_paid boolean) --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <i class="fas fa-check-circle mr-1"></i>{{ __('Payment Status') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="paymentStatus" data-field="paymentStatus"
                        class="w-full px-3 py-2.5 text-sm rounded-lg border border-zinc-200 dark:border-zinc-600
                               bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                        <option value="paid">{{ __('Paid') }}</option>
                        <option value="unpaid">{{ __('Unpaid') }}</option>
                        <option value="refunded">{{ __('Refunded') }}</option>
                    </select>
                </div>

                {{-- Order Status --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <i class="fas fa-info-circle mr-1"></i>{{ __('Order Status') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="status" data-field="status"
                        class="w-full px-3 py-2.5 text-sm rounded-lg border border-zinc-200 dark:border-zinc-600
                               bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                        <option value="completed">{{ __('Completed') }}</option>
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="preparing">{{ __('Preparing') }}</option>
                        <option value="in_transit">{{ __('In transit') }}</option>
                        <option value="delivered">{{ __('Delivered') }}</option>
                        <option value="cancelled">{{ __('Cancelled') }}</option>
                    </select>
                </div>

                {{-- Delivery person (delivery only) --}}
                @if($orderType === 'deliver')
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            <i class="fas fa-user-tie mr-1"></i>{{ __('Delivery Person') }}
                            <span class="text-red-500">*</span>
                        </label>
                        @include('livewire.partials.orders.form.employee.dropdown', ['forceSelect' => false])
                    </div>
                @endif
            </div>
        </div>

        {{-- Customer (optional for walk-in orders) --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm p-5">
            <h3 class="text-sm font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-4">
                <i class="fas fa-user text-blue-500 mr-2"></i>{{ __('Customer Information') }}
            </h3>
            @include('livewire.partials.orders.form.customer', ['order_type' => $orderType])
        </div>

        {{-- Order Items --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h3 class="text-sm font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                    <i class="fas fa-shopping-cart text-blue-500 mr-2"></i>{{ __('Order Items') }}
                </h3>
                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="openProductForm()"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-emerald-600 text-white
                               text-sm font-semibold hover:bg-emerald-700 active:scale-95 transition-all shadow-sm">
                        <i class="fas fa-box-open"></i>{{ __('Create Product') }}
                    </button>
                    <button type="button" wire:click="addOrderItem"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-600 text-white
                               text-sm font-semibold hover:bg-blue-700 active:scale-95 transition-all shadow-sm">
                        <i class="fas fa-plus"></i>{{ __('Add Item') }}
                    </button>
                </div>
            </div>

            @include('livewire.partials.orders.form.product.create', [
                'subtitle' => __('The new product will be added to this sales record after it is created.'),
            ])

            <div class="space-y-3">
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
                        'item'  => $item,
                        'count' => count($orderItems),
                        'excludeProductIds' => $excludeIds,
                    ])
                @endforeach
            </div>

            {{-- Proof of payment (all GCash orders) --}}
            @if($paymentType === 'gcash')
                <div class="md:col-span-2">
                    @include('livewire.partials.orders.proof-of-payment', [
                        'existingProofUrl' => null,
                        'paymentType' => $paymentType,
                        'allowCamera' => $orderType === 'walk_in',
                        'readOnly' => false,
                    ])
                </div>
            @endif

            <div class="pt-4 border-t border-zinc-200 dark:border-zinc-600">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50/80 dark:bg-zinc-900/40 p-4 space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ __('Discount Preset') }}</h4>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Applied before the final total is calculated.</p>
                            </div>
                            <a href="{{ route('settings.discounts') }}" wire:navigate class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                Manage presets
                            </a>
                        </div>

                        <select wire:model.live="discountPresetId"
                            class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                                   bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100
                                   focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                            <option value="">No Discount</option>
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
                            <span class="font-semibold uppercase tracking-wide">Subtotal</span>
                            <span class="font-bold font-mono text-lg">₱{{ number_format($this->totalAmount, 2) }}</span>
                        </div>

                        @if($discountPresetId && $this->orderDiscountAmount > 0)
                            <div class="flex items-center justify-between text-sm text-zinc-700 dark:text-zinc-300">
                                <span class="font-semibold uppercase tracking-wide">Order Discount</span>
                                <span class="font-bold font-mono text-lg">{{ $this->orderDiscountDisplay }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between items-center border-t border-zinc-200 dark:border-zinc-700 pt-3">
                            <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wide">
                                {{ __('Total Amount') }}
                            </span>
                            <span class="text-2xl font-black font-mono text-zinc-900 dark:text-zinc-100">
                                ₱{{ number_format($this->finalTotal, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex gap-3">
            <a href="{{ route('orders') }}" wire:navigate
                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl
                       bg-zinc-100 dark:bg-zinc-700 text-sm font-semibold
                       text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition">
                <i class="fas fa-times"></i>{{ __('Cancel') }}
            </a>
            <button type="submit"
                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl
                       bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700
                       active:scale-95 transition-all shadow-md shadow-blue-500/20">
                <i class="fas fa-save mr-1"></i>{{ __('Save Record') }}
            </button>
        </div>
    </form>

    {{-- Universal confirm modal --}}
    @include('livewire.partials.orders.modal.order', [
        'modalMode'   => 'confirm',
        'confirmData' => $confirmData,
    ])

    @include('livewire.partials.loading-overlay', [
        'wireTarget' => 'createOrder,createProduct,selectProduct,selectCustomer,selectEmployee,addOrderItem,removeOrderItem,openProductForm,closeProductForm,saveSalesRecord',
    ])
    @include('livewire.partials.form-error-handler')
</div>
