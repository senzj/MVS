{{--
    Required prop : $pageMode = 'create' | 'record' | 'edit'

    Usage
    -----
    Create (inside <form wire:submit.prevent="openSaveConfirmation">):
        @include('livewire.partials.orders.layout.order', ['pageMode' => 'create'])

    Record Sales (inside <form wire:submit.prevent="openSaveConfirmation">):
        @include('livewire.partials.orders.layout.order', ['pageMode' => 'record'])

    Edit (no form wrapper):
        @include('livewire.partials.orders.layout.order', ['pageMode' => 'edit'])
--}}

@php
    $pageMode ??= 'create';

    // Normalise property names – Edit uses snake_case, the other two use camelCase
    $ctOrderType     = $pageMode === 'edit' ? ($order_type     ?? 'walk_in') : ($orderType     ?? 'walk_in');
    $ctPaymentType   = $pageMode === 'edit' ? ($payment_type   ?? 'cash')    : ($paymentType   ?? 'cash');
    $ctPaymentStatus = match($pageMode) {
        'edit'   => $payment_status ?? 'unpaid',
        'record' => $paymentStatus  ?? 'paid',
        default  => 'paid',
    };

    // Totals
    $ctSubtotal  = $pageMode === 'edit' ? ($this->editedTotal          ?? 0) : ($this->totalAmount ?? 0);
    $ctTotal     = $pageMode === 'edit' ? ($this->discountedEditedTotal ?? 0) : ($this->finalTotal  ?? 0);
    $ctDiscount  = $this->orderDiscountAmount ?? 0;
    $ctDiscDisp  = $pageMode === 'edit'
        ? ('-₱' . number_format($ctDiscount, 2))
        : ($this->orderDiscountDisplay ?? '');
    $hasDiscount = ($discountPresetId ?? null) && $ctDiscount > 0;

    // Cart item count (filled rows only)
    $filledItems = array_values(array_filter($orderItems ?? [], fn($i) => !empty($i['product_id'])));
    $itemCount   = count($filledItems);

    $otherPaymentTypes = config('storeconfig.other_payment_types', []);

    // Wire property for order-type to watch in Alpine
    $wireOrderTypeProp = $pageMode === 'edit' ? 'order_type' : 'orderType';
@endphp


