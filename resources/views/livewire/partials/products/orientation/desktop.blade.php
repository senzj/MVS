<div class="hidden lg:block bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm overflow-hidden"
    wire:key="desktop-products-{{ $categoryFilter }}-{{ $stockFilter }}-{{ $search }}">

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-zinc-100 dark:divide-zinc-700"
                wire:loading.class="opacity-40 pointer-events-none"
                wire:target="categoryFilter,stockFilter,search,sortByField">

            <thead class="bg-zinc-50 dark:bg-zinc-900/60">
                <tr>
                    {{-- Image --}}
                    <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                        {{ __('Image') }}
                    </th>

                    {{-- Product name --}}
                    <th wire:click="sortByField('name')"
                        class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800 select-none">
                        <div class="flex items-center gap-1">
                            {{ __('Product Name') }}
                            @if($sortBy === 'name')
                                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-blue-500"></i>
                            @else
                                <i class="fas fa-sort text-zinc-300 dark:text-zinc-600"></i>
                            @endif
                        </div>
                    </th>

                    {{-- Category --}}
                    <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                        {{ __('Category') }}
                    </th>

                    {{-- Price --}}
                    <th wire:click="sortByField('price')"
                        class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800 select-none">
                        <div class="flex items-center justify-center gap-1">
                            {{ __('Price') }}
                            @if($sortBy === 'price')
                                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-blue-500"></i>
                            @else
                                <i class="fas fa-sort text-zinc-300 dark:text-zinc-600"></i>
                            @endif
                        </div>
                    </th>

                    {{-- Products Sold --}}
                    <th wire:click="sortByField('sold')"
                        class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800 select-none">
                        <div class="flex items-center justify-center gap-1">
                            {{ __('Sold') }}
                            @if($sortBy === 'sold')
                                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-blue-500"></i>
                            @else
                                <i class="fas fa-sort text-zinc-300 dark:text-zinc-600"></i>
                            @endif
                        </div>
                    </th>

                    {{-- Products Stocks --}}
                    <th wire:click="sortByField('stock')"
                        class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800 select-none">
                        <div class="flex items-center justify-center gap-1">
                            {{ __('Stock') }}
                            @if($sortBy === 'stock')
                                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-blue-500"></i>
                            @else
                                <i class="fas fa-sort text-zinc-300 dark:text-zinc-600"></i>
                            @endif
                        </div>
                    </th>

                    {{-- Product Cost --}}
                    <th wire:click="sortByField('cost')"
                        class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800 select-none">
                        <div class="flex items-center justify-center gap-1">
                            {{ __('Cost') }}
                            @if($sortBy === 'cost')
                                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-blue-500"></i>
                            @else
                                <i class="fas fa-sort text-zinc-300 dark:text-zinc-600"></i>
                            @endif
                        </div>
                    </th>

                    {{-- Product Profit --}}
                    <th wire:click="sortByField('profit')"
                        class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800 select-none">
                        <div class="flex items-center justify-center gap-1">
                            {{ __('Profit') }}
                            @if($sortBy === 'profit')
                                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-blue-500"></i>
                            @else
                                <i class="fas fa-sort text-zinc-300 dark:text-zinc-600"></i>
                            @endif
                        </div>
                    </th>

                    {{-- Actions --}}
                    <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($products as $index => $product)
                    @php
                        $isOutOfStock  = empty($product->stocks) || (int)$product->stocks === 0;
                        $catLabel      = $categoryNames[$product->category] ?? __('Uncategorized');
                        $statusColor   = $isOutOfStock ? '#f87171'
                            : ($product->stock_status === 'low_stock' ? '#fbbf24'
                            : ($product->is_in_stock ? '#22c55e' : '#fb923c'));
                        $productColor  = $product->color ?? null;
                    @endphp
                    <tr wire:key="product-row-{{ $product->id }}-{{ $categoryFilter }}-{{ $stockFilter }}-{{ $search }}-{{ $index }}"
                        class="transition hover:brightness-95 {{ $productColor ? 'bg-(--row-color)/10' : 'bg-gray-200 dark:bg-zinc-800' }}"
                        style="
                            --row-color: {{ $statusColor }};
                            --product-color: {{ $productColor }};
                            box-shadow: inset 4px 0 0 0 {{ $statusColor }};
                        "
                    >

                        {{-- Image --}}
                        <td class="px-4 py-3">
                            <div class="w-28 h-28 rounded-xl overflow-hidden border border-zinc-100 dark:border-zinc-700 bg-white/60 dark:bg-zinc-900/40 flex items-center justify-center mx-auto">
                                @if($product->image_url)
                                    <img src="{{ $product->image_url }}" class="w-full h-full object-cover">
                                @else
                                    <i class="fas fa-box text-zinc-300 dark:text-zinc-500"></i>
                                @endif
                            </div>
                        </td>

                        {{-- Name + ID --}}
                        <td class="px-4 py-3">
                            <div class="text-sm font-semibold text-(--product-color)">{{ $product->name }}</div>
                            <div class="text-xs font-semibold text-(--product-color)/70 mt-0.5">
                                <span class="opacity-50">ID:</span>
                                {{ $product->id }}
                            </div>
                        </td>

                        {{-- Category --}}
                        <td class="px-4 py-3 text-center gap-1">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium
                                            bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                <i class="fas fa-tag text-xs"></i>{{ __($catLabel) }}
                            </span>

                            <div class="">
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
                        </td>

                        {{-- Price --}}
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ config('storeconfig.currency_symbol') }}{{ number_format($product->price, 2) }}
                            </span>
                        </td>

                        {{-- Sold --}}
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm font-medium text-green-600 dark:text-green-400">
                                <i class="fas fa-chart-bar mr-1 opacity-70"></i>{{ $product->sold }}
                            </span>
                        </td>

                        {{-- Stocks --}}
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm font-semibold
                                {{ $isOutOfStock ? 'text-red-600 dark:text-red-400'
                                    : ($product->stock_status === 'low_stock' ? 'text-yellow-600 dark:text-yellow-400'
                                    : 'text-zinc-900 dark:text-zinc-100') }}">
                                <i class="fas fa-cubes mr-1 opacity-60"></i>{{ $product->stocks }}
                            </span>
                        </td>

                        {{-- Cost (avg buying price) --}}
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                {{ config('storeconfig.currency_symbol') }}{{ number_format($product->cost ?? 0, 2) }}
                            </span>
                        </td>

                        {{-- Profit margin --}}
                        <td class="px-4 py-3 text-center">
                            @php
                                $profit = (float)$product->price - (float)($product->cost ?? 0);
                                $margin = ($product->price > 0 && ($product->cost ?? 0) > 0)
                                    ? ($profit / $product->price) * 100
                                    : null;
                            @endphp
                            <div class="flex flex-col items-center gap-0.5">
                                <span class="text-sm font-semibold {{ $profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ config('storeconfig.currency_symbol') }}{{ number_format($profit, 2) }}
                                </span>
                                @if($margin !== null)
                                    <span class="text-xs text-zinc-400 dark:text-zinc-500">
                                        {{ number_format($margin, 1) }}%
                                    </span>
                                @endif
                            </div>
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">

                                {{-- View --}}
                                <a href="{{ route('products.overview', $product->id) }}"
                                    class="tbl-action-btn inline-flex items-center text-green-600 hover:bg-green-100 dark:text-green-400 dark:hover:bg-green-700/50"
                                    title="{{ __('View Product Details') }}">
                                    
                                    <i class="fas fa-eye text-sm"></i>
                                    <span class="text-xs ml-1">{{ __('View') }}</span>
                                </a>

                                {{-- Edit --}}
                                <button @click="openEditModal({{ $product->id }})"
                                    class="tbl-action-btn text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20"
                                    title="{{ __('Edit Product') }}">
                                    <i class="fas fa-edit text-sm"></i>
                                    <span class="text-xs">{{ __('Edit') }}</span>
                                </button>

                                {{-- Restock --}}
                                <button @click="openRestockModal({{ $product->id }})"
                                    class="tbl-action-btn text-yellow-600 hover:bg-yellow-50 dark:text-yellow-400 dark:hover:bg-yellow-900/20"
                                    title="{{ __('Add stocks to this product') }}">
                                    <i class="fas fa-truck-ramp-box text-sm"></i>
                                    <span class="text-xs">{{ __('Restock') }}</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr wire:key="no-products-{{ $categoryFilter }}-{{ $stockFilter }}-{{ $search }}">
                        <td colspan="9" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center text-zinc-400 dark:text-zinc-500">
                                <i class="fas fa-box-open text-5xl mb-4 opacity-40"></i>
                                <p class="text-sm">{{ __('No products found.') }}</p>
                                <p class="text-xs mt-1 opacity-70">{{ __('Try adjusting your search or filter criteria.') }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($products->hasPages())
        <div class="px-4 py-3 border-t border-zinc-100 dark:border-zinc-700">
            {{ $products->links() }}
        </div>
    @endif
</div>
