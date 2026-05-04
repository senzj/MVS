@section('title', __('Product Inventory'))

<div class="w-full max-w-full overflow-x-hidden px-2 sm:px-4 pb-8"
     x-data="{
         showCreateModal: false,
         showEditModal: false,
         showDeleteModal: false,
         showArchiveModal: false,

         openCreateModal()  { $wire.openCreateModal();  this.showCreateModal  = true; },
         closeCreateModal() { this.showCreateModal  = false; $wire.resetForm(); },
         openEditModal(id)  { $wire.openEditModal(id); this.showEditModal    = true; },
         closeEditModal()   { this.showEditModal    = false; $wire.resetForm(); },
         openDeleteModal(id)  { $wire.openDeleteModal(id);  this.showDeleteModal  = true; },
         closeDeleteModal()   { this.showDeleteModal  = false; $wire.selectedProductId = null; },
         openArchiveModal(id) { $wire.openArchiveModal(id); this.showArchiveModal = true; },
         closeArchiveModal()  { this.showArchiveModal = false; $wire.selectedProductId = null; },
     }"
     @close-create-modal.window="closeCreateModal()"
     @close-edit-modal.window="closeEditModal()"
     @close-delete-modal.window="closeDeleteModal()"
     @close-archive-modal.window="closeArchiveModal()">

    {{-- ═══════════════════════════════════════════════
         HEADER
    ════════════════════════════════════════════════ --}}
    <div class="flex items-start justify-between gap-3 py-2 mb-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <i class="fas fa-boxes text-blue-500"></i>
                {{ __('Product Inventory') }}
            </h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                {{ __('Manage your product inventory and stock levels') }}
            </p>
        </div>
        <button @click="openCreateModal()"
            class="cursor-pointer inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold
                   hover:bg-blue-700 active:scale-95 transition-all shadow-md shadow-blue-500/20 shrink-0">
            <i class="fas fa-plus"></i>
            <span>{{ __('Add Product') }}</span>
        </button>
    </div>

    {{-- ═══════════════════════════════════════════════
         QUICK STATS  (2×2 on mobile, 4-col on md+)
    ════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">

        {{-- Total --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center shrink-0">
                <i class="fas fa-box text-blue-600 dark:text-blue-400"></i>
            </div>
            <div>
                <div class="text-xl font-bold text-zinc-900 dark:text-zinc-100 leading-none">{{ $products->total() }}</div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('Total Products') }}</div>
            </div>
        </div>

        {{-- In Stock --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-green-100 dark:bg-green-900/40 flex items-center justify-center shrink-0">
                <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
            </div>
            <div>
                <div class="text-xl font-bold text-zinc-900 dark:text-zinc-100 leading-none">
                    {{ $allProducts->where('stocks', '>=', 10)->count() }}
                </div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('In Stock') }}</div>
            </div>
        </div>

        {{-- Low Stock --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-yellow-100 dark:bg-yellow-900/40 flex items-center justify-center shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400"></i>
            </div>
            <div>
                <div class="text-xl font-bold text-zinc-900 dark:text-zinc-100 leading-none">
                    {{ $allProducts->where('stocks', '<', 10)->where('stocks', '>', 0)->count() }}
                </div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('Low Stock') }}</div>
            </div>
        </div>

        {{-- Out of Stock --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/40 flex items-center justify-center shrink-0">
                <i class="fas fa-times-circle text-red-600 dark:text-red-400"></i>
            </div>
            <div>
                <div class="text-xl font-bold text-zinc-900 dark:text-zinc-100 leading-none">
                    {{ $allProducts->where('stocks', '==', 0)->count() }}
                </div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('Out of Stock') }}</div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════
         FILTERS & SEARCH
    ════════════════════════════════════════════════ --}}
    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-4 mb-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">

            {{-- Search --}}
            <div class="sm:col-span-2 lg:col-span-1">
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 mb-1.5 uppercase tracking-wide">
                    <i class="fas fa-search mr-1"></i>{{ __('Search product') }}
                </label>
                <div class="relative">
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="{{ __('Search products...') }}"
                           class="w-full pl-3 pr-8 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                                  bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                  focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                    @if($search || $categoryFilter !== 'all' || $stockFilter)
                        <button wire:click="clearSearch"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-red-500 transition-colors">
                            <i class="fas fa-times-circle text-sm"></i>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Category Filter --}}
            <div>
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 mb-1.5 uppercase tracking-wide">
                    <i class="fas fa-tags mr-1"></i>{{ __('Category') }}
                </label>
                <select wire:model.live.debounce.300ms="categoryFilter"
                        wire:key="category-filter-{{ $categoryFilter }}"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                               bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition cursor-pointer">
                    <option value="all">{{ __('All Categories') }}</option>
                    @foreach($categories as $key => $category)
                        <option value="{{ $key }}">{{ __($category) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Stock Filter --}}
            <div>
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 mb-1.5 uppercase tracking-wide">
                    <i class="fas fa-layer-group mr-1"></i>{{ __('Stock Level') }}
                </label>
                <select wire:model.live.debounce.300ms="stockFilter"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                               bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition cursor-pointer">
                    <option value="">{{ __('All Stock Levels') }}</option>
                    <option value="in_stock">{{ __('In Stock') }}</option>
                    <option value="low_stock">{{ __('Low Stock') }}</option>
                    <option value="out_of_stock">{{ __('Out of Stock') }}</option>
                    <option value="available">{{ __('Available') }}</option>
                    <option value="hidden">{{ __('Hidden') }}</option>
                </select>
            </div>

            {{-- Results count --}}
            <div class="flex items-end">
                <div class="text-xs text-zinc-500 dark:text-zinc-400 py-2">
                    @if($search || $categoryFilter !== 'all' || $stockFilter)
                        <i class="fas fa-filter mr-1 text-blue-500"></i>
                        {{ __('Filtered') }}: <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ $products->total() }}</span> {{ __('products results') }}
                    @else
                        <i class="fas fa-list mr-1"></i>
                        {{ __('Total') }}: <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ $products->total() }}</span> {{ __('products') }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Loading overlay --}}
    @include('livewire.partials.loading-overlay', [
        'wireTarget' => 'categoryFilter,stockFilter,search,sortByField',
        'title' => __('Updating...'),
        'message' => __('Please wait while we process your request'),
    ])

    {{-- PRODUCT LIST --}}

    {{-- ── Mobile Cards (< lg) ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 lg:hidden"
         wire:loading.class="opacity-50 pointer-events-none"
         wire:target="categoryFilter,stockFilter,search,sortByField"
         wire:key="mobile-products-{{ $categoryFilter }}-{{ $stockFilter }}-{{ $search }}">

        @forelse($products as $product)
            @php
                $isOutOfStock = empty($product->stocks) || (int)$product->stocks === 0;
                $categoryNames = \App\Models\Product::getCategories();
                $catLabel = $categoryNames[$product->category] ?? ucfirst($product->category ?? 'Other');
            @endphp

            <div wire:key="mobile-card-{{ $product->id }}"
                 class="bg-white dark:bg-zinc-800 rounded-2xl border shadow-sm overflow-hidden transition-all
                        {{ $isOutOfStock
                            ? 'border-red-200 dark:border-red-900/50'
                            : 'border-zinc-100 dark:border-zinc-700' }}">

                {{-- Top accent strip (stock color) --}}
                @php
                    $strip = $isOutOfStock ? 'bg-red-400'
                        : ($product->stock_status === 'low_stock' ? 'bg-yellow-400'
                        : ($product->is_in_stock ? 'bg-green-500' : 'bg-orange-400'));
                @endphp
                <div class="h-1 w-full {{ $strip }}"></div>

                <div class="p-4 space-y-3">
                    {{-- Name + ID + Category --}}
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">{{ $product->name }}</p>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">
                                <i class="fas fa-hashtag mr-0.5"></i>{{ $product->id }}
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium shrink-0
                                     bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                            <i class="fas fa-tag text-[10px]"></i>{{ __($catLabel) }}
                        </span>
                    </div>

                    {{-- Description --}}
                    @if($product->description)
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 leading-relaxed line-clamp-2">
                            {{ Str::limit($product->description, 80) }}
                        </p>
                    @endif

                    {{-- Stats row --}}
                    <div class="grid grid-cols-3 gap-2">
                        <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-xl px-3 py-2 text-center">
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('Price') }}</p>
                            <p class="text-sm font-bold text-zinc-900 dark:text-zinc-100">₱{{ number_format($product->price, 2) }}</p>
                        </div>
                        <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-xl px-3 py-2 text-center">
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('Stock') }}</p>
                            <p class="text-sm font-bold {{ $isOutOfStock ? 'text-red-600 dark:text-red-400' : ($product->stock_status === 'low_stock' ? 'text-yellow-600 dark:text-yellow-400' : 'text-zinc-900 dark:text-zinc-100') }}">
                                {{ $product->stocks }}
                            </p>
                        </div>
                        <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-xl px-3 py-2 text-center">
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('Sold') }}</p>
                            <p class="text-sm font-bold text-green-600 dark:text-green-400">{{ $product->sold }}</p>
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

                        {{-- Availability toggle --}}
                        @if($product->is_in_stock)
                            <button @click="openArchiveModal({{ $product->id }})"
                                class="prod-card-btn text-green-700 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-900/20">
                                <i class="fas fa-eye-slash"></i>{{ __('Hide') }}
                            </button>
                        @elseif($isOutOfStock)
                            <span class="prod-card-btn text-zinc-400 cursor-not-allowed opacity-60"
                                  title="{{ __('This product is out of stock. Edit to add more stocks.') }}">
                                <i class="fas fa-ban"></i>{{ __('Out Of Stock') }}
                            </span>
                        @else
                            <button wire:click="makeAvailable({{ $product->id }})"
                                class="prod-card-btn text-orange-600 hover:bg-orange-50 dark:text-orange-400 dark:hover:bg-orange-900/20">
                                <i class="fas fa-eye"></i>{{ __('Show') }}
                            </button>
                        @endif

                        {{-- Edit --}}
                        <button @click="openEditModal({{ $product->id }})"
                            class="prod-card-btn text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20">
                            <i class="fas fa-edit"></i>{{ __('Edit') }}
                        </button>

                        {{-- Delete --}}
                        @if($product->orderItems()->count() === 0)
                            <button @click="openDeleteModal({{ $product->id }})"
                                class="prod-card-btn text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 ml-auto">
                                <i class="fas fa-trash"></i>{{ __('Delete') }}
                            </button>
                        @else
                            <span class="prod-card-btn text-zinc-400 opacity-60 cursor-not-allowed ml-auto"
                                  title="{{ __('Cannot delete - has ongoing order') }}">
                                <i class="fas fa-ban"></i>{{ __('Pending') }}
                            </span>
                        @endif
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

    {{-- ── Desktop Table (≥ lg) ── --}}
    <div class="hidden lg:block bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm overflow-hidden"
         wire:key="desktop-products-{{ $categoryFilter }}-{{ $stockFilter }}-{{ $search }}">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-100 dark:divide-zinc-700"
                   wire:loading.class="opacity-40 pointer-events-none"
                   wire:target="categoryFilter,stockFilter,search,sortByField">
                <thead class="bg-zinc-50 dark:bg-zinc-900/60">
                    <tr>
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
                        <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            {{ __('Description') }}
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            {{ __('Category') }}
                        </th>
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
                        <th wire:click="sortByField('stocks')"
                            class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800 select-none">
                            <div class="flex items-center justify-center gap-1">
                                {{ __('Stock') }}
                                @if($sortBy === 'stocks')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-blue-500"></i>
                                @else
                                    <i class="fas fa-sort text-zinc-300 dark:text-zinc-600"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            {{ __('Status') }}
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @forelse($products as $index => $product)
                        @php
                            $isOutOfStock  = empty($product->stocks) || (int)$product->stocks === 0;
                            $categoryNames = \App\Models\Product::getCategories();
                            $catLabel      = $categoryNames[$product->category] ?? ucfirst($product->category ?? 'Other');
                        @endphp
                        <tr wire:key="product-row-{{ $product->id }}-{{ $categoryFilter }}-{{ $stockFilter }}-{{ $search }}-{{ $index }}"
                            class="transition-colors
                                   {{ $isOutOfStock
                                       ? 'bg-red-50/60 dark:bg-red-900/10 hover:bg-red-50 dark:hover:bg-red-900/20'
                                       : 'hover:bg-zinc-50 dark:hover:bg-zinc-700/40' }}">

                            {{-- Name + ID --}}
                            <td class="px-4 py-3">
                                <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $product->name }}</div>
                                <div class="text-xs text-zinc-400 dark:text-zinc-500"><i class="fas fa-hashtag mr-0.5"></i>{{ $product->id }}</div>
                            </td>

                            {{-- Description --}}
                            <td class="px-4 py-3 max-w-[180px]">
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 truncate">
                                    {{ Str::limit($product->description, 40) ?: __('No description') }}
                                </div>
                            </td>

                            {{-- Category --}}
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium
                                             bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                    <i class="fas fa-tag text-[10px]"></i>{{ __($catLabel) }}
                                </span>
                            </td>

                            {{-- Price --}}
                            <td class="px-4 py-3 text-center">
                                <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">₱{{ number_format($product->price, 2) }}</span>
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

                            {{-- Status --}}
                            <td class="px-4 py-3 text-center">
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
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">

                                    {{-- Availability toggle --}}
                                    @if($product->is_in_stock)
                                        <button @click="openArchiveModal({{ $product->id }})"
                                            class="tbl-action-btn text-green-700 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-900/20"
                                            title="{{ __('This Item is Currently for Sale - Mark as Unavailable') }}">
                                            <i class="fas fa-eye-slash text-sm"></i>
                                            <span class="text-xs">{{ __('Hide') }}</span>
                                        </button>
                                    @elseif($isOutOfStock)
                                        <span class="tbl-action-btn text-zinc-400 opacity-50 cursor-not-allowed"
                                              title="{{ __('This product is out of stock. Edit to add more stocks.') }}">
                                            <i class="fas fa-ban text-sm"></i>
                                            <span class="text-xs leading-tight">{{ __('Out of Stock') }}</span>
                                        </span>
                                    @else
                                        <button wire:click="makeAvailable({{ $product->id }})"
                                            class="tbl-action-btn text-orange-600 hover:bg-orange-50 dark:text-orange-400 dark:hover:bg-orange-900/20"
                                            title="{{ __('This Item is Currently Hidden - Make Available for Sale') }}">
                                            <i class="fas fa-eye text-sm"></i>
                                            <span class="text-xs">{{ __('Show') }}</span>
                                        </button>
                                    @endif

                                    {{-- Edit --}}
                                    <button @click="openEditModal({{ $product->id }})"
                                        class="tbl-action-btn text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20"
                                        title="{{ __('Edit Product') }}">
                                        <i class="fas fa-edit text-sm"></i>
                                        <span class="text-xs">{{ __('Edit') }}</span>
                                    </button>

                                    {{-- Delete --}}
                                    @if($product->orderItems()->count() === 0)
                                        <button @click="openDeleteModal({{ $product->id }})"
                                            class="tbl-action-btn text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                            title="{{ __('Delete Permanently. This action cannot be undone.') }}">
                                            <i class="fas fa-trash text-sm"></i>
                                            <span class="text-xs">{{ __('Delete') }}</span>
                                        </button>
                                    @else
                                        <span class="tbl-action-btn text-zinc-400 opacity-50 cursor-not-allowed"
                                              title="{{ __('Cannot delete - has ongoing order') }}">
                                            <i class="fas fa-ban text-sm"></i>
                                            <span class="text-xs">{{ __('Pending') }}</span>
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr wire:key="no-products-{{ $categoryFilter }}-{{ $stockFilter }}-{{ $search }}">
                            <td colspan="8" class="px-6 py-20 text-center">
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

    {{-- Mobile pagination --}}
    <div class="lg:hidden mt-3">
        @if($products->hasPages())
            {{ $products->links() }}
        @endif
    </div>


    {{-- ═══════════════════════════════════════════════
         CREATE PRODUCT MODAL
    ════════════════════════════════════════════════ --}}
    <div x-show="showCreateModal" x-cloak
         class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4 z-50">
        <div class="bg-white dark:bg-zinc-800 w-full sm:rounded-2xl sm:max-w-2xl max-h-[92dvh] overflow-y-auto shadow-2xl">

            <div class="sticky top-0 flex items-center justify-between px-5 py-4 border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 z-10">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <i class="fas fa-plus-circle text-blue-500"></i>{{ __('Add New Product') }}
                </h3>
                <button @click="closeCreateModal()"
                    class="cursor-pointer w-8 h-8 flex items-center justify-center rounded-full text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form wire:submit.prevent="createProduct" class="p-5 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Product Name --}}
                    <div>
                        <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                            <i class="fas fa-tag mr-1"></i>{{ __('Product Name') }}
                        </label>
                        <input type="text" wire:model="name"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                        @error('name') <p class="text-red-500 text-xs mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    {{-- Category --}}
                    <div>
                        <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                            <i class="fas fa-folder mr-1"></i>{{ __('Category') }}
                        </label>
                        <select wire:model="category"
                            class="cursor-pointer w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                            <option value="">{{ __('Select Category') }}</option>
                            @foreach($categories as $key => $categoryName)
                                <option value="{{ $key }}">{{ __($categoryName) }}</option>
                            @endforeach
                        </select>
                        @error('category') <p class="text-red-500 text-xs mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    {{-- Price --}}
                    <div>
                        <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                            <i class="fas fa-peso-sign mr-1"></i>{{ __('Price') }}
                            <span class="normal-case font-normal ml-1">({{ __('per unit or kilo') }})</span>
                        </label>
                        <input type="number" step="0.01" wire:model="price"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                        @error('price') <p class="text-red-500 text-xs mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    {{-- Stocks --}}
                    <div>
                        <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                            <i class="fas fa-cubes mr-1"></i>{{ __('Stocks') }}
                            <span class="normal-case font-normal ml-1">({{ __('per unit or kilo') }})</span>
                        </label>
                        <input type="number" wire:model="stocks"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                        @error('stocks') <p class="text-red-500 text-xs mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    {{-- Available for Sale --}}
                    <div class="sm:col-span-2">
                        <label class="inline-flex items-center gap-2.5 cursor-pointer select-none">
                            <input type="checkbox" wire:model="is_in_stock"
                                class="h-4 w-4 rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                <i class="fas fa-check-circle mr-1 text-green-500"></i>{{ __('Available for Sale') }}
                            </span>
                        </label>
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                        <i class="fas fa-align-left mr-1"></i>{{ __('Description') }}
                        <span class="normal-case font-normal ml-1">({{ __('optional') }})</span>
                    </label>
                    <textarea wire:model="description" rows="3"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition resize-none"></textarea>
                    @error('description') <p class="text-red-500 text-xs mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="closeCreateModal()"
                        class="cursor-pointer px-4 py-2 text-sm font-medium rounded-xl border border-zinc-200 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        <i class="fas fa-times mr-1"></i>{{ __('Cancel') }}
                    </button>
                    <button type="submit"
                        class="cursor-pointer px-4 py-2 text-sm font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-700 active:scale-95 transition-all shadow-md shadow-blue-500/20">
                        <i class="fas fa-save mr-1"></i>{{ __('Create Product') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════
         EDIT PRODUCT MODAL
    ════════════════════════════════════════════════ --}}
    <div x-show="showEditModal" x-cloak
         class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4 z-50">
        <div class="bg-white dark:bg-zinc-800 w-full sm:rounded-2xl sm:max-w-2xl max-h-[92dvh] overflow-y-auto shadow-2xl">

            <div class="sticky top-0 flex items-center justify-between px-5 py-4 border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 z-10">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <i class="fas fa-edit text-blue-500"></i>{{ __('Edit Product') }}
                </h3>
                <button @click="closeEditModal()"
                    class="cursor-pointer w-8 h-8 flex items-center justify-center rounded-full text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form wire:submit.prevent="updateProduct" @submit="closeEditModal()" class="p-5 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                            <i class="fas fa-tag mr-1"></i>{{ __('Product Name') }}
                        </label>
                        <input type="text" wire:model="name"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                        @error('name') <p class="text-red-500 text-xs mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                            <i class="fas fa-folder mr-1"></i>{{ __('Category') }}
                        </label>
                        <select wire:model="category"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition cursor-pointer">
                            <option value="">{{ __('Select Category') }}</option>
                            @foreach($categories as $key => $categoryName)
                                <option value="{{ $key }}">{{ __($categoryName) }}</option>
                            @endforeach
                        </select>
                        @error('category') <p class="text-red-500 text-xs mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                            <i class="fas fa-peso-sign mr-1"></i>{{ __('Price') }}
                            <span class="normal-case font-normal ml-1">({{ __('per unit or kilo') }})</span>
                        </label>
                        <input type="number" step="0.01" wire:model="price"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                        @error('price') <p class="text-red-500 text-xs mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                            <i class="fas fa-cubes mr-1"></i>{{ __('Stock') }}
                        </label>
                        <input type="number" wire:model="stocks"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                        @error('stocks') <p class="text-red-500 text-xs mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                            <i class="fas fa-chart-line mr-1"></i>{{ __('Sold') }}
                        </label>
                        <input type="number" wire:model="sold"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                        @error('sold') <p class="text-red-500 text-xs mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center">
                        <label class="inline-flex items-center gap-2.5 cursor-pointer select-none">
                            <input type="checkbox" wire:model="is_in_stock"
                                class="h-4 w-4 rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                <i class="fas fa-check-circle mr-1 text-green-500"></i>{{ __('Available for Sale') }}
                            </span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                        <i class="fas fa-align-left mr-1"></i>{{ __('Description') }}
                        <span class="normal-case font-normal ml-1">({{ __('optional') }})</span>
                    </label>
                    <textarea wire:model="description" rows="3"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition resize-none"></textarea>
                    @error('description') <p class="text-red-500 text-xs mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="closeEditModal()"
                        class="cursor-pointer px-4 py-2 text-sm font-medium rounded-xl border border-zinc-200 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        <i class="fas fa-times mr-1"></i>{{ __('Cancel') }}
                    </button>
                    <button type="submit"
                        class="cursor-pointer px-4 py-2 text-sm font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-700 active:scale-95 transition-all shadow-md shadow-blue-500/20">
                        <i class="fas fa-save mr-1"></i>{{ __('Update Product') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════
         HIDE / ARCHIVE CONFIRM MODAL
    ════════════════════════════════════════════════ --}}
    <div x-show="showArchiveModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-sm bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl overflow-hidden">
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-green-100 dark:bg-green-900/40 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-eye-slash text-green-600 dark:text-green-400 text-2xl"></i>
                </div>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 mb-2">{{ __('Hide Product') }}</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">
                    {{ __('Are you sure you want to Hide this product? This will mark it as unavailable for sale.') }}
                </p>
                <div class="flex justify-center gap-2">
                    <button @click="closeArchiveModal()"
                        class="cursor-pointer px-4 py-2 text-sm font-medium rounded-xl border border-zinc-200 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        <i class="fas fa-times mr-1"></i>{{ __('Cancel') }}
                    </button>
                    <button wire:click="archiveProduct"
                        class="cursor-pointer px-4 py-2 text-sm font-semibold rounded-xl bg-green-600 text-white hover:bg-green-700 active:scale-95 transition-all">
                        <i class="fas fa-eye-slash mr-1"></i>{{ __('Confirm Hide') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════
         DELETE CONFIRM MODAL
    ════════════════════════════════════════════════ --}}
    <div x-show="showDeleteModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-sm bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl overflow-hidden">
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-red-100 dark:bg-red-900/40 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-trash text-red-600 dark:text-red-400 text-2xl"></i>
                </div>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 mb-2">{{ __('Delete Product') }}</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">
                    {{ __('Are you sure you want to delete this product? This action cannot be undone.') }}
                </p>
                <div class="flex justify-center gap-2">
                    <button @click="closeDeleteModal()"
                        class="cursor-pointer px-4 py-2 text-sm font-medium rounded-xl border border-zinc-200 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        <i class="fas fa-times mr-1"></i>{{ __('Cancel') }}
                    </button>
                    <button wire:click="deleteProduct" @click="closeDeleteModal()"
                        class="cursor-pointer px-4 py-2 text-sm font-semibold rounded-xl bg-red-600 text-white hover:bg-red-700 active:scale-95 transition-all">
                        <i class="fas fa-trash mr-1"></i>{{ __('Delete') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

<style>
    .prod-card-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.375rem 0.625rem;
        border-radius: 0.625rem;
        font-size: 0.75rem;
        font-weight: 500;
        transition: background-color 0.15s;
        cursor: pointer;
        white-space: nowrap;
    }
    .tbl-action-btn {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        gap: 0.125rem;
        padding: 0.375rem 0.5rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        font-weight: 500;
        transition: background-color 0.15s;
        cursor: pointer;
        white-space: nowrap;
        min-width: 3rem;
        text-align: center;
    }
</style>
</div>
