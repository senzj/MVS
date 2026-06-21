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
        ? ('-' . config('storeconfig.currency_symbol') . ' ' . number_format($ctDiscount, 2))
        : ($this->orderDiscountDisplay ?? '');
    $hasDiscount = ($discountPresetId ?? null) && $ctDiscount > 0;

    $otherPaymentTypes = config('storeconfig.other_payment_types', []);

    // Wire property for order-type to watch in Alpine
    $wireOrderTypeProp = $pageMode === 'edit' ? 'order_type' : 'orderType';
@endphp


<div class="w-full min-h-screen flex flex-col gap-3 overflow-x-hidden"
    x-data="{
        // ── Badge counters (product_id → qty in cart) ─────────────────
        cartQty: {},

        // ── cart rows data ──────────────────────────────────────
        cartItems: [],

        // ── Server-sync queue + debounce timer ────────────────────────
        pendingQueue: [],
        _flushTimer:  null,
        _qtyTimers:   {},

        // ── Cart header status indicator ─────────────────────────────
        cartStatus:       'idle',
        _cartStatusTimer: null,

        // Used in x-text price strings so the cart matches whatever
        // currency the store is configured for, not a hardcoded symbol.
        currency: '{{ config('storeconfig.currency_symbol', '₱') }}',

        addToCart(product) {
            const pid      = String(product.id);
            const maxStock = parseInt(product.stocks) || 0;

            const curBadge = parseInt(this.cartQty[pid]) || 0;
            if (maxStock <= 0 || curBadge < maxStock) {
                this.cartQty[pid] = curBadge + 1;
            }

            const existing = this.cartItems.find(i => String(i.product_id) === pid);
            if (existing) {
                if (maxStock <= 0 || existing.quantity < maxStock) {
                    existing.quantity++;
                    this.recalcTotal(existing);
                }
            } else {
                this.cartItems.push({
                    product_id:    product.id,
                    product_name:  product.name,
                    product_image: product.image,
                    price:         parseFloat(product.price) || 0,
                    quantity:      1,
                    stocks:        maxStock,
                    discount:      0,
                    total:         parseFloat(product.price) || 0,
                    is_free:       false,
                    wireIndex:     null,
                    pending:       true,
                });
            }

            this.pendingQueue.push(product.id);
            clearTimeout(this._flushTimer);
            this.cartStatus  = 'loading';
            this._flushTimer = setTimeout(() => this.flushQueue(), 350);
        },

        async flushQueue() {
            if (!this.pendingQueue.length) return;
            const ids      = [...this.pendingQueue];
            this.pendingQueue = [];
            this.cartStatus  = 'loading';

            try {
                await $wire.addProductsToCart(ids);
                this.cartStatus = 'success';
                clearTimeout(this._cartStatusTimer);
                this._cartStatusTimer = setTimeout(() => { this.cartStatus = 'idle'; }, 900);
            } catch (err) {
                this.cartStatus = 'failed';
                clearTimeout(this._cartStatusTimer);
                this._cartStatusTimer = setTimeout(() => { this.cartStatus = 'idle'; }, 2500);
                console.error('Cart flush failed:', err);
            }
        },

        syncFromWire() {
            const wireItems = $wire.orderItems || [];

            const map = {};
            wireItems.forEach(item => {
                if (item.product_id) {
                    const pid  = String(item.product_id);
                    map[pid]   = (map[pid] || 0) + (parseInt(item.quantity) || 1);
                }
            });
            this.cartQty = map;

            const synced = wireItems
                .map((item, index) => {
                    if (!item.product_id) return null;
                    const pid   = String(item.product_id);
                    const local = this.cartItems.find(i => String(i.product_id) === pid);
                    return {
                        product_id: item.product_id,
                        product_name: item.product_name || local?.product_name || '',
                        product_image: item.product_image || local?.product_image || '',
                        price: parseFloat(item.price)    || 0,
                        quantity: parseInt(item.quantity)   || 1,
                        stocks: parseInt(item.stocks)     || (local?.stocks ?? 0),
                        discount: parseFloat(item.discount) || 0,
                        total: parseFloat(item.total)    || 0,
                        is_free: Boolean(item.is_free),
                        wireIndex: index,
                        pending: false,
                    };
                })
                .filter(Boolean);

                const syncedIds    = synced.map(i => String(i.product_id));
                const stillPending = this.cartItems.filter(
                i => i.pending && !syncedIds.includes(String(i.product_id))
            );

            this.cartItems = [...synced, ...stillPending];
        },

        removeCartItem(item) {
            this.cartItems = this.cartItems.filter(i => i.product_id !== item.product_id);
            delete this.cartQty[String(item.product_id)];

            if (item.wireIndex !== null) {
                $wire.removeOrderItem(item.wireIndex);
            }
        },

        recalcTotal(item) {
            if (item.is_free) {
                item.total = 0;
                return;
            }
            const subtotal = item.quantity * item.price;
            item.discount  = Math.min(Math.max(0, parseFloat(item.discount) || 0), subtotal);
            item.total     = Math.max(0, subtotal - item.discount);
        },

        incrementQty(item) {
            const max = parseInt(item.stocks) || 0;
            if (max <= 0 || item.quantity < max) {
                item.quantity++;
                this.recalcTotal(item);
                this.cartQty[String(item.product_id)] = item.quantity;
                this._debounceSyncQty(item);
            }
        },

        decrementQty(item) {
            if (item.quantity > 1) {
                item.quantity--;
                this.recalcTotal(item);
                this.cartQty[String(item.product_id)] = item.quantity;
                this._debounceSyncQty(item);
            }
        },

        manualQtyUpdate(item) {
            let qty = parseInt(item.quantity) || 1;
            qty = Math.max(1, qty);
            if (item.stocks > 0) qty = Math.min(qty, item.stocks);
            item.quantity = qty;
            this.recalcTotal(item);
            this.cartQty[String(item.product_id)] = qty;
            this._debounceSyncQty(item);
        },

        _debounceSyncQty(item) {
            const pid = String(item.product_id);
            clearTimeout(this._qtyTimers[pid]);
            this._qtyTimers[pid] = setTimeout(() => {
                if (item.wireIndex !== null) {
                    $wire.set('orderItems.' + item.wireIndex + '.quantity', item.quantity);
                }
            }, 500);
        },

        updatePrice(item) {
            item.price = Math.max(0, parseFloat(item.price) || 0);
            this.recalcTotal(item);
            if (item.wireIndex !== null) {
                $wire.set('orderItems.' + item.wireIndex + '.price', item.price);
            }
        },

        // NEW — was referenced by the markup but never defined.
        updateDiscount(item) {
            this.recalcTotal(item);
            if (item.wireIndex !== null) {
                $wire.set('orderItems.' + item.wireIndex + '.discount', item.discount);
            }
        },

        updateFree(item) {
            this.recalcTotal(item);
            if (item.wireIndex !== null) {
                $wire.set('orderItems.' + item.wireIndex + '.is_free', item.is_free);
            }
        },

        get itemCount() {
            return this.cartItems.filter(i => i.product_id).length;
        },
    }"
    x-init="
        syncFromWire();
        $watch(() => JSON.stringify($wire.orderItems), () => syncFromWire());
    "
    @add-to-cart="addToCart($event.detail)"
    >

    {{-- 2 Column Layout --}}
    <div class="w-full h-full grid grid-cols-1 lg:grid-cols-[1fr_380px] xl:grid-cols-[1fr_420px] gap-3">

        {{-- Column 1: Customer + Product Grid --}}
        <div class="flex flex-col gap-1 min-w-0">
            {{-- CUSTOMER --}}
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm overflow-visible"
                    x-data="{ open: {{ $ctOrderType === 'deliver' ? 'true' : 'false' }} }"
                    x-init="$watch(() => $wire.{{ $wireOrderTypeProp }}, v => { if (v === 'deliver') open = true; })">

                <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between px-4 py-3 text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider
                            hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">
                    <span>
                        <i class="fas fa-user text-blue-500 mr-1.5"></i>{{ __('Customer') }}
                        @if($ctOrderType !== 'deliver')
                            <span class="normal-case font-normal text-zinc-400 ml-1">({{ __('optional') }})</span>
                        @endif
                    </span>
                    <i :class="open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="text-zinc-400 text-xs"></i>
                </button>

                <div x-show="open" x-cloak
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="p-2 border-t border-zinc-100 dark:border-zinc-700">
                    @include('livewire.partials.orders.form.customer', ['order_type' => $ctOrderType])
                </div>
            </div>

            {{-- PRODUCT GRID --}}
            <div class="lg:col-span-2 min-w-0 space-y-3">
                @if($pageMode !== 'edit')
                    @include('livewire.partials.orders.form.product.create', [
                        'subtitle' => __('The new product will appear here after it is created.'),
                    ])
                @endif

                @include('livewire.partials.orders.products.grid', ['pageMode' => $pageMode])
            </div>

        </div>

        {{-- Column 2: Order Details + Summary + Actions --}}
        <div class="flex flex-col gap-1 min-w-0 lg:sticky lg:overflow-y-auto scrollbar-thin scrollbar-thumb-zinc-300 dark:scrollbar-thumb-zinc-700/80 scrollbar-track-transparent">

            {{-- ORDER DETAILS --}}
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm overflow-visible">

                <div class="flex items-center gap-2 px-4 py-3 border-b border-zinc-100 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-900/20">
                    <i class="fas fa-file-invoice text-blue-500 text-sm"></i>
                    <h3 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                        @if($pageMode === 'create')
                            {{ __('Order Details') }}

                        @elseif($pageMode === 'record')
                            {{ __('Sale Details') }}

                        @else
                            <div class="flex items-baseline gap-1">
                                <p class="font-bold">
                                    {{ __('Order #') }}
                                </p>
                                <span class="font-mono font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{"#" . $order->id ?? '' }}
                                </span>
                            </div>
                        @endif
                    </h3>
                </div>

                <div class="p-4 space-y-3">

                    {{-- ─── CREATE ──────────────────────── --}}
                    @if($pageMode === 'create')

                        <div class="flex items-center justify-between text-xs">

                            {{-- Order Number Label --}}
                            <span class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">
                                {{ __('Order #') }}
                            </span>

                            {{-- Order Number Display --}}
                            <span class="font-mono font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $orderNumber ?? '' }}
                            </span>
                        </div>

                        {{-- Order Type and Payment Method --}}
                        <div class="flex items-center w-full col-span-1 lg:col-span-2 gap-3">

                            {{-- Order Type Label --}}
                            <div class="flex-1">
                                <p class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1.5">
                                    {{ __('Order Type') }}
                                </p>
                                <div class="flex items-center gap-3">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer"
                                            :checked="$wire.orderType === 'deliver'"
                                            @change="$wire.set('orderType', $event.target.checked ? 'deliver' : 'walk_in')">
                                        <div class="relative w-14 h-7 bg-orange-400 rounded-full transition-colors duration-200 peer-checked:bg-blue-600
                                                    after:content-[''] after:absolute after:top-0.5 after:left-0.5
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

                            {{-- Payment Method --}}
                            <div class="flex-1">
                                <p class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1.5">
                                    {{ __('Payment Method') }}
                                </p>
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

                        </div>

                    {{-- ─── RECORD SALES ─────────────────── --}}
                    @elseif($pageMode === 'record')

                        <div class="flex items-center justify-between text-xs">
                            <span class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">
                                {{ __('Order #') }}
                            </span>
                            <span class="font-mono font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $receiptNumber ?? '' }}
                            </span>
                        </div>

                        {{-- Date & Time --}}
                        <div>
                            <p class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1">
                                {{ __('Date & Time') }} <span class="text-red-500 font-normal">*</span>
                            </p>
                            <input type="datetime-local" wire:model="saleDate"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                                        bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                        focus:outline-none focus:ring-2 focus:ring-blue-500/40 transition">
                        </div>

                        {{-- Order Type + Payment Method --}}
                        <div class="grid grid-cols-2 gap-2 mb-1">

                            {{-- Order Type --}}
                            <div>

                                <p class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">
                                    {{ __('Order Type') }}
                                </p>

                                <div class="flex items-center gap-3">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer"
                                            :checked="$wire.orderType === 'deliver'"
                                            @change="$wire.set('orderType', $event.target.checked ? 'deliver' : 'walk_in')">
                                        <div class="relative w-14 h-7 bg-orange-400 rounded-full transition-colors duration-200 peer-checked:bg-blue-600
                                                    after:content-[''] after:absolute after:top-0.5 after:left-0.5
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

                            {{-- Payment Method --}}
                            <div>
                                <p class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1.5">
                                    {{ __('Payment Method') }}
                                </p>
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

                        </div>

                        {{-- Payment Status + Order Status --}}
                        <div class="grid grid-cols-2 gap-2 mb-1">

                            {{-- Payment Status --}}
                            <div>
                                <p class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">
                                    {{ __('Payment Status') }}
                                </p>
                                <select wire:model="paymentStatus"
                                    class="w-full px-2.5 py-2 text-xs rounded-xl border border-zinc-200 dark:border-zinc-600
                                            bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                            focus:outline-none focus:ring-2 focus:ring-blue-500/40 transition">
                                    <option value="paid">{{ __('Paid') }}</option>
                                    <option value="unpaid">{{ __('Unpaid') }}</option>
                                    <option value="refunded">{{ __('Refunded') }}</option>
                                </select>
                            </div>

                            {{-- Order Status --}}
                            <div>
                                <p class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">
                                    {{ __('Order Status') }}
                                </p>
                                <select wire:model="status"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-zinc-200 dark:border-zinc-600
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
                        </div>

                    {{-- ─── EDIT ─────────────────────────── --}}
                    @else

                        <div class="grid grid-cols-2 gap-x-3 gap-y-1 text-xs mb-2">

                            {{-- Receipt --}}
                            <div>
                                <p class="text-zinc-400 dark:text-zinc-500">
                                    {{ __('Order #') }}
                                </p>
                                <p class="font-mono font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $order->receipt_number ?? '' }}
                                </p>
                            </div>

                            {{-- Date --}}
                            <div>
                                <p class="text-zinc-400 dark:text-zinc-500">{{ __('Date') }}</p>
                                @php $loc = app()->getLocale() === 'cn' ? 'zh_CN' : app()->getLocale(); @endphp
                                <p class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ isset($order) ? $order->created_at->locale($loc)->isoFormat('MMM D, YYYY') : '' }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">

                            {{-- Order Status --}}
                            <div>
                                <p class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1.5">
                                    {{ __('Order Status') }}
                                </p>
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

                            {{-- Order Type --}}
                            <div>
                                <p class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1.5">
                                    {{ __('Order Type') }}
                                </p>
                                <select wire:model.defer="order_type"
                                    class="w-full px-2.5 py-2 text-xs rounded-xl border border-zinc-200 dark:border-zinc-600
                                            bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                            focus:outline-none focus:ring-2 focus:ring-blue-500/40 transition">
                                    <option value="deliver">{{ __('Delivery') }}</option>
                                    <option value="walk_in">{{ __('Walk-In') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">

                            {{-- Payment Type --}}
                            <div>
                                <p class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1.5">
                                    {{ __('Payment Method') }}
                                </p>
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

                            {{-- Payment Status --}}
                            <div>
                                <p class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1.5">
                                    {{ __('Payment Status') }}
                                </p>
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
                            <p class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1.5">
                                <i class="fas fa-user-tie mr-1"></i>{{ __('Delivery Person') }}
                                <span class="text-red-500 font-normal">*</span>
                            </p>
                            @include('livewire.partials.orders.form.employee.dropdown', ['forceSelect' => false])
                        </div>
                    @endif
                </div>
            </div>

            {{-- SUMMARY + ACTIONS --}}
            <div class="lg:col-span-1 min-w-0 space-y-3">
                {{-- CART --}}
                <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm overflow-hidden">

                    {{-- CART HEADER --}}
                    <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-100 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-900/20">
                        <h3 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            <i class="fas fa-shopping-cart text-blue-500 mr-1.5"></i>{{ __('Cart') }}
                        </h3>

                        <div class="items-center gap-2 flex">
                            {{-- Server-sync status indicator --}}
                            <div x-show="cartStatus !== 'idle'" x-cloak
                                class="flex items-center w-3.5"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-75"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-75">
                                <i x-show="cartStatus === 'loading'" class="fas fa-spinner fa-spin text-blue-500 text-xs"></i>
                                <i x-show="cartStatus === 'success'" class="fas fa-check text-green-500 text-xs"></i>
                                <i x-show="cartStatus === 'failed'"  class="fas fa-times text-red-500 text-xs"></i>
                            </div>

                            {{-- Item count --}}
                            <span x-show="itemCount > 0" x-cloak
                                class="text-xs font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
                                x-text="itemCount + ' ' + (itemCount === 1 ? '{{ __('item') }}' : '{{ __('items') }}')">
                            </span>
                        </div>
                    </div>

                    {{-- CART ROWS --}}
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50 overflow-y-auto h-[52vh] scrollbar-thin scrollbar-thumb-zinc-300 dark:scrollbar-thumb-zinc-700/80 scrollbar-track-transparent">

                        {{-- Empty state --}}
                        <template x-if="cartItems.filter(i => i.product_id).length === 0">
                            <div class="h-full flex flex-col items-center justify-center text-zinc-400 dark:text-zinc-500 text-center">
                                <i class="fas fa-shopping-cart text-3xl mb-2 opacity-20"></i>
                                <p class="text-sm font-medium">{{ __('Cart is empty.') }}</p>
                                <p class="text-xs mt-0.5 opacity-70">{{ __('Select or create a product to add to the order.') }}</p>
                            </div>
                        </template>

                        {{-- Cart items --}}
                        <template x-for="item in cartItems.filter(i => i.product_id)" :key="String(item.product_id)">
                            <div class="p-3 flex flex-col gap-2.5 transition-colors"
                                :class="item.pending
                                    ? 'bg-blue-50/40 dark:bg-blue-900/10'
                                    : 'hover:bg-zinc-50/80 dark:hover:bg-zinc-700/20'"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0 scale-95">

                                {{-- Row 1: Thumbnail + Name + Remove --}}
                                <div class="flex items-start gap-2.5">

                                    {{-- Thumbnail --}}
                                    <div class="w-11 h-11 shrink-0 rounded-lg overflow-hidden
                                                bg-zinc-100 dark:bg-zinc-700 ring-1 ring-zinc-200/70 dark:ring-zinc-700">
                                        <template x-if="item.product_image">
                                            <img :src="item.product_image" :alt="item.product_name"
                                                class="w-full h-full object-cover" loading="lazy">
                                        </template>
                                        <template x-if="!item.product_image">
                                            <div class="w-full h-full flex items-center justify-center">
                                                <i class="fas fa-box text-zinc-300 dark:text-zinc-500 text-sm"></i>
                                            </div>
                                        </template>
                                    </div>

                                    {{-- Name + low stock --}}
                                    <div class="flex-1 min-w-0 pt-0.5">
                                        <p class="text-sm font-bold text-zinc-900 dark:text-zinc-100 leading-snug truncate"
                                            x-text="item.product_name"></p>
                                        <template x-if="!item.pending && item.stocks > 0 && item.stocks < 10">
                                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-yellow-700 dark:text-yellow-400">
                                                <i class="fas fa-triangle-exclamation"></i>
                                                <span x-text="`{{ __('Only') }} ${item.stocks} {{ __('left') }}`"></span>
                                            </span>
                                        </template>
                                    </div>

                                    {{-- Remove button --}}
                                    <button type="button" @click="removeCartItem(item)"
                                            :disabled="item.pending"
                                            title="{{ __('Remove item') }}"
                                            class="shrink-0 w-7 h-7 flex items-center justify-center rounded-lg
                                                text-zinc-300 dark:text-zinc-600
                                                hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20 dark:hover:text-red-400
                                                disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                        <i class="fas fa-trash-can text-xs"></i>
                                    </button>
                                </div>

                                {{-- Row 2: Qty × Price − Discount --}}
                                <div class="flex items-end gap-2 flex-wrap">

                                    {{-- Qty stepper --}}
                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-bold text-zinc-400 uppercase tracking-wider">
                                            {{ __('Qty / KG') }}
                                        </label>
                                        <div class="inline-flex items-stretch h-8 rounded-lg border border-zinc-200 dark:border-zinc-600 overflow-hidden">
                                            <button type="button" @click="decrementQty(item)"
                                                    :disabled="item.quantity <= 1 || item.pending"
                                                    class="w-8 flex items-center justify-center text-xs
                                                        bg-zinc-50 dark:bg-zinc-700/80 text-zinc-500
                                                        hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20 dark:hover:text-red-400
                                                        border-r border-zinc-200 dark:border-zinc-600 transition-colors
                                                        disabled:opacity-40 disabled:cursor-not-allowed">
                                                <i class="fas fa-minus text-xs"></i>
                                            </button>
                                            <input type="number"
                                                x-model.number="item.quantity"
                                                @blur="manualQtyUpdate(item)"
                                                @keydown.enter.prevent="manualQtyUpdate(item)"
                                                @keydown.tab="manualQtyUpdate(item)"
                                                :disabled="item.pending"
                                                min="1" :max="item.stocks > 0 ? item.stocks : undefined"
                                                class="w-10 text-center text-sm font-bold
                                                        bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100
                                                        focus:outline-none focus:ring-1 focus:ring-inset focus:ring-blue-400
                                                        disabled:opacity-50 [appearance:textfield]
                                                        [&::-webkit-outer-spin-button]:appearance-none
                                                        [&::-webkit-inner-spin-button]:appearance-none">
                                            <button type="button" @click="incrementQty(item)"
                                                    :disabled="(item.stocks > 0 && item.quantity >= item.stocks) || item.pending"
                                                    class="w-8 flex items-center justify-center text-xs
                                                        bg-zinc-50 dark:bg-zinc-700/80 text-zinc-500
                                                        hover:bg-blue-50 hover:text-blue-500 dark:hover:bg-blue-900/20 dark:hover:text-blue-400
                                                        border-l border-zinc-200 dark:border-zinc-600 transition-colors
                                                        disabled:opacity-40 disabled:cursor-not-allowed">
                                                <i class="fas fa-plus text-xs"></i>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- × --}}
                                    <span class="text-zinc-300 dark:text-zinc-600 text-sm pb-1.5 select-none">×</span>

                                    {{-- Unit price --}}
                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-bold text-zinc-400 uppercase tracking-wider">{{ __('Price') }}</label>
                                        <div class="relative">
                                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-xs text-zinc-400 font-semibold pointer-events-none select-none">
                                                {{ config('storeconfig.currency_symbol') }}
                                            </span>
                                            <input type="number"
                                                x-model.number="item.price"
                                                @blur="updatePrice(item)"
                                                @keydown.enter.prevent="updatePrice(item)"
                                                :disabled="item.pending"
                                                min="0" step="0.01"
                                                class="h-8 w-24 pl-5 pr-2 text-sm font-mono font-bold rounded-lg
                                                        border border-zinc-200 dark:border-zinc-600
                                                        bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100
                                                        focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 transition
                                                        disabled:opacity-50 [appearance:textfield]
                                                        [&::-webkit-outer-spin-button]:appearance-none
                                                        [&::-webkit-inner-spin-button]:appearance-none">
                                        </div>
                                    </div>

                                    {{-- − --}}
                                    <span class="text-zinc-300 dark:text-zinc-600 text-sm pb-1.5 select-none">−</span>

                                    {{-- Discount --}}
                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-bold text-zinc-400 uppercase tracking-wider">
                                            {{ __('Discount') }}
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-xs text-zinc-400 font-semibold pointer-events-none select-none">
                                                {{ config('storeconfig.currency_symbol') }}
                                            </span>

                                            <input type="number"
                                                x-model.number="item.discount"
                                                @blur="updateDiscount(item)"
                                                @keydown.enter.prevent="updateDiscount(item)"
                                                :disabled="item.pending || item.is_free"
                                                min="0" step="0.01" :max="item.quantity * item.price"
                                                class="h-8 w-24 pl-5 pr-2 text-sm font-mono font-bold rounded-lg
                                                        border border-zinc-200 dark:border-zinc-600
                                                        bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100
                                                        focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-400 transition
                                                        disabled:opacity-50 [appearance:textfield]
                                                        [&::-webkit-outer-spin-button]:appearance-none
                                                        [&::-webkit-inner-spin-button]:appearance-none">
                                        </div>
                                    </div>
                                </div>

                                {{-- Row 3: Total (left) + No Charge toggle (right) — always bottom, never overlapping --}}
                                <div class="flex items-center justify-between gap-2 pt-1
                                            border-t border-zinc-100 dark:border-zinc-700/60">

                                    {{-- Total --}}
                                    <div class="flex items-baseline gap-1.5">
                                        <span class="text-base font-black font-mono"
                                            :class="item.is_free
                                                ? 'line-through text-zinc-400 dark:text-zinc-500'
                                                : 'text-zinc-900 dark:text-zinc-100'"
                                            x-text="currency + (item.total || 0).toFixed(2)">
                                        </span>

                                        <template x-if="item.is_free">
                                            <span class="text-xs text-green-600 dark:text-green-400 font-semibold">
                                                <i class="fas fa-gift mr-0.5"></i>{{ __('Complimentary') }}
                                            </span>
                                        </template>

                                        <template x-if="!item.is_free && item.discount > 0">
                                            <span class="text-xs font-mono text-zinc-400 dark:text-zinc-500 line-through"
                                                x-text="currency + (item.quantity * item.price).toFixed(2)"></span>
                                        </template>

                                        <template x-if="!item.is_free && item.discount > 0">
                                            <span class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold">
                                                <i class="fas fa-tag mr-0.5"></i>
                                                <span x-text="'-' + currency + item.discount.toFixed(2)"></span>
                                            </span>
                                        </template>
                                    </div>

                                    {{-- No Charge toggle — pinned to the right --}}
                                    <label class="inline-flex items-center gap-1.5 cursor-pointer shrink-0" title="{{ __('No Charge') }}">
                                        <div class="relative">
                                            <input type="checkbox" class="sr-only peer"
                                                x-model="item.is_free" @change="updateFree(item)" :disabled="item.pending">
                                            <div class="w-7 h-4 rounded-full transition-colors duration-150
                                                        bg-zinc-300 dark:bg-zinc-600 peer-checked:bg-green-500
                                                        after:content-[''] after:absolute after:top-0.5 after:left-0.5
                                                        after:bg-white after:rounded-full after:h-3 after:w-3
                                                        after:transition-all peer-checked:after:translate-x-3"></div>
                                        </div>
                                        <span class="text-xs font-semibold"
                                            :class="item.is_free
                                                ? 'text-green-600 dark:text-green-400'
                                                : 'text-zinc-400 dark:text-zinc-500'">
                                            {{ __('No Charge') }}
                                        </span>
                                    </label>
                                </div>

                            </div>
                        </template>
                    </div>
                </div>

                {{-- FOOTER --}}
                <div class="mt-4 gap-2 space-y-2">

                    {{-- DISCOUNT + TOTALS --}}
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm overflow-hidden">

                        <div class="px-4 pt-3 pb-3 border-b border-zinc-100 dark:border-zinc-700 space-y-2">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">
                                    <i class="fas fa-tag mr-1"></i>{{ __('Discount') }}
                                </p>
                                <a href="{{ route('settings.discounts') }}" wire:navigate
                                    class="text-xs text-blue-500 hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                                    {{ __('Manage discount presets') }}
                                </a>
                            </div>
                            <select wire:model{{ $pageMode !== 'edit' ? '.live' : '' }}="discountPresetId"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                                    bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                    focus:outline-none focus:ring-2 focus:ring-blue-500/40 transition">
                                <option value="">{{ __('No discount applied') }}</option>
                                @foreach($discountPresets as $preset)
                                    <option value="{{ $preset['id'] }}">
                                        {{ $preset['name'] }}
                                        ({{ ucfirst($preset['type']) }}:
                                        {{ $preset['type'] === 'percentage'
                                            ? rtrim(rtrim(number_format((float)$preset['value'], 2, '.', ''), '0'), '.') . '%'
                                            : config('storeconfig.currency_symbol') . number_format((float)$preset['value'], 2) }})
                                        @if(!($preset['is_active'] ?? true)) — {{ __('Inactive') }} @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="px-4 py-3 space-y-1.5">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-zinc-500 dark:text-zinc-400 font-semibold">{{ __('Subtotal') }}</span>
                                <span class="font-bold font-mono text-zinc-800 dark:text-zinc-200">{{ config('storeconfig.currency_symbol') }}{{ number_format($ctSubtotal, 2) }}</span>
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
                                    {{ config('storeconfig.currency_symbol') }}{{ number_format($ctTotal, 2) }}
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
                                    <i class="fas fa-spinner fa-spin"></i>{{ __('Saving') }}
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
    </div>
</div>
