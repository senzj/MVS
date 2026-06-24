<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 lg:hidden"
    wire:loading.class="opacity-50 pointer-events-none"
    wire:target="categoryFilter,stockFilter,search,sortByField"
    wire:key="mobile-products-{{ $categoryFilter }}-{{ $stockFilter }}-{{ $search }}">

@forelse($products as $product)
    @php
        $isOutOfStock = empty($product->stocks) || (int)$product->stocks === 0;
        $catLabel = $categoryNames[$product->category] ?? __('Uncategorized');

        // Same status → color mapping drives the mobile top strip
        // AND the desktop inset accent, so both read identically.
        $statusColor = $isOutOfStock ? '#f87171'
            : ($product->stock_status === 'low_stock' ? '#fbbf24'
            : ($product->is_in_stock ? '#22c55e' : '#fb923c'));
        $strip = $isOutOfStock ? 'bg-red-400'
            : ($product->stock_status === 'low_stock' ? 'bg-yellow-400'
            : ($product->is_in_stock ? 'bg-green-500' : 'bg-orange-400'));
        $productColor = $product->color ?? null;
    @endphp

    <div wire:key="mobile-card-{{ $product->id }}"
        class="rounded-2xl border shadow-sm overflow-hidden transition-all hover:brightness-95 bg-gray-200 dark:bg-zinc-800
            {{ $isOutOfStock
                ? 'border-red-200 dark:border-red-900/50'
                : 'border-zinc-100 dark:border-zinc-700' }}"
        style="--product-color: {{ $productColor }};">

        {{-- Status strip --}}
        <div class="h-1 w-full {{ $strip }}"></div>

        <div class="p-4 space-y-3">
            {{-- Image + Name + ID + Category --}}
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-start gap-2.5 min-w-0">
                    <div class="w-10 h-10 rounded-xl overflow-hidden shrink-0 border border-zinc-100 dark:border-zinc-700 bg-white/60 dark:bg-zinc-900/40 flex items-center justify-center">
                        @if($product->image_url)
                            <img src="{{ $product->image_url }}" class="w-full h-full object-cover">
                        @else
                            <i class="fas fa-box text-zinc-300 dark:text-zinc-500"></i>
                        @endif
                    </div>

                    <div class="min-w-0">
                        <p class="font-semibold text-sm text-(--product-color) truncate">{{ $product->name }}</p>
                        <p class="font-semibold text-xs text-(--product-color)/70 mt-0.5">
                            <span class="mr-1">ID: </span>
                            {{ $product->id }}
                        </p>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium shrink-0
                            bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                    <i class="fas fa-tag text-[10px]"></i>{{ __($catLabel) }}
                </span>
            </div>

            {{-- Stats row --}}
            <div class="grid grid-cols-4 gap-2">

                {{-- Price --}}
                <div class="bg-zinc-50 dark:bg-zinc-700/80 rounded-xl px-3 py-2 text-center">
                    <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('Price') }}</p>
                    <p class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                        {{ config('storeconfig.currency_symbol') }}{{ number_format($product->price, 2) }}
                    </p>
                </div>

                {{-- Stock --}}
                <div class="bg-zinc-50 dark:bg-zinc-700/80 rounded-xl px-3 py-2 text-center">
                    <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('Stock') }}</p>
                    <p class="text-sm font-bold {{ $isOutOfStock ? 'text-red-600 dark:text-red-400' : ($product->stock_status === 'low_stock' ? 'text-yellow-600 dark:text-yellow-400' : 'text-zinc-900 dark:text-zinc-100') }}">
                        {{ $product->stocks }}
                    </p>
                </div>

                {{-- Cost --}}
                <div class="bg-zinc-50 dark:bg-zinc-700/80 rounded-xl px-3 py-2 text-center">
                    <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('Cost') }}</p>
                    <p class="text-sm font-bold text-zinc-700 dark:text-zinc-300">
                        {{ config('storeconfig.currency_symbol') }}{{ number_format($product->cost ?? 0, 2) }}
                    </p>
                </div>

                {{-- Profit --}}
                <div class="bg-zinc-50 dark:bg-zinc-700/80 rounded-xl px-3 py-2 text-center">
                    @php $profit = (float)$product->price - (float)($product->cost ?? 0); @endphp
                    <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('Profit') }}</p>
                    <p class="text-sm font-bold {{ $profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ config('storeconfig.currency_symbol') }}{{ number_format($profit, 2) }}
                    </p>
                </div>
            </div>

            {{-- Status badge --}}
            <div>
                @if($isOutOfStock)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                        <i class="fas fa-times-circle"></i>{{ __('Out of Stock') }}
                    </span>
                @elseif(!$product->is_in_stock)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300">
                        <i class="fas fa-ban"></i>{{ __('Hidden') }}
                    </span>
                @elseif($product->stock_status === 'low_stock')
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                        <i class="fas fa-exclamation-triangle"></i>{{ __('Low Stock') }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                        <i class="fas fa-check-circle"></i>{{ __('In Stock') }}
                    </span>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-1.5 pt-1 border-t border-zinc-100 dark:border-zinc-700 flex-wrap">

                {{-- View --}}
                <a href="{{ route('products.overview', $product->id) }}"
                    class="prod-card-btn inline-flex items-center text-green-600 hover:bg-green-100 dark:text-green-400 dark:hover:bg-green-700/50">

                    <i class="fas fa-eye mr-1"></i>{{ __('View') }}
                </a>

                {{-- Edit --}}
                <button @click="openEditModal({{ $product->id }})"
                    class="prod-card-btn text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20">
                    <i class="fas fa-edit"></i>{{ __('Edit') }}
                </button>

                {{-- Restock --}}
                <button @click="openRestockModal({{ $product->id }})"
                    class="prod-card-btn text-yellow-700 hover:bg-yellow-100 dark:text-yellow-400 dark:hover:bg-yellow-900/40">
                    <i class="fas fa-truck-ramp-box"></i>{{ __('Restock') }}
                </button>
            </div>
        </div>
    </div>

@empty
    <div class="sm:col-span-2 flex flex-col items-center justify-center py-20 text-zinc-400 dark:text-zinc-500">
        <i class="fas fa-box-open text-5xl mb-4 opacity-40"></i>
        <p class="text-sm">{{ __('No products found.') }}</p>
        <p class="text-xs mt-1 opacity-70">{{ __('Try adjusting your search or filter criteria.') }}</p>
    </div>
@endforelse
</div>
