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

{{-- Order details and Info - for desktop --}}
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