<div class="w-full min-h-screen flex flex-col gap-3 overflow-x-hidden"
     x-data="{
         cartQty: {},
         refreshCart() {
             const items = $wire.orderItems || [];
             const map   = {};
             items.forEach(item => {
                 if (item.product_id)
                     map[String(item.product_id)] = (map[String(item.product_id)] || 0) + (parseInt(item.quantity) || 1);
             });
             this.cartQty = map;
         }
     }"
     x-init="
         refreshCart();
         $watch(() => JSON.stringify($wire.orderItems), () => refreshCart());
     ">

    {{-- ═════ Row 1: Order details and Customer info ═════ --}}
    <div class="w-full sticky top-0 z-20">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">

            {{-- ORDER DETAILS --}}
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm overflow-visible">

                <div class="flex items-center gap-2 px-4 py-3 border-b border-zinc-100 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-900/20">
                    <i class="fas fa-file-invoice text-blue-500 text-sm"></i>
                    <h3 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                        @if($pageMode === 'create')  {{ __('Order Details') }}
                        @elseif($pageMode === 'record') {{ __('Sale Details') }}
                        @else {{ __('Order') }} #{{ $order->id ?? '' }}
                        @endif
                    </h3>
                </div>

                <div class="p-4 space-y-3">

                    {{-- ─── CREATE ──────────────────────── --}}
                    @if($pageMode === 'create')

                        <div class="flex items-center justify-between text-xs">
                            <span class="text-zinc-400 dark:text-zinc-500">{{ __('Order #') }}</span>
                            <span class="font-mono font-semibold text-zinc-900 dark:text-zinc-100">{{ $orderNumber ?? '' }}</span>
                        </div>

                        {{-- Order type toggle --}}
                        <div>
                            <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1.5">{{ __('Order Type') }}</p>
                            <div class="flex items-center gap-3">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer"
                                        :checked="$wire.orderType === 'deliver'"
                                        @change="$wire.set('orderType', $event.target.checked ? 'deliver' : 'walk_in')">
                                    <div class="relative w-14 h-7 bg-orange-400 rounded-full transition-colors duration-200 peer-checked:bg-blue-600
                                                after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                                after:bg-white after:border after:border-gray-300 after:rounded-full
                                                after:h-6 after:w-6 after:transition-all
                                                peer-checked:after:translate-x-7 peer-checked:after:border-white"></div>
                                </label>
                                <span class="text-sm font-semibold flex items-center gap-1.5 text-zinc-700 dark:text-zinc-300">
                                    <i :class="$wire.orderType === 'deliver' ? 'fas fa-truck text-blue-500' : 'fas fa-walking text-orange-500'"></i>
                                    <span x-text="$wire.orderType === 'deliver' ? '{{ __('Delivery') }}' : '{{ __('Walk-In') }}'"></span>
                                </span>
                            </div>
                        </div>

                        {{-- Payment method --}}
                        <div>
                            <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1.5">{{ __('Payment Method') }}</p>
                            <select wire:model="paymentType"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                                        bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                        focus:outline-none focus:ring-2 focus:ring-blue-500/40 transition">
                                <option value="cash">{{ __('Cash') }}</option>
                                @foreach($otherPaymentTypes as $type)
                                    <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                        </div>

                    {{-- ─── RECORD SALES ─────────────────── --}}
                    @elseif($pageMode === 'record')

                        <div class="flex items-center justify-between text-xs">
                            <span class="text-zinc-400 dark:text-zinc-500">{{ __('Receipt #') }}</span>
                            <span class="font-mono font-semibold text-zinc-900 dark:text-zinc-100">{{ $receiptNumber ?? '' }}</span>
                        </div>

                        {{-- Date & time --}}
                        <div>
                            <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1.5">
                                {{ __('Date & Time') }} <span class="text-red-500 font-normal">*</span>
                            </p>
                            <input type="datetime-local" wire:model="saleDate"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                                        bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                        focus:outline-none focus:ring-2 focus:ring-blue-500/40 transition">
                        </div>

                        {{-- Order type toggle --}}
                        <div>
                            <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1.5">{{ __('Order Type') }}</p>
                            <div class="flex items-center gap-3">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer"
                                        :checked="$wire.orderType === 'deliver'"
                                        @change="$wire.set('orderType', $event.target.checked ? 'deliver' : 'walk_in')">
                                    <div class="relative w-14 h-7 bg-orange-400 rounded-full transition-colors duration-200 peer-checked:bg-blue-600
                                                after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                                after:bg-white after:border after:border-gray-300 after:rounded-full
                                                after:h-6 after:w-6 after:transition-all
                                                peer-checked:after:translate-x-7 peer-checked:after:border-white"></div>
                                </label>
                                <span class="text-sm font-semibold flex items-center gap-1.5 text-zinc-700 dark:text-zinc-300">
                                    <i :class="$wire.orderType === 'deliver' ? 'fas fa-truck text-blue-500' : 'fas fa-walking text-orange-500'"></i>
                                    <span x-text="$wire.orderType === 'deliver' ? '{{ __('Delivery') }}' : '{{ __('Walk-In') }}'"></span>
                                </span>
                            </div>
                        </div>

                        {{-- 2-col: payment method + status --}}
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1.5">{{ __('Payment') }}</p>
                                <select wire:model.defer="paymentType"
                                    class="w-full px-2.5 py-2 text-xs rounded-xl border border-zinc-200 dark:border-zinc-600
                                            bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                            focus:outline-none focus:ring-2 focus:ring-blue-500/40 transition">
                                    <option value="cash">{{ __('Cash') }}</option>
                                    @foreach($otherPaymentTypes as $type)
                                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1.5">{{ __('Pay Status') }}</p>
                                <select wire:model="paymentStatus"
                                    class="w-full px-2.5 py-2 text-xs rounded-xl border border-zinc-200 dark:border-zinc-600
                                            bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                            focus:outline-none focus:ring-2 focus:ring-blue-500/40 transition">
                                    <option value="paid">{{ __('Paid') }}</option>
                                    <option value="unpaid">{{ __('Unpaid') }}</option>
                                    <option value="refunded">{{ __('Refunded') }}</option>
                                </select>
                            </div>
                        </div>

                        {{-- Order status --}}
                        <div>
                            <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1.5">{{ __('Order Status') }}</p>
                            <select wire:model="status"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                                        bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                        focus:outline-none focus:ring-2 focus:ring-blue-500/40 transition">
                                <option value="completed">{{ __('Completed') }}</option>
                                <option value="pending">{{ __('Pending') }}</option>
                                <option value="preparing">{{ __('Preparing') }}</option>
                                <option value="in_transit">{{ __('In Transit') }}</option>
                                <option value="delivered">{{ __('Delivered') }}</option>
                                <option value="cancelled">{{ __('Cancelled') }}</option>
                            </select>
                        </div>

                    {{-- ─── EDIT ─────────────────────────── --}}
                    @else

                        <div class="grid grid-cols-2 gap-x-3 gap-y-1 text-xs mb-2">
                            <div>
                                <p class="text-zinc-400 dark:text-zinc-500">{{ __('Receipt') }}</p>
                                <p class="font-mono font-semibold text-zinc-900 dark:text-zinc-100">{{ $order->receipt_number ?? '' }}</p>
                            </div>
                            <div>
                                <p class="text-zinc-400 dark:text-zinc-500">{{ __('Date') }}</p>
                                @php $loc = app()->getLocale() === 'cn' ? 'zh_CN' : app()->getLocale(); @endphp
                                <p class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ isset($order) ? $order->created_at->locale($loc)->isoFormat('MMM D, YYYY') : '' }}
                                </p>
                            </div>
                        </div>

                        {{-- 2-col: status + order type --}}
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1.5">{{ __('Status') }}</p>
                                <select wire:model="status"
                                    class="w-full px-2.5 py-2 text-xs rounded-xl border border-zinc-200 dark:border-zinc-600
                                            bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                            focus:outline-none focus:ring-2 focus:ring-blue-500/40 transition">
                                    <option value="pending">{{ __('Pending') }}</option>
                                    <option value="preparing">{{ __('Preparing') }}</option>
                                    <option value="in_transit">{{ __('In Transit') }}</option>
                                    <option value="delivered">{{ __('Delivered') }}</option>
                                    <option value="completed">{{ __('Completed') }}</option>
                                    <option value="cancelled">{{ __('Cancelled') }}</option>
                                </select>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1.5">{{ __('Type') }}</p>
                                <select wire:model.defer="order_type"
                                    class="w-full px-2.5 py-2 text-xs rounded-xl border border-zinc-200 dark:border-zinc-600
                                            bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                            focus:outline-none focus:ring-2 focus:ring-blue-500/40 transition">
                                    <option value="deliver">{{ __('Delivery') }}</option>
                                    <option value="walk_in">{{ __('Walk-In') }}</option>
                                </select>
                            </div>
                        </div>

                        {{-- 2-col: payment method + payment status --}}
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1.5">{{ __('Payment') }}</p>
                                <select wire:model.defer="payment_type"
                                    class="w-full px-2.5 py-2 text-xs rounded-xl border border-zinc-200 dark:border-zinc-600
                                            bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                            focus:outline-none focus:ring-2 focus:ring-blue-500/40 transition">
                                    <option value="cash">{{ __('Cash') }}</option>
                                    @foreach($otherPaymentTypes as $type)
                                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1.5">{{ __('Pay Status') }}</p>
                                <select wire:model="payment_status"
                                    class="w-full px-2.5 py-2 text-xs rounded-xl border border-zinc-200 dark:border-zinc-600
                                            bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                            focus:outline-none focus:ring-2 focus:ring-blue-500/40 transition">
                                    <option value="unpaid">{{ __('Unpaid') }}</option>
                                    <option value="paid">{{ __('Paid') }}</option>
                                    <option value="refunded">{{ __('Refunded') }}</option>
                                </select>
                                <div class="mt-1.5">
                                    @include('livewire.partials.orders.status.payment-badge', ['status' => $payment_status ?? 'unpaid'])
                                </div>
                            </div>
                        </div>

                    @endif

                    {{-- ─── DELIVERY PERSON (all modes, when deliver) ──── --}}
                    @if($ctOrderType === 'deliver')
                        <div class="pt-2 border-t border-zinc-100 dark:border-zinc-700">
                            <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1.5">
                                <i class="fas fa-user-tie mr-1"></i>{{ __('Delivery Person') }}
                                <span class="text-red-500 font-normal">*</span>
                            </p>
                            @include('livewire.partials.orders.form.employee.dropdown', ['forceSelect' => false])
                        </div>
                    @endif
                </div>
            </div>

            {{-- CUSTOMER --}}
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm overflow-visible"
                    x-data="{ open: {{ $ctOrderType === 'deliver' ? 'true' : 'false' }} }"
                    x-init="$watch(() => $wire.{{ $wireOrderTypeProp }}, v => { if (v === 'deliver') open = true; })">

                <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between px-4 py-3 text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider
                            hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">
                    <span>
                        <i class="fas fa-user text-blue-500 mr-1.5"></i>{{ __('Customer Info') }}
                        @if($ctOrderType !== 'deliver')
                            <span class="normal-case font-normal text-zinc-400 ml-1">({{ __('optional') }})</span>
                        @endif
                    </span>
                    <i :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="text-zinc-400 text-[10px]"></i>
                </button>

                <div x-show="open" x-cloak
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="px-4 pb-4 border-t border-zinc-100 dark:border-zinc-700 pt-3">
                    @include('livewire.partials.orders.form.customer', ['order_type' => $ctOrderType])
                </div>
            </div>

        </div>
    </div>

    {{-- ═════ Row 2: Products selection + Cart + Proof of Payment + Discount + Totals ═════ --}}
    <div class="w-full grid grid-cols-1 lg:grid-cols-3 gap-3 items-start">

        {{-- Left: Products Grid Selection --}}
        <div class="lg:col-span-2 min-w-0 space-y-3">
            {{-- Inline "Create Product" form (not available in Edit) --}}
            @if($pageMode !== 'edit')
                @include('livewire.partials.orders.form.product.create', [
                    'subtitle' => __('The new product will appear here after it is created.'),
                ])
            @endif

            {{-- POS product grid --}}
            @include('livewire.partials.orders.products.grid', ['pageMode' => $pageMode])
        </div>

        {{-- Right: Summary --}}
        <div class="lg:col-span-1 min-w-0 space-y-3 lg:sticky lg:top-24">
            {{-- CART --}}
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm overflow-hidden">

                <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-100 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-900/20">
                    <h3 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                        <i class="fas fa-shopping-cart text-blue-500 mr-1.5"></i>{{ __('Cart') }}
                    </h3>
                    @if($itemCount > 0)
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                            {{ $itemCount }} {{ $itemCount === 1 ? __('item') : __('items') }}
                        </span>
                    @endif
                </div>

                <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50
                            {{ $itemCount > 4 ? 'max-h-64 overflow-y-auto' : '' }}">
                    @forelse($filledItems as $cartIndex => $cartItem)
                        @php
                            // Find original index in orderItems for Livewire wire:model paths
                            $origIndex = array_search($cartItem, $orderItems);
                            if ($origIndex === false) $origIndex = $cartIndex;
                        @endphp
                        @include('livewire.partials.orders.form.itemrow', [
                            'index' => $origIndex,
                            'item'  => $cartItem,
                            'count' => $itemCount,
                        ])
                    @empty
                        <div class="py-10 flex flex-col items-center text-zinc-400 dark:text-zinc-500">
                            <i class="fas fa-shopping-cart text-3xl mb-2 opacity-20"></i>
                            <p class="text-sm font-medium">{{ __('Cart is empty') }}</p>
                            <p class="text-xs mt-0.5 opacity-70">{{ __('Click a product to add it') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- FOOTER --}}
            <div class="mt-4 gap-2 space-y-2">
                {{-- DISCOUNT + TOTALS --}}
                <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm overflow-hidden">

                    <div class="px-4 pt-3 pb-3 border-b border-zinc-100 dark:border-zinc-700 space-y-2">
                        <div class="flex items-center justify-between">
                            <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">
                                <i class="fas fa-tag mr-1"></i>{{ __('Discount') }}
                            </p>
                            <a href="{{ route('settings.discounts') }}" wire:navigate
                                class="text-[10px] text-blue-500 hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                                {{ __('Manage presets') }}
                            </a>
                        </div>
                        <select wire:model{{ $pageMode !== 'edit' ? '.live' : '' }}="discountPresetId"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                                bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                focus:outline-none focus:ring-2 focus:ring-blue-500/40 transition">
                            <option value="">{{ __('No Discount') }}</option>
                            @foreach($discountPresets as $preset)
                                <option value="{{ $preset['id'] }}">
                                    {{ $preset['name'] }}
                                    ({{ ucfirst($preset['type']) }}:
                                    {{ $preset['type'] === 'percentage'
                                        ? rtrim(rtrim(number_format((float)$preset['value'], 2, '.', ''), '0'), '.') . '%'
                                        : '₱' . number_format((float)$preset['value'], 2) }})
                                    @if(!($preset['is_active'] ?? true)) — {{ __('Inactive') }} @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="px-4 py-3 space-y-1.5">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-zinc-500 dark:text-zinc-400 font-semibold">{{ __('Subtotal') }}</span>
                            <span class="font-bold font-mono text-zinc-800 dark:text-zinc-200">₱{{ number_format($ctSubtotal, 2) }}</span>
                        </div>

                        @if($hasDiscount)
                            <div class="flex justify-between items-center text-sm text-green-600 dark:text-green-400">
                                <span class="font-semibold">{{ __('Discount') }}</span>
                                <span class="font-bold font-mono">{{ $ctDiscDisp }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between items-center pt-2 border-t border-zinc-100 dark:border-zinc-700">
                            <span class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                                <i class="fas fa-receipt mr-1.5 opacity-70"></i>{{ __('Total') }}
                            </span>
                            <span class="text-2xl font-black font-mono text-zinc-900 dark:text-zinc-100">
                                ₱{{ number_format($ctTotal, 2) }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- ── ACTION BUTTONS ───────────────────────── --}}
                <div class="flex gap-2 pb-4">
                    {{-- Cancel --}}
                    @if($pageMode === 'edit')
                        <button type="button" wire:click="cancel"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl
                                bg-zinc-100 dark:bg-zinc-700 text-sm font-semibold text-zinc-700 dark:text-zinc-200
                                hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                            <i class="fas fa-times"></i>{{ __('Cancel') }}
                        </button>
                    @else
                        <a href="{{ route('orders') }}" wire:navigate
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl
                                bg-zinc-100 dark:bg-zinc-700 text-sm font-semibold text-zinc-700 dark:text-zinc-200
                                hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                            <i class="fas fa-times"></i>{{ __('Cancel') }}
                        </a>
                    @endif

                    {{-- Submit --}}
                    @if($pageMode === 'edit')
                        <button type="button"
                            wire:click="openSaveConfirmation"
                            wire:loading.attr="disabled"
                            wire:target="openSaveConfirmation"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl
                                bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700
                                active:scale-95 transition-all shadow-md shadow-blue-500/20 disabled:opacity-60">
                            <span wire:loading.remove wire:target="openSaveConfirmation">
                                <i class="fas fa-save mr-1"></i>{{ __('Save Changes') }}
                            </span>
                            <span wire:loading wire:target="openSaveConfirmation" class="flex items-center gap-2">
                                <i class="fas fa-spinner fa-spin"></i>{{ __('Saving…') }}
                            </span>
                        </button>
                    @elseif($pageMode === 'record')
                        <button type="submit"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl
                                bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700
                                active:scale-95 transition-all shadow-md shadow-blue-500/20">
                            <i class="fas fa-save mr-1"></i>{{ __('Save Record') }}
                        </button>
                    @else
                        <button type="submit"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl
                                bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700
                                active:scale-95 transition-all shadow-md shadow-blue-500/20">
                            <i class="fas fa-save mr-1"></i>{{ __('Create Order') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- PROOF OF PAYMENT --}}
    @php
        $showProof = ($pageMode === 'record' && $ctPaymentType === 'gcash')
                    || ($pageMode === 'edit'   && ($ctPaymentType !== 'cash' || !empty($existingProof ?? null)));
    @endphp
    @if($showProof)
        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-4 space-y-3">
            @if($pageMode === 'edit' && ($this->showQr ?? false))
                @php $qrImage = \App\Helpers\PaymentImageHelper::getPaymentImageUrl(); @endphp
                @if($qrImage)
                    <div class="flex flex-col items-center gap-2 p-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/40">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Scan to pay') }}</p>
                        <img src="{{ $qrImage }}" alt="{{ __('GCash QR') }}" class="max-w-[140px] max-h-[140px] object-contain rounded-lg">
                    </div>
                @endif
            @endif

            @include('livewire.partials.orders.proof-of-payment', [
                'existingProofUrl' => $pageMode === 'edit' ? (isset($existingProof) && $existingProof ? asset('storage/'.$existingProof) : null) : null,
                'paymentType'      => $ctPaymentType,
                'allowCamera'      => $ctOrderType === 'walk_in',
                'readOnly'         => false,
            ])
        </div>
    @endif
</div>
