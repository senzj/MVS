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
    " class="relative">

    <button type="button"
        x-ref="trigger"
        @click="toggle()"
        data-field="orderItems.{{ $index }}.product_id"
        class="cursor-pointer w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 flex items-center justify-between transition">

        <!-- LEFT -->
        <span class="ml-1 truncate">
            {{ blank($item['product_name'] ?? null) ? __('Select a product') : $item['product_name'] }}
        </span>

        <!-- RIGHT -->
        <span class="ml-2 font-semibold flex items-center gap-2 shrink-0">
            @if(isset($item['stocks']))
                <span class="flex items-center gap-1">
                    <i class="fas fa-boxes"></i> {{ $item['stocks'] }}
                </span>
            @endif

            <i class="fas fa-chevron-down"></i>
        </span>
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
                <input type="text" wire:model.live="productSearch" placeholder="{{ __('Search products...') }}"
                    class="w-full pl-9 pr-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <ul class="max-h-60 overflow-y-auto">
            @forelse(($this->filteredProducts ?? $products) as $product)
                <li class="px-3 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-700 cursor-pointer"
                    wire:click="selectProduct({{ $product->id }}, {{ $index }})"
                    @click="open = false">
                    <div class="flex items-start justify-between w-full gap-3">
                        <div class="ml-1 font-semibold truncate">{{ $product->name }}</div>
                        <div class="text-right shrink-0">
                            <div class="font-mono">₱{{ number_format($product->price, 2) }}</div>
                            <div class="text-xs text-zinc-600 dark:text-zinc-400">
                                <i class="fas fa-boxes mr-1"></i>{{ __('Stock') }}: {{ $product->stocks ?? 0 }}
                            </div>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-3 py-2 text-center text-zinc-500 dark:text-zinc-400">
                    <i class="fas fa-box-open mr-2"></i>{{ __('No Available Product. Try to create one.') }}
                </li>
            @endforelse
        </ul>
    </div>
</div>
