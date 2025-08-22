@section('title', 'Product Inventory')
<div class="container mx-auto p-6" x-data="{ 
    showCreateModal: false, 
    showEditModal: false, 
    showDeleteModal: false,
    showArchiveModal: false,
    
    openCreateModal() {
        $wire.openCreateModal();
        this.showCreateModal = true;
    },
    closeCreateModal() {
        this.showCreateModal = false;
        $wire.resetForm();
    },
    openEditModal(productId) {
        $wire.openEditModal(productId);
        this.showEditModal = true;
    },
    closeEditModal() {
        this.showEditModal = false;
        $wire.resetForm();
    },
    openDeleteModal(productId) {
        $wire.openDeleteModal(productId);
        this.showDeleteModal = true;
    },
    closeDeleteModal() {
        this.showDeleteModal = false;
        $wire.selectedProductId = null;
    },
    openArchiveModal(productId) {
        $wire.openArchiveModal(productId);
        this.showArchiveModal = true;
    },
    closeArchiveModal() {
        this.showArchiveModal = false;
        $wire.selectedProductId = null;
    },
}" 
@close-create-modal.window="closeCreateModal()"
@close-edit-modal.window="closeEditModal()"
@close-delete-modal.window="closeDeleteModal()"
@close-archive-modal.window="closeArchiveModal()"
>
    {{-- Header --}}
    <div class="mb-3">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                    <i class="fas fa-boxes mr-2"></i>Product Inventory
                </h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">Manage your product inventory and stock levels</p>
            </div>
            <button @click="openCreateModal()" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-plus"></i>
                Add Product
            </button>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-3">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                    <i class="fas fa-box text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $products->total() }}</div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">Total Products</div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                        {{ $allProducts->where('stocks', '>=', 10)->count() }}
                    </div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">In Stock</div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                    <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                        {{ $allProducts->where('stocks', '<', 10)->where('stocks', '>', 0)->count() }}
                    </div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">Low Stock</div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 dark:bg-red-900">
                    <i class="fas fa-times-circle text-red-600 dark:text-red-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                        {{ $allProducts->where('stocks', '==', 0)->count() }}
                    </div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">Out of Stock</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters and Search --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4 mb-3">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            {{-- Search --}}
            <div>
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    <i class="fas fa-search mr-1 text-xs"></i>Search
                </label>
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search products" class="w-full px-2 py-1.5 pr-8 text-sm border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                    
                    {{-- Enhanced Clear Button --}}
                    @if($search || $categoryFilter !== 'all' || $stockFilter)
                        <button wire:click="clearSearch" class="absolute right-1 top-1/2 transform -translate-y-1/2 p-1.5 text-zinc-500 hover:text-red-600 dark:hover:text-red-400 rounded-full transition-all duration-200" title="Clear search and filters">
                            <i class="fas fa-times-circle text-sm"></i>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Category Filter - FIXED --}}
            <div>
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    <i class="fas fa-tags mr-1 text-xs"></i>Category ({{ $categoryFilter }})
                </label>
                <select wire:model.live.debounce.300ms="categoryFilter" 
                        wire:key="category-filter-{{ $categoryFilter }}"
                        class="w-full px-2 py-1.5 text-sm border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    <option value="all">All Categories</option>
                    @foreach($categories as $key => $category)
                        <option value="{{ $key }}">{{ $category }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Stock Filter --}}
            <div>
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    <i class="fas fa-layer-group mr-1 text-xs"></i>Stock
                </label>
                <select wire:model.live.debounce.300ms="stockFilter" class="w-full px-2 py-1.5 text-sm border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Levels</option>
                    <option value="in_stock">In Stock</option>
                    <option value="low_stock">Low Stock</option>
                    <option value="out_of_stock">Out of Stock</option>
                    <option value="available">Available</option>
                    <option value="hidden">Hidden</option>
                </select>
            </div>

            {{-- Search Results Info --}}
            <div class="flex items-end">
                @if($search || $categoryFilter !== 'all' || $stockFilter)
                    <div class="text-xs text-zinc-600 dark:text-zinc-400 px-2 py-1.5">
                        <i class="fas fa-filter mr-1"></i>
                        Filtered: {{ $products->total() }} results
                    </div>
                @else
                    <div class="text-xs text-zinc-600 dark:text-zinc-400 px-2 py-1.5">
                        <i class="fas fa-list mr-1"></i>
                        Total: {{ $products->total() }} products
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Loading Indicator --}}
    {{-- <div wire:loading wire:target="categoryFilter,stockFilter,search,sortByField" 
         class="flexed bg-white dark:bg-zinc-800 text-gray-600 dark:text-white px-4 py-2 rounded-lg shadow-lg z-50">
        <i class="fas fa-spinner fa-spin mr-2"></i>Updating...
    </div> --}}

    {{-- Products Table - FIXED with proper wire:key --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">

        {{-- Loading Indicator --}}
        <div wire:loading wire:target="categoryFilter,stockFilter,search,sortByField" 
            class="absolute bottom-1/4 right-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 z-50">
            <div class="w-40 h-12 bg-white dark:bg-zinc-700 text-gray-600 dark:text-white px-6 py-3 rounded-lg shadow-lg">
                <i class="fas fa-spinner fa-spin mr-2 text-blue-500"></i>Updating...
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full" wire:key="products-table-{{ $categoryFilter }}-{{ $stockFilter }}-{{ $search }}">
                {{-- table header --}}
                <thead class="bg-zinc-50 dark:bg-zinc-700">
                    <tr>
                        {{-- product name --}}
                        <th wire:click="sortByField('name')" class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-600">
                            <div class="flex items-center justify-center gap-1">
                                Product Name
                                @if($sortBy === 'name')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort text-zinc-400"></i>
                                @endif
                            </div>
                        </th>

                        {{-- product description --}}
                        <th class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            <div class="flex items-center justify-center gap-1">
                                Description
                            </div>
                        </th>

                        {{-- product category --}}
                        <th class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            <div class="flex items-center justify-center gap-1">
                                Category
                            </div>
                        </th>

                        {{-- product price --}}
                        <th wire:click="sortByField('price')" class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-600">
                            <div class="flex items-center justify-center gap-1">
                                Price
                                @if($sortBy === 'price')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort text-zinc-400"></i>
                                @endif
                            </div>
                        </th>

                        {{-- product sold --}}
                        <th wire:click="sortByField('sold')" class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-600">
                            <div class="flex items-center justify-center gap-1">
                                Sold
                                @if($sortBy === 'sold')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort text-zinc-400"></i>
                                @endif
                            </div>
                        </th>

                        {{-- product stock --}}
                        <th wire:click="sortByField('stocks')" class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-600">
                            <div class="flex items-center justify-center gap-1">
                                Stock
                                @if($sortBy === 'stocks')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort text-zinc-400"></i>
                                @endif
                            </div>
                        </th>

                        {{-- product status --}}
                        <th class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            <div class="flex items-center justify-center gap-1">
                                Status
                            </div>
                        </th>

                        {{-- action button --}}
                        <th class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            <div class="flex items-center justify-center gap-1">
                                Actions
                            </div>
                        </th>
                    </tr>
                </thead>

                {{-- table body - FIXED with unique wire:key for each row --}}
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700" wire:loading.class="opacity-40 pointer-events-none">
                    @forelse($products as $index => $product)
                        <tr wire:key="product-row-{{ $product->id }}-{{ $categoryFilter }}-{{ $stockFilter }}-{{ $search }}-{{ $index }}"
                            class="{{ !$product->is_in_stock ? 'bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30' : 'hover:bg-zinc-50 dark:hover:bg-zinc-700' }}">

                            {{-- product name & id --}}
                            <td class="px-6 py-4 text-center">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $product->name }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">ID: {{ $product->id }}</div>
                            </td>

                            {{-- product description --}}
                            <td class="px-6 py-4 text-left">
                                <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ Str::limit($product->description, 30) ?: 'No description' }}
                                </div>
                            </td>

                            {{-- product category --}}
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    <i class="fas fa-tag mr-1"></i>
                                    @php
                                        $categoryNames = \App\Models\Product::getCategories();
                                        echo $categoryNames[$product->category] ?? ucfirst($product->category ?? 'Other');
                                    @endphp
                                </span>
                            </td>

                            {{-- product price --}}
                            <td class="px-6 py-4 text-center">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">₱{{ number_format($product->price, 2) }}</div>
                            </td>

                            {{-- products sold --}}
                            <td class="px-6 py-4 text-center">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    <i class="fas fa-chart-bar mr-1 text-green-600"></i>{{ $product->sold }}
                                </div>
                            </td>

                            {{-- product stocks --}}
                            <td class="px-6 py-4 text-center">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    <i class="fas fa-boxes mr-1 text-blue-600"></i>{{ $product->stocks }}
                                </div>
                            </td>

                            {{-- product stock status --}}
                            <td class="px-6 py-4 text-center">
                                @php
                                    $isOutOfStock = empty($product->stocks) || (int) $product->stocks === 0;
                                @endphp

                                @if($isOutOfStock)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        <i class="fas fa-times-circle mr-1"></i>Out of Stock
                                    </span>
                                @elseif(!$product->is_in_stock)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-200 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                        <i class="fas fa-ban mr-1"></i>Unavailable
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $product->stock_status === 'in_stock' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                        {{ $product->stock_status === 'low_stock' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}">
                                        @if($product->stock_status === 'in_stock')
                                            <i class="fas fa-check-circle mr-1"></i>In Stock
                                        @elseif($product->stock_status === 'low_stock')
                                            <i class="fas fa-exclamation-triangle mr-1"></i>Low Stock
                                        @endif
                                    </span>
                                @endif
                            </td>

                            {{-- action button --}}
                            <td class="px-6 py-2 text-center">
                                <div class="flex items-center justify-center gap-1 min-w-[180px]">
                                    
                                    {{-- Availability Toggle Button --}}
                                    @if ($product->is_in_stock)
                                        <button @click="openArchiveModal({{ $product->id }})"
                                            class="cursor-pointer inline-flex flex-col items-center gap-0.5 px-2 py-1.5 text-xs font-medium 
                                                text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 
                                                hover:bg-green-100 dark:hover:bg-green-900/20 rounded-md transition-colors 
                                                w-[60px] text-center"
                                            title="This Item is Currently for Sale - Mark as Unavailable">
                                            <i class="fas fa-eye-slash text-sm"></i>
                                            <span class="text-[15px] leading-tight">Hide</span>
                                        </button>
                                    @else
                                        @if ($isOutOfStock)
                                            <div class="cursor-not-allowed inline-flex flex-col items-center gap-0.5 px-2 py-1.5 text-xs font-medium 
                                                        text-zinc-400 dark:text-zinc-600 w-[60px] text-center"
                                                title="This product is out of stock. Edit to add more stocks.">
                                                <i class="fas fa-ban text-sm"></i>
                                                <span class="text-[14px] leading-tight">Out Of Stock</span>
                                            </div>
                                        @else
                                            <button wire:click="makeAvailable({{ $product->id }})"
                                                class="cursor-pointer inline-flex flex-col items-center gap-0.5 px-2 py-1.5 text-xs font-medium 
                                                    text-orange-600 hover:text-orange-900 dark:text-orange-400 dark:hover:text-orange-300 
                                                    hover:bg-orange-100 dark:hover:bg-orange-900/20 rounded-md transition-colors 
                                                    w-[60px] text-center"
                                                title="This Item is Currently Hidden - Make Available for Sale">
                                                <i class="fas fa-eye text-sm"></i>
                                                <span class="text-[15px] leading-tight">Show</span>
                                            </button>
                                        @endif
                                    @endif
                                    
                                    {{-- Edit Button --}}
                                    <button @click="openEditModal({{ $product->id }})"
                                        class="cursor-pointer inline-flex flex-col items-center gap-0.5 px-2 py-1.5 text-xs font-medium 
                                            text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 
                                            hover:bg-blue-100 dark:hover:bg-blue-900/20 rounded-md transition-colors 
                                            w-[60px] text-center"
                                        title="Edit Product">
                                        <i class="fas fa-edit text-sm"></i>
                                        <span class="text-[15px] leading-tight">Edit</span>
                                    </button>
                                    
                                    {{-- Delete Button (only for products with no order history) --}}
                                    @if($product->orderItems()->count() === 0)
                                        <button @click="openDeleteModal({{ $product->id }})"
                                            class="cursor-pointer inline-flex flex-col items-center gap-0.5 px-2 py-1.5 text-xs font-medium 
                                                text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 
                                                hover:bg-red-100 dark:hover:bg-red-900/20 rounded-md transition-colors 
                                                w-[60px] text-center"
                                            title="Delete Permanently">
                                            <i class="fas fa-trash text-sm"></i>
                                            <span class="text-[15px] leading-tight">Delete</span>
                                        </button>
                                    @else
                                        <div class="inline-flex flex-col items-center gap-0.5 px-2 py-1.5 text-xs font-medium 
                                                    text-zinc-400 dark:text-zinc-600 w-[60px] text-center"
                                            title="Cannot delete - has order history">
                                            <i class="fas fa-ban text-sm"></i>
                                            <span class="text-[15px] leading-tight">Pending</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr wire:key="no-products-{{ $categoryFilter }}-{{ $stockFilter }}-{{ $search }}">
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="text-zinc-500 dark:text-zinc-400">
                                    <i class="fas fa-box-open text-4xl mb-4"></i>
                                    <p class="text-sm">No products found.</p>
                                    <p class="text-xs mt-1">Try adjusting your search or filter criteria.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
            <div class="px-6 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    {{-- Create Product Modal --}}
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 bg-zinc-500/80 flex items-center justify-center p-4 z-50">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-zinc-200 dark:border-zinc-700">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                    <i class="fas fa-plus-circle mr-2"></i>Add New Product
                </h3>
                <button @click="closeCreateModal()" class="cursor-pointer text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form wire:submit.prevent="createProduct" class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Product Name --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            <i class="fas fa-tag mr-1"></i>Product Name
                        </label>
                        <input type="text" wire:model="name" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                        @error('name') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                    </div>

                    {{-- Category --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            <i class="fas fa-folder mr-1"></i>Category
                        </label>
                        <select wire:model="category" class="cursor-pointer w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                            <option value="">Select Category</option>
                            @foreach($categories as $key => $categoryName)
                                <option value="{{ $key }}">{{ $categoryName }}</option>
                            @endforeach
                        </select>
                        @error('category') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                    </div>

                    {{-- Price --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            <i class="fas fa-peso-sign mr-1"></i>Price <small>(per unit/kg)</small>
                        </label>
                        <input type="number" step="0.01" wire:model="price" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                        @error('price') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                    </div>

                    {{-- Initial Stock --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            <i class="fas fa-cubes mr-1"></i>Stocks
                        </label>
                        <input type="number" wire:model="stocks" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                        @error('stocks') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                    </div>

                    {{-- In Stock Status --}}
                    <div class="flex items-center md:col-span-2">
                        <input type="checkbox" wire:model="is_in_stock" class="h-4 w-4 text-blue-600 border-zinc-300 rounded">
                        <label class="ml-2 block text-sm text-zinc-900 dark:text-zinc-100">
                            <i class="fas fa-check-circle mr-1"></i>Available for Sale
                        </label>
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <i class="fas fa-align-left mr-1"></i>Description <small>(optional)</small>
                    </label>
                    <textarea wire:model="description" rows="3" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100"></textarea>
                    @error('description') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="closeCreateModal()" class="cursor-pointer px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                        <i class="fas fa-times mr-1"></i>Cancel
                    </button>
                    <button type="submit" class="cursor-pointer px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-save mr-1"></i>Create Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Product Modal --}}
    <div x-show="showEditModal" x-cloak class="fixed inset-0 bg-zinc-500/80 flex items-center justify-center p-4 z-50">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-zinc-200 dark:border-zinc-700">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                    <i class="fas fa-edit mr-2"></i>Edit Product
                </h3>
                <button @click="closeEditModal()" class="cursor-pointer text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form wire:submit.prevent="updateProduct" @submit="closeEditModal()" class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Product Name --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            <i class="fas fa-tag mr-1"></i>Product Name
                        </label>
                        <input type="text" wire:model="name" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                        @error('name') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                    </div>

                    {{-- Category --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            <i class="fas fa-folder mr-1"></i>Category
                        </label>
                        <select wire:model="category" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                            <option value="">Select Category</option>
                            @foreach($categories as $key => $categoryName)
                                <option value="{{ $key }}">{{ $categoryName }}</option>
                            @endforeach
                        </select>
                        @error('category') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                    </div>

                    {{-- Price --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            <i class="fas fa-peso-sign mr-1"></i>Price <small>(per unit/kg)</small>
                        </label>
                        <input type="number" step="0.01" wire:model="price" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                        @error('price') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                    </div>

                    {{-- Stock --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            <i class="fas fa-cubes mr-1"></i>Current Stock
                        </label>
                        <input type="number" wire:model="stocks" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                        @error('stocks') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                    </div>

                    {{-- Sold --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            <i class="fas fa-chart-line mr-1"></i>Items Sold
                        </label>
                        <input type="number" wire:model="sold" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                        @error('sold') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                    </div>

                    {{-- In Stock Status --}}
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="is_in_stock" class="h-4 w-4 text-blue-600 border-zinc-300 rounded">
                        <label class="ml-2 block text-sm text-zinc-900 dark:text-zinc-100">
                            <i class="fas fa-check-circle mr-1"></i>Available for Sale
                        </label>
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        <i class="fas fa-align-left mr-1"></i>Description <small>(optional)</small>
                    </label>
                    <textarea wire:model="description" rows="3" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100"></textarea>
                    @error('description') <span class="text-red-500 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="closeEditModal()" class="cursor-pointer px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                        <i class="fas fa-times mr-1"></i>Cancel
                    </button>
                    <button type="submit" class="cursor-pointer px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-save mr-1"></i>Update Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Archive Confirmation Modal --}}
    <div x-show="showArchiveModal" x-cloak class="fixed inset-0 bg-zinc-500/80 flex items-center justify-center p-4 z-50">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                    <i class="fas fa-archive text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 text-center mb-2">Archive Product</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 text-center mb-6">
                    Are you sure you want to Hide this product? This will mark it as unavailable for sale.
                </p>
                <div class="flex justify-center gap-3">
                    <button @click="closeArchiveModal()" class="cursor-pointer px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                        <i class="fas fa-times mr-1"></i>Cancel
                    </button>
                    <button wire:click="archiveProduct" class="cursor-pointer px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                        <i class="fas fa-archive mr-1"></i>Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Confirm Modal --}}
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 bg-zinc-500/80 flex items-center justify-center p-4 z-50">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                    <i class="fas fa-trash text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 text-center mb-2">Delete Product</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 text-center mb-6">
                    Are you sure you want to delete this product? This action cannot be undone.
                </p>
                <div class="flex justify-center gap-3">
                    <button @click="closeDeleteModal()" class="cursor-pointer px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                        <i class="fas fa-times mr-1"></i>Cancel
                    </button>
                    <button wire:click="deleteProduct" @click="closeDeleteModal()" class="cursor-pointer px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                        <i class="fas fa-trash mr-1"></i>Confirm Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>