{{--
    Products are added via the POS grid (addProductToCart), so this
    partial is purely a cart-row: qty stepper, price edit, total, free toggle.

    Props: $index, $item, $count
--}}

@php
    $stocks = (int)($item['stocks'] ?? 0);
    $isLow  = $stocks > 0 && $stocks < 10;
    $isFree = (bool)($item['is_free'] ?? false);
@endphp

<div wire:key="cart-row-{{ $index }}"
     x-data="{
         qty: {{ (int)($item['quantity'] ?? 1) }},
         max: {{ $stocks > 0 ? $stocks : 9999 }},
         increment() {
             if (this.max <= 0 || this.qty < this.max) { this.qty++; this.commit(); }
         },
         decrement() {
             if (this.qty > 1) { this.qty--; this.commit(); }
         },
         commit() {
             if (!this.qty || this.qty < 1) this.qty = 1;
             if (this.max > 0 && this.qty > this.max) this.qty = this.max;
             $wire.set('orderItems.{{ $index }}.quantity', this.qty);
         }
     }"
     x-init="
         $watch(() => $wire.orderItems?.[{{ $index }}]?.quantity, v => {
             if (v != null && v !== '') {
                 const el = document.querySelector('[data-qty-idx=\'{{ $index }}\']');
                 if (!el || document.activeElement !== el) qty = parseInt(v) || 1;
             }
         });
         $watch(() => $wire.orderItems?.[{{ $index }}]?.stocks, v => {
             if (v != null) max = parseInt(v) > 0 ? parseInt(v) : 9999;
         });
     "
     class="group flex items-start gap-3 px-4 py-3
            hover:bg-zinc-50/80 dark:hover:bg-zinc-700/20 transition-colors">

    {{-- Item number --}}
    <span class="shrink-0 mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full
                 bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300
                 text-[10px] font-bold select-none">
        {{ $index + 1 }}
    </span>

    {{-- Content --}}
    <div class="flex-1 min-w-0 space-y-1.5">

        {{-- Product name + remove --}}
        <div class="flex items-start justify-between gap-2">
            <p class="text-sm font-bold text-zinc-900 dark:text-zinc-100 leading-tight truncate">
                {{ $item['product_name'] }}
            </p>
            <button type="button"
                    wire:click="removeOrderItem({{ $index }})"
                    wire:loading.attr="disabled"
                    wire:target="removeOrderItem({{ $index }})"
                    title="{{ __('Remove item') }}"
                    class="shrink-0 w-5 h-5 flex items-center justify-center rounded-full
                        text-zinc-300 dark:text-zinc-600
                        hover:text-red-500 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/20
                        transition-all">
                <i class="fas fa-trash text-xs"></i>
            </button>
        </div>

        {{-- Low stock warning --}}
        @if($isLow)
            <p class="text-[10px] font-semibold text-yellow-600 dark:text-yellow-400 leading-none">
                <i class="fas fa-exclamation-triangle mr-0.5"></i>{{ __('Only :n left', ['n' => $stocks]) }}
            </p>
        @endif

        {{-- Controls: [−][qty][+] × ₱price = ₱total  [free] --}}
        <div class="flex items-center gap-1.5 flex-wrap">

            {{-- Qty stepper --}}
            <div class="flex items-stretch h-7 rounded-lg border border-zinc-200 dark:border-zinc-600 overflow-hidden shrink-0">
                <button type="button" @click="decrement()"
                    :disabled="qty <= 1"
                    class="w-7 flex items-center justify-center
                           bg-zinc-50 dark:bg-zinc-700/80 text-zinc-500 dark:text-zinc-400
                           hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20 dark:hover:text-red-400
                           border-r border-zinc-200 dark:border-zinc-600 transition-colors
                           disabled:opacity-40 disabled:cursor-not-allowed">
                    <i class="fas fa-minus text-[9px]"></i>
                </button>

                <input type="number"
                    x-model.number="qty"
                    @blur="commit()"
                    @keydown.enter.prevent="commit()"
                    @keydown.tab="commit()"
                    data-qty-idx="{{ $index }}"
                    min="1"
                    :max="max > 0 ? max : undefined"
                    class="w-8 text-center text-xs font-bold
                           bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100
                           focus:outline-none focus:ring-1 focus:ring-inset focus:ring-blue-400
                           [appearance:textfield]
                           [&::-webkit-outer-spin-button]:appearance-none
                           [&::-webkit-inner-spin-button]:appearance-none">

                <button type="button" @click="increment()"
                    :disabled="max > 0 && qty >= max"
                    class="w-7 flex items-center justify-center
                           bg-zinc-50 dark:bg-zinc-700/80 text-zinc-500 dark:text-zinc-400
                           hover:bg-blue-50 hover:text-blue-500 dark:hover:bg-blue-900/20 dark:hover:text-blue-400
                           border-l border-zinc-200 dark:border-zinc-600 transition-colors
                           disabled:opacity-40 disabled:cursor-not-allowed">
                    <i class="fas fa-plus text-[9px]"></i>
                </button>
            </div>

            <span class="text-zinc-300 dark:text-zinc-600 text-xs select-none">×</span>

            {{-- Unit price --}}
            <div class="relative shrink-0">
                <span class="absolute left-2 top-1/2 -translate-y-1/2 text-[10px] text-zinc-400 font-semibold pointer-events-none select-none">₱</span>
                <input type="number"
                    wire:model.blur="orderItems.{{ $index }}.price"
                    data-field="orderItems.{{ $index }}.price"
                    min="0" step="0.01"
                    class="h-7 w-[4.5rem] pl-5 pr-1 text-xs font-mono font-bold rounded-lg
                           border border-zinc-200 dark:border-zinc-600
                           bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100
                           focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 transition">
            </div>

            <span class="text-zinc-300 dark:text-zinc-600 text-xs select-none">=</span>

            {{-- Line total --}}
            <span class="text-sm font-black font-mono shrink-0
                {{ $isFree ? 'line-through text-zinc-400 dark:text-zinc-500' : 'text-zinc-900 dark:text-zinc-100' }}">
                ₱{{ number_format($isFree ? 0 : (float)($item['total'] ?? 0), 2) }}
            </span>

            {{-- No-charge toggle --}}
            <label class="inline-flex items-center gap-1 cursor-pointer shrink-0 ml-auto" title="{{ __('No Charge') }}">
                <div class="relative">
                    <input type="checkbox" class="sr-only peer"
                        :checked="$wire.orderItems[{{ $index }}]?.is_free"
                        @change="$wire.set('orderItems.{{ $index }}.is_free', $event.target.checked)">
                    <div class="w-7 h-4 rounded-full transition-colors duration-150
                                bg-zinc-300 dark:bg-zinc-600 peer-checked:bg-green-500
                                after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                after:bg-white after:rounded-full after:h-3 after:w-3
                                after:transition-all peer-checked:after:translate-x-3"></div>
                </div>
                <span class="text-[10px] font-semibold
                    {{ $isFree ? 'text-green-600 dark:text-green-400' : 'text-zinc-400 dark:text-zinc-500' }}">
                    {{ __('Free') }}
                </span>
            </label>
        </div>

        @if($isFree)
            <p class="text-[10px] text-green-600 dark:text-green-400 font-semibold">
                <i class="fas fa-gift mr-0.5"></i>{{ __('Complimentary — no charge') }}
            </p>
        @endif
    </div>
</div>
