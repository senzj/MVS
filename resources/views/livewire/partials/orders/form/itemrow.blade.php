{{--
    Order Item Row  (fixed)
    =======================
    Props: $index, $item, $count, $excludeProductIds (optional)

--}}

<div wire:key="order-item-row-{{ $index }}"
     class="rounded-2xl border border-zinc-200 dark:border-zinc-700 p-4 space-y-3 bg-white dark:bg-zinc-800">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-2 min-w-0">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full
                         bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300
                         text-xs font-bold shrink-0">
                {{ $index + 1 }}
            </span>
            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 truncate">
                {{ $item['product_name'] ?: __('Select a product') }}
            </p>
        </div>

        @if($count > 1)
            <button type="button"
                wire:click="removeOrderItem({{ $index }})"
                class="shrink-0 text-xs font-semibold text-red-500 hover:text-red-600 transition">
                <i class="fas fa-times mr-1"></i>Remove
            </button>
        @endif
    </div>

    {{-- Fields --}}
    <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-start">

        {{-- Product dropdown --}}
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                {{ __('Product Name') }} <span class="text-red-500">*</span>
            </label>
            @include('livewire.partials.orders.form.product.dropdown', [
                'index' => $index,
                'item' => $item,
                'excludeProductIds' => $excludeProductIds ?? []
            ])
        </div>

        {{--
            Quantity — Alpine local buffer prevents the reset-on-keystroke bug.

            How it works:
              1. Alpine holds the value in `qty` — never touches the DOM
                 with a Livewire update while the user is typing.
              2. `commit()` fires on blur and Enter: validates > 0,
                 then calls $wire.set() once with the final clean value.
              3. $wire.set triggers updatedOrderItems on the server, which
                 clamps to stock and recalculates the total — no race.

            If Livewire resets the component (e.g. after removeOrderItem),
            `x-init` keeps the Alpine qty in sync with the new server value.
        --}}
        <div class="md:col-span-1"
             x-data="{
                qty: {{ (int)($item['quantity'] ?? 1) }},
                max: {{ (int)($item['stocks'] ?? 9999) }},
                commit() {
                    if (!this.qty || this.qty < 1) this.qty = 1;
                    if (this.max > 0 && this.qty > this.max) this.qty = this.max;
                    $wire.set('orderItems.{{ $index }}.quantity', this.qty);
                }
             }"
             x-init="
                $watch(() => $wire.orderItems[{{ $index }}].quantity, v => {
                    if (v !== null && v !== undefined && v !== '' && !document.activeElement?.matches('input[data-qty-idx=\'{{ $index }}\']')) {
                        qty = parseInt(v) || 1;
                    }
                });
             ">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                {{ __('Qty / kg ') }}
                <span class="text-red-500">*</span>
            </label>
            <input type="number"
                x-model.number="qty"
                @blur="commit()"
                @keydown.enter.prevent="commit()"
                @keydown.tab="commit()"
                data-qty-idx="{{ $index }}"
                data-field="orderItems.{{ $index }}.quantity"
                min="1"
                :max="max > 0 ? max : undefined"
                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg
                       bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100
                       focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
        </div>

        {{-- Unit price --}}
        <div class="md:col-span-1">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                {{ __('Unit Price') }}
                <span class="text-gray-500">*</span>
            </label>
            <input type="number"
                wire:model.blur="orderItems.{{ $index }}.price"
                data-field="orderItems.{{ $index }}.price"
                min="0" step="0.01"
                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg
                       bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 font-mono
                       focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
        </div>

        {{-- Total + No Charge --}}
        <div class="md:col-span-1">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                {{ __('Total') }}
            </label>
            <div class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg
                       bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 font-mono">
                ₱{{ number_format($item['is_free'] ? 0 : (float)($item['total'] ?? 0), 2) }}
            </div>

            {{-- No Charge toggle --}}
            <div class="mt-3 flex items-center gap-2">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input
                        type="checkbox"
                        class="sr-only peer"
                        :checked="$wire.orderItems[{{ $index }}]?.is_free"
                        @change="$wire.set('orderItems.{{ $index }}.is_free', $event.target.checked)"
                    >

                    <div class="relative w-11 h-6 bg-zinc-400 rounded-full
                                peer peer-checked:bg-green-600
                                after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                after:bg-white after:border after:border-zinc-500 after:rounded-full
                                after:h-5 after:w-5 after:transition-all
                                peer-checked:after:translate-x-5 peer-checked:after:border-white
                                transition-colors duration-200">
                    </div>
                </label>

                <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">
                    {{ __('No Charge') }}
                </span>
            </div>
        </div>
    </div>
</div>
