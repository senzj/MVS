{{--
    Clicking a card calls $wire.addProductToCart(id):
      • product not in cart → adds it with qty 1
      • product already in cart → increments qty
    Cart qty badges come from the parent layout's Alpine `cartQty` map.
--}}

@php $categories = \App\Models\Product::getCategories(); @endphp

<div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm overflow-hidden flex flex-col"
     x-data="{ cat: 'all' }">

    {{-- ── Header: search + create product button ── --}}
    <div class="flex items-center gap-2 px-3 py-2.5
                bg-zinc-50/60 dark:bg-zinc-900/20
                border-b border-zinc-100 dark:border-zinc-700">

        @if(isset($pageMode) && $pageMode !== 'edit' && isset($showProductForm) && !$showProductForm)
            <button type="button" wire:click="openProductForm()"
                class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold
                       bg-emerald-600 text-white hover:bg-emerald-700 active:scale-95
                       transition-all shadow-sm shadow-emerald-500/20 whitespace-nowrap">
                <i class="fas fa-box-open text-[10px]"></i>
                <span class="hidden sm:inline">{{ __('New Product') }}</span>
                <span class="sm:hidden">+</span>
            </button>
        @endif

        <div class="relative flex-1">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-xs pointer-events-none"></i>
            <input type="text"
                wire:model.live.debounce.200ms="productSearch"
                placeholder="{{ __('Search products…') }}"
                class="w-full pl-8 pr-8 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                       bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100
                       focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
            @if(!empty($productSearch ?? ''))
                <button type="button" wire:click="$set('productSearch', '')"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 w-5 h-5 flex items-center justify-center
                           rounded-full text-zinc-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                    <i class="fas fa-times text-[10px]"></i>
                </button>
            @endif
        </div>
    </div>

    {{-- ── Category filter pills ── --}}
    @if(count($categories) > 0)
        <div class="flex gap-1.5 px-3 py-2 overflow-x-auto border-b border-zinc-100 dark:border-zinc-700 bg-zinc-50/30 dark:bg-zinc-900/10 scrollbar-thin scrollbar-thumb-zinc-300 dark:scrollbar-thumb-zinc-700/80 scrollbar-track-transparent">
            <button type="button" @click="cat = 'all'"
                :class="cat === 'all'
                    ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/20'
                    : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-600 hover:border-blue-300 dark:hover:border-blue-600'"
                class="shrink-0 px-3 py-1 rounded-full text-xs font-bold transition-all whitespace-nowrap">
                <i class="fas fa-th mr-1 opacity-70"></i>{{ __('All') }}
            </button>
            @foreach($categories as $catId => $catName)
                <button type="button" @click="cat = '{{ $catId }}'"
                    :class="cat === '{{ $catId }}'
                        ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/20'
                        : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-600 hover:border-blue-300 dark:hover:border-blue-600'"
                    class="shrink-0 px-3 py-1 rounded-full text-xs font-bold transition-all whitespace-nowrap">
                    {{ $catName }}
                </button>
            @endforeach
        </div>
    @endif

    {{-- ── Product card grid ── --}}
    <div class="p-1 overflow-y-auto max-h-[78vh] scrollbar-thin scrollbar-thumb-zinc-300 dark:scrollbar-thumb-zinc-700/80 scrollbar-track-transparent">

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-3 xl:grid-cols-4 gap-2">

            @forelse(($this->filteredProducts ?? $products ?? []) as $product)
                @php
                    $pOOS = ($product->stocks ?? 0) <= 0 || !($product->is_in_stock ?? true);
                    $pLow = !$pOOS && ($product->stocks ?? 0) < 10;

                    $productColor = $product->color ? '' : 'bg-zinc-50/50 dark:bg-zinc-800/50';
                @endphp

                <button type="button"
                    wire:key="pgrid-{{ $product->id }}"
                    x-show="cat === 'all' || cat === '{{ (string)$product->category_id }}'"
                    @if(!$pOOS)
                        wire:click="addProductToCart({{ $product->id }})"
                        x-on:click="
                            /* brief pop animation */
                            $el.classList.add('scale-95');
                            setTimeout(() => $el.classList.remove('scale-95'), 120);
                        "
                    @else
                        disabled
                    @endif

                    class="relative flex flex-col text-left rounded-xl border overflow-hidden
                           transition-all duration-150 select-none
                           {{ $pOOS
                               ? 'border-zinc-100 dark:border-zinc-700/40 opacity-45 cursor-not-allowed'
                               : 'border-zinc-200 dark:border-zinc-700
                                  hover:border-blue-400 dark:hover:border-blue-500
                                  hover:brightness-95 hover:shadow-md cursor-pointer' }}"
                            style="
                                {{ $product->color ? "background-color: {$product->color}80;" : '' }}
                            ">

                    {{-- Stock colour strip --}}
                    <div class="h-1 w-full shrink-0
                        {{ $pOOS ? 'bg-red-300 dark:bg-red-800' : ($pLow ? 'bg-yellow-400' : 'bg-green-400') }}">
                    </div>

                    {{-- Cart qty badge (shows when item is in cart) --}}
                    <div x-show="(cartQty['{{ $product->id }}'] || 0) > 0"
                         x-cloak
                         class="absolute top-2 right-2 z-10">
                        <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full
                                     text-[10px] font-black bg-blue-600 text-white shadow-sm shadow-blue-500/30">
                            <span x-text="cartQty['{{ $product->id }}'] || 0"></span>
                        </span>
                    </div>

                    <div class="flex flex-col flex-1 p-2.5 gap-1.5">

                        {{-- Image --}}
                        <div class="flex items-center justify-center w-38 h-38 bg-zinc-100 dark:bg-zinc-700 rounded-lg overflow-hidden mx-auto">
                            @if ($product->image_url)
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="max-h-full max-w-full object-contain">
                            @else
                                <i class="fas fa-box text-zinc-300 dark:text-zinc-500"></i>
                            @endif
                        </div>

                        <div class="flex items-center gap-1 space-between">
                            {{-- Product name --}}
                            <p class="text-xs font-bold text-zinc-900 dark:text-zinc-100 leading-snug line-clamp-2 flex-1">
                                {{ $product->name }}
                            </p>

                            {{-- Category badge --}}
                            <span class="inline-block self-start px-1.5 py-0.5 rounded text-[10px] font-bold leading-tight
                                        bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100
                                        truncate max-w-full">
                                {{ $product->category_name }}
                            </span>
                        </div>

                        {{-- Price + stock --}}
                        <div class="flex items-end justify-between gap-1 pt-0.5">
                            <span class="text-sm font-black font-mono text-zinc-900 dark:text-zinc-100
                                         group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors">
                                ₱{{ number_format($product->price, 2) }}
                            </span>

                            <span class="text-[10px] font-semibold shrink-0
                                {{ $pOOS
                                    ? 'text-red-500 dark:text-red-400'
                                    : ($pLow ? 'text-yellow-600 dark:text-yellow-400' : 'text-zinc-800 dark:text-gray-100') }}">
                                <i class="fas fa-box mr-0.5"></i>{{ $product->stocks ?? 0 }}
                                @if($pOOS) <span class="hidden sm:inline">{{ __('OOS') }}</span> @endif
                            </span>
                        </div>
                    </div>
                </button>
            @empty
                <div class="col-span-2 sm:col-span-3 xl:col-span-4 py-14
                            flex flex-col items-center text-zinc-400 dark:text-zinc-500">
                    <i class="fas fa-box-open text-4xl mb-2 opacity-30"></i>
                    <p class="text-sm font-medium">{{ __('No products found') }}</p>
                    <p class="text-xs mt-0.5 opacity-70">{{ __('Try a different search or category') }}</p>
                </div>
            @endforelse

        </div>
    </div>
</div>
