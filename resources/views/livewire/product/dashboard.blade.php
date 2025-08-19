@section('title', 'Product Inventory')
<div class="container mx-auto p-6">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                    <i class="fas fa-boxes mr-2"></i>Product Inventory
                </h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">Manage your product inventory and stock levels</p>
            </div>
            <button wire:click="openCreateModal" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-plus"></i>
                Add Product
            </button>
        </div>
    </div>

    {{-- Success Message --}}
    @if (session()->has('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    {{-- Error Message --}}
    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    {{-- Quick Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
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
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Search --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <i class="fas fa-search mr-1"></i>Search
                </label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search products..." class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
            </div>

            {{-- Category Filter --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <i class="fas fa-tags mr-1"></i>Category
                </label>
                <select wire:model.live="categoryFilter" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                    <option value="">All Categories</option>
                    @foreach($categories as $key => $category)
                        <option value="{{ $key }}">{{ $category }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Stock Filter --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    <i class="fas fa-layer-group mr-1"></i>Stock Status
                </label>
                <select wire:model.live="stockFilter" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                    <option value="">All Stock Levels</option>
                    <option value="in_stock">In Stock</option>
                    <option value="low_stock">Low Stock</option>
                    <option value="out_of_stock">Out of Stock</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Products Table --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                {{-- table header --}}
                <thead class="bg-zinc-50 dark:bg-zinc-700">
                    <tr>
                        {{-- product id --}}
                        <th wire:click="sortByField('id')" class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-600">
                            <div class="flex items-center justify-center gap-1">
                                ID
                                @if($sortBy === 'id')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort text-zinc-400"></i>
                                @endif
                            </div>
                        </th>

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
                        <th class="px-3 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                            <div class="flex items-center justify-center gap-1">
                                Actions
                            </div>
                        </th>
                    </tr>
                </thead>

                {{-- table body --}}
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($products as $product)
                        @if (!$product->is_in_stock)
                            <tr class="bg-red-100 dark:bg-red-800 hover:bg-red-200 dark:hover:bg-red-700">
                        @else
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700">
                        @endif

                            {{-- product id --}}
                            <td class="px-6 py-4 text-center">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $product->id }}</div>
                            </td>

                            {{-- product name --}}
                            <td class="px-6 py-4 text-center">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $product->name }}</div>
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
                                    <i class="fas fa-tag mr-1"></i>{{ $product->category_name }}
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
                                @if(!$product->is_in_stock)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-200 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                        <i class="fas fa-ban mr-1"></i>Not Available
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $product->stock_status === 'in_stock' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                        {{ $product->stock_status === 'low_stock' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                        {{ $product->stock_status === 'out_of_stock' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                                    ">
                                        @if($product->stock_status === 'in_stock')
                                            <i class="fas fa-check-circle mr-1"></i>In Stock
                                        @elseif($product->stock_status === 'low_stock')
                                            <i class="fas fa-exclamation-triangle mr-1"></i>Low Stock
                                        @else
                                            <i class="fas fa-times-circle mr-1"></i>Out of Stock
                                        @endif
                                    </span>
                                @endif
                            </td>

                            {{-- action button --}}
                            <td class="px-3 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button wire:click="openEditModal({{ $product->id }})" class="cursor-pointer text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 px-2 py-1 rounded">
                                        <i class="fas fa-edit"></i>Edit
                                    </button>
                                    
                                    <button wire:click="openDeleteModal({{ $product->id }})" class="cursor-pointer text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 px-2 py-1 rounded">
                                        <i class="fas fa-archive"></i>Archive
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center">
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
    @if($showCreateModal)
        <div class="fixed inset-0 bg-zinc-500/80 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                        <i class="fas fa-plus-circle mr-2"></i>Add New Product
                    </h3>
                    <button wire:click="closeCreateModal" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
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
                        <button type="button" wire:click="closeCreateModal" class="cursor-pointer px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                            <i class="fas fa-times mr-1"></i>Cancel
                        </button>
                        <button type="submit" class="cursor-pointer px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-save mr-1"></i>Create Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Edit Product Modal --}}
    @if($showEditModal)
        <div class="fixed inset-0 bg-zinc-500/80 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                        <i class="fas fa-edit mr-2"></i>Edit Product
                    </h3>
                    <button wire:click="closeEditModal" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form wire:submit.prevent="updateProduct" class="p-6 space-y-4">
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
                        <button type="button" wire:click="closeEditModal" class="cursor-pointer px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                            <i class="fas fa-times mr-1"></i>Cancel
                        </button>
                        <button type="submit" class="cursor-pointer px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-save mr-1"></i>Update Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
        <div class="fixed inset-0 bg-zinc-500/80 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                        <i class="fas fa-archive text-red-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 text-center mb-2">Archive Product</h3>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 text-center mb-6">
                        Are you sure you want to archive this product? This will mark it as unavailable for sale.
                    </p>
                    <div class="flex justify-center gap-3">
                        <button wire:click="closeDeleteModal" class="cursor-pointer px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                            <i class="fas fa-times mr-1"></i>Cancel
                        </button>
                        <button wire:click="deleteProduct" class="cursor-pointer px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                            <i class="fas fa-archive mr-1"></i>Archive
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
