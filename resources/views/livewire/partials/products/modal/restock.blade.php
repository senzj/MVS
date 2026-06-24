@php
    $product  ??= null;
    $subtotal ??= 0;
@endphp

<div x-data="{ isOpen: @entangle('showModal').live }"
     x-show="isOpen"
     x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="display:none;"
     @product-restocked.window="$wire.$refresh()">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/60" @click="$wire.closeModal()"></div>

    {{-- Panel --}}
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="relative w-full max-w-lg bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl overflow-hidden">

        {{-- Header --}}
        <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-900/60 border-b border-zinc-100 dark:border-zinc-700 flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <i class="fas fa-truck-ramp-box text-green-500"></i>
                    {{ __('Product Restock') }}
                </h3>
                @if($product)
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        {{ __('Restocking:') }}
                        <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ $product->name }}</span>
                    </p>
                @endif
            </div>
            <button @click="$wire.closeModal()"
                    class="w-8 h-8 flex items-center justify-center rounded-xl text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form wire:submit.prevent="saveRestock">
            <div class="px-6 py-5 space-y-4">

                {{-- Current stats --}}
                @if($product)
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-xl px-4 py-3">
                            <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">{{ __('Current Stock') }}</p>
                            <p class="text-xl font-bold text-blue-800 dark:text-blue-200 mt-0.5">
                                {{ number_format($product->stocks) }}
                                <span class="text-sm font-normal">{{ __('units') }}</span>
                            </p>
                        </div>
                        <div class="bg-zinc-50 dark:bg-zinc-700/60 border border-zinc-100 dark:border-zinc-600 rounded-xl px-4 py-3">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">{{ __('Avg Unit Cost') }}</p>
                            <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100 mt-0.5">
                                {{ config('storeconfig.currency_symbol') }}{{ number_format($product->cost, 2) }}
                            </p>
                        </div>
                    </div>
                @endif

                {{-- Quantity + Unit Type (combobox) --}}
                <div class="grid grid-cols-3 gap-3">

                    {{-- Quantity --}}
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                            <i class="fas fa-cubes mr-1"></i>{{ __('Restock Quantity') }}
                        </label>
                        <input type="number"
                               wire:model.live="quantity"
                               min="1" step="1"
                               placeholder="0"
                               class="w-full px-3 py-2.5 text-sm rounded-xl border
                                      {{ $errors->has('quantity') ? 'border-red-400 bg-red-50 dark:bg-red-900/20' : 'border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60' }}
                                      text-zinc-900 dark:text-zinc-100
                                      focus:outline-none focus:ring-2 focus:ring-green-500/40 focus:border-green-500 transition">
                        @error('quantity')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Unit type combobox --}}
                    <div x-data="{
                            open: false,
                            unitVal: $wire.entangle('unit_type'),
                            allOptions: @js($unitOptions),

                            get filtered() {
                                const q = (this.unitVal || '').toLowerCase().trim();
                                if (!q) return this.allOptions;
                                return this.allOptions.filter(o => o.toLowerCase().includes(q));
                            },

                            select(opt) {
                                this.unitVal = opt;
                                this.open    = false;
                                this.$refs.unitInput.blur();
                            },

                            onKeydown(e) {
                                if (e.key === 'Escape') { this.open = false; this.$refs.unitInput.blur(); }
                                if (e.key === 'Enter' && this.filtered.length === 1) {
                                    e.preventDefault();
                                    this.select(this.filtered[0]);
                                }
                            },
                        }"
                         @click.outside="open = false"
                         class="relative">

                        <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                            {{ __('Unit') }}
                        </label>

                        {{-- Input --}}
                        <div class="relative">
                            <input type="text"
                                   x-ref="unitInput"
                                   x-model="unitVal"
                                   @focus="open = true"
                                   @input="open = true"
                                   @keydown="onKeydown($event)"
                                   placeholder="{{ __('pcs') }}"
                                   autocomplete="off"
                                   class="w-full px-3 py-2.5 pr-7 text-sm rounded-xl border
                                          {{ $errors->has('unit_type') ? 'border-red-400 bg-red-50 dark:bg-red-900/20' : 'border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60' }}
                                          text-zinc-900 dark:text-zinc-100
                                          focus:outline-none focus:ring-2 focus:ring-green-500/40 focus:border-green-500 transition">

                            {{-- chevron toggle --}}
                            <button type="button"
                                    tabindex="-1"
                                    @click="open = !open; open && $nextTick(() => $refs.unitInput.focus())"
                                    class="absolute inset-y-0 right-2 flex items-center text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors">
                                <i class="fas fa-chevron-down text-[10px]"
                                   :class="open ? 'rotate-180' : ''"
                                   style="transition: transform 0.15s;"></i>
                            </button>
                        </div>

                        {{-- Dropdown --}}
                        <div x-show="open"
                             x-cloak
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-1"
                             class="absolute z-[60] left-0 right-0 mt-1 bg-white dark:bg-zinc-800
                                    border border-zinc-200 dark:border-zinc-600 rounded-xl shadow-lg
                                    overflow-hidden">

                            {{-- Matches --}}
                            <ul class="max-h-44 overflow-y-auto py-1"
                                x-show="filtered.length > 0">
                                <template x-for="opt in filtered" :key="opt">
                                    <li>
                                        <button type="button"
                                                @click="select(opt)"
                                                class="w-full text-left px-3 py-2 text-sm
                                                       text-zinc-700 dark:text-zinc-200
                                                       hover:bg-green-50 dark:hover:bg-green-900/20
                                                       hover:text-green-700 dark:hover:text-green-300
                                                       flex items-center gap-2 transition-colors"
                                                :class="opt === unitVal
                                                    ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 font-semibold'
                                                    : ''">
                                            <i class="fas fa-check text-[10px] opacity-0 shrink-0"
                                               :class="opt === unitVal ? 'opacity-100' : ''"></i>
                                            <span x-text="opt"></span>
                                        </button>
                                    </li>
                                </template>
                            </ul>

                            {{-- No match — show "use custom" hint --}}
                            <div x-show="filtered.length === 0"
                                 class="px-3 py-2.5 text-xs text-zinc-400 dark:text-zinc-500 flex items-center gap-1.5">
                                <i class="fas fa-keyboard"></i>
                                <span>{{ __('Press Enter or keep typing to use a custom unit') }}</span>
                            </div>
                        </div>

                        @error('unit_type')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Unit Cost --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                        <i class="fas fa-tag mr-1"></i>{{ __('Unit Cost (Buying Price)') }}
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-zinc-400 dark:text-zinc-500 text-sm pointer-events-none">
                            {{ config('storeconfig.currency_symbol') }}
                        </span>
                        <input type="number"
                               wire:model.live="unit_cost"
                               min="0" step="0.01"
                               placeholder="0.00"
                               class="w-full pl-7 pr-3 py-2.5 text-sm rounded-xl border
                                      {{ $errors->has('unit_cost') ? 'border-red-400 bg-red-50 dark:bg-red-900/20' : 'border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60' }}
                                      text-zinc-900 dark:text-zinc-100
                                      focus:outline-none focus:ring-2 focus:ring-green-500/40 focus:border-green-500 transition">
                    </div>
                    @error('unit_cost')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Remarks --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                        <i class="fas fa-note-sticky mr-1"></i>{{ __('Remarks / Supplier Notes') }}
                    </label>
                    <textarea wire:model="remarks"
                              rows="2"
                              placeholder="{{ __('e.g. Supplier Batch #42, Invoice XYZ') }}"
                              class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                                     bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                     focus:outline-none focus:ring-2 focus:ring-green-500/40 focus:border-green-500 transition resize-none">
                    </textarea>
                    @error('remarks')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Summary --}}
                <div class="bg-zinc-50 dark:bg-zinc-700/60 rounded-xl border border-zinc-100 dark:border-zinc-600 p-4 space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Units Added') }}</span>
                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $quantity ?: 0 }} {{ $unit_type }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Unit Cost') }}</span>
                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ config('storeconfig.currency_symbol') }}{{ number_format((float)($unit_cost ?? 0), 2) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-sm pt-2 border-t border-zinc-200 dark:border-zinc-600">
                        <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Total Spent') }}</span>
                        <span class="text-lg font-bold text-green-600 dark:text-green-400">
                            {{ config('storeconfig.currency_symbol') }}{{ number_format($subtotal, 2) }}
                        </span>
                    </div>
                    @if($product && ($quantity ?? 0) > 0)
                        @php
                            $currentStocks = (int)   $product->stocks;
                            $currentCost   = (float)  $product->cost;
                            $newQty        = (int)   ($quantity  ?? 0);
                            $newCost       = (float) ($unit_cost ?? 0);
                            $newAvg = ($currentStocks + $newQty) > 0
                                ? (($currentStocks * $currentCost) + ($newQty * $newCost)) / ($currentStocks + $newQty)
                                : $newCost;
                        @endphp
                        <div class="flex items-center justify-between text-xs pt-1 text-zinc-400 dark:text-zinc-500">
                            <span>{{ __('New stock level') }}</span>
                            <span>{{ number_format($currentStocks + $newQty) }} {{ $unit_type }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-zinc-400 dark:text-zinc-500">
                            <span>{{ __('New avg unit cost') }}</span>
                            <span>{{ config('storeconfig.currency_symbol') }}{{ number_format($newAvg, 4) }}</span>
                        </div>
                    @endif
                </div>

            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-900/60 border-t border-zinc-100 dark:border-zinc-700 flex items-center justify-end gap-2">
                <button type="button"
                        @click="$wire.closeModal()"
                        class="cursor-pointer px-4 py-2 text-sm font-medium rounded-xl border border-zinc-200 dark:border-zinc-600
                               text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <i class="fas fa-times mr-1.5"></i>{{ __('Cancel') }}
                </button>
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl
                               bg-green-600 text-white hover:bg-green-700 active:scale-95 transition-all
                               disabled:opacity-60 disabled:cursor-not-allowed">
                    <span wire:loading wire:target="saveRestock">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                    </span>
                    <i class="fas fa-truck-ramp-box" wire:loading.class="hidden" wire:target="saveRestock"></i>
                    <span>{{ __('Save') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
