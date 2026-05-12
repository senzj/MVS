{{--
    Product Dropdown

    Props: $index (int), $item (array with keys: product_name, stocks, quantity, price, is_free)
           $excludeProductIds (optional array of product IDs to exclude from dropdown)
--}}

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
            const rect        = t.getBoundingClientRect();
            const panelHeight = Math.min((p.scrollHeight || 0), 320);
            const spaceBelow  = window.innerHeight - rect.bottom;
            const spaceAbove  = rect.top;
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
        data-field="orderItems.{{ $index }}.product_id"
        class="cursor-pointer w-full px-3 py-2 border border-zinc-200 dark:border-zinc-600 rounded-lg
               bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
               flex items-center justify-between transition">
        <span class="ml-1 truncate">
            {{ blank($item['product_name'] ?? null) ? __('Select a product') : $item['product_name'] }}
        </span>
        <span class="ml-2 flex items-center gap-2 shrink-0 text-zinc-500">
            @if(isset($item['stocks']))
                <span class="flex items-center gap-1 text-xs font-medium">
                    <i class="fas fa-boxes"></i>{{ $item['stocks'] }}
                </span>
            @endif
            <i class="fas fa-chevron-down text-xs"></i>
        </span>
    </button>

    <div x-show="open"
         x-ref="panel"
         @click.outside="open = false"
         @keydown.escape.window="open = false"
         x-cloak
         :class="dropUp ? 'bottom-full mb-1' : 'top-full mt-1'"
         class="absolute z-30 w-full bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-lg shadow-xl">

        {{-- Search --}}
        <div class="sticky top-0 z-10 p-2 bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-600">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-xs"></i>
                <input type="text"
                    wire:model.live.debounce.250ms="productSearch"
                    placeholder="{{ __('Search products') }}"
                    class="w-full pl-8 pr-3 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-600
                           bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        {{-- List --}}
        <ul class="max-h-60 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-700/50">
            @forelse(($this->filteredProducts ?? $products ?? []) as $product)
                @if(empty($excludeProductIds) || !in_array($product->id, $excludeProductIds))
                    <li wire:key="prod-opt-{{ $product->id }}-{{ $index }}"
                        class="px-3 py-2.5 hover:bg-zinc-50 dark:hover:bg-zinc-700 cursor-pointer transition-colors"
                        wire:click="selectProduct({{ $product->id }}, {{ $index }})"
                        @click="open = false">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-sm text-zinc-900 dark:text-zinc-100 truncate">{{ $product->name }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-mono text-sm font-semibold text-zinc-900 dark:text-zinc-100">₱{{ number_format($product->price, 2) }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    <i class="fas fa-boxes mr-0.5"></i>{{ $product->stocks ?? 0 }}
                                </p>
                            </div>
                        </div>
                    </li>
                @endif
            @empty
                <li class="px-3 py-8 text-center text-zinc-500 dark:text-zinc-400">
                    <i class="fas fa-box-open text-2xl mb-2 block opacity-40"></i>
                    <span class="text-sm">{{ __('No products found.') }}</span>
                </li>
            @endforelse
        </ul>
    </div>
</div>
