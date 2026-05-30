@section('title', __('Product Categories'))

<div class="w-full max-w-full overflow-x-hidden px-2 sm:px-4 pb-8"
     x-data="{
         showCreateModal: false,
         showEditModal: false,
         showDeleteModal: false,
         showProductsModal: false,
         showEditLoading: false,
         showDeleteLoading: false,

         openCreateModal() { this.showCreateModal = true; $wire.openCreateModal(); },
         closeCreateModal() { this.showCreateModal = false; $wire.resetForm(); },
         openEditModal(id) { this.showEditModal = false; this.showEditLoading = true; $wire.openEditModal(id); },
         closeEditModal() { this.showEditModal = false; this.showEditLoading = false; $wire.resetForm(); },
         openDeleteModal(id) { this.showDeleteModal = false; this.showDeleteLoading = true; $wire.openDeleteModal(id); },
         closeDeleteModal() { this.showDeleteModal = false; this.showDeleteLoading = false; $wire.resetForm(); },
         openProductsModal(id) { this.showProductsModal = false; $wire.openProductsModal(id); },
         closeProductsModal() { this.showProductsModal = false; $wire.resetForm(); },
     }"
     @close-create-modal.window="closeCreateModal()"
     @close-edit-modal.window="closeEditModal()"
     @close-delete-modal.window="closeDeleteModal()"
     @products-category-loaded.window="showProductsModal = true"
     @edit-category-loaded.window="showEditLoading = false; showEditModal = true"
     @edit-category-load-failed.window="showEditLoading = false"
     @delete-category-loaded.window="showDeleteLoading = false; showDeleteModal = true"
     @delete-category-load-failed.window="showDeleteLoading = false">

    <div class="flex items-start justify-between gap-3 py-2 mb-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <i class="fas fa-tags text-blue-500"></i>
                {{ __('Product Categories') }}
            </h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                {{ __('Create, rename, and remove product categories without touching the code.') }}
            </p>
        </div>

        <button @click="openCreateModal()"
            class="cursor-pointer inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 active:scale-95 transition-all shadow-md shadow-blue-500/20 shrink-0">
            <i class="fas fa-plus"></i>
            <span>{{ __('Add Category') }}</span>
        </button>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-6 gap-3 mb-4">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-4 flex items-center gap-3 lg:col-span-2">
            <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center shrink-0">
                <i class="fas fa-layer-group text-blue-600 dark:text-blue-400"></i>
            </div>
            <div>
                <div class="text-xl font-bold text-zinc-900 dark:text-zinc-100 leading-none">{{ $kpi['total_categories'] }}</div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('Total Categories') }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-4 flex items-center gap-3 lg:col-span-2">
            <div class="w-10 h-10 rounded-xl bg-green-100 dark:bg-green-900/40 flex items-center justify-center shrink-0">
                <i class="fas fa-boxes-stacked text-green-600 dark:text-green-400"></i>
            </div>
            <div>
                <div class="text-xl font-bold text-zinc-900 dark:text-zinc-100 leading-none">{{ $kpi['active_categories'] }}</div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('Categories in Use') }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center shrink-0">
                <i class="fas fa-question-circle text-amber-600 dark:text-amber-400"></i>
            </div>
            <div>
                <div class="text-xl font-bold text-zinc-900 dark:text-zinc-100 leading-none">{{ $kpi['uncategorized_products'] }}</div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('Uncategorized Products') }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center shrink-0">
                <i class="fas fa-coins text-violet-600 dark:text-violet-400"></i>
            </div>
            <div>
                <div class="text-xl font-bold text-zinc-900 dark:text-zinc-100 leading-none">₱{{ number_format($kpi['total_inventory_value'], 2) }}</div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('Inventory Value') }}</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-4">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-4">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Category Distribution') }}</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Products assigned to each category') }}</p>
                </div>
                <i class="fas fa-chart-column text-blue-500"></i>
            </div>

            @php
                $maxCategoryValue = collect($categoryChart)->max('value') ?: 1;
            @endphp

            <div class="space-y-3">
                @forelse($categoryChart as $item)
                    @php $percent = $maxCategoryValue > 0 ? round(($item['value'] / $maxCategoryValue) * 100) : 0; @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400 mb-1">
                            <span class="truncate pr-3">{{ $item['label'] }}</span>
                            <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ $item['value'] }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-zinc-100 dark:bg-zinc-700 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-cyan-500" style="width: {{ $percent }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-sm text-zinc-400 dark:text-zinc-500">
                        {{ __('No category data yet.') }}
                    </div>
                @endforelse
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-4">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Inventory Value by Category') }}</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Estimated stock value per category') }}</p>
                </div>
                <i class="fas fa-chart-bar text-emerald-500"></i>
            </div>

            @php
                $maxValue = collect($valueChart)->max('value') ?: 1;
            @endphp

            <div class="space-y-3">
                @forelse($valueChart as $item)
                    @php $percent = $maxValue > 0 ? round(($item['value'] / $maxValue) * 100) : 0; @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400 mb-1">
                            <span class="truncate pr-3">{{ $item['label'] }}</span>
                            <span class="font-semibold text-zinc-700 dark:text-zinc-300">₱{{ number_format($item['value'], 2) }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-zinc-100 dark:bg-zinc-700 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-lime-500" style="width: {{ $percent }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-sm text-zinc-400 dark:text-zinc-500">
                        {{ __('No inventory value data yet.') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-4 mb-4">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 items-end">
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                    <i class="fas fa-search mr-1"></i>{{ __('Search Category') }}
                </label>
                <div class="relative">
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="{{ __('Search categories by name or description...') }}"
                           class="w-full pl-3 pr-9 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                    @if($search)
                        <button wire:click="$set('search', '')"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-red-500 transition-colors">
                            <i class="fas fa-times-circle text-sm"></i>
                        </button>
                    @endif
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                    <i class="fas fa-sort mr-1"></i>{{ __('Sort by') }}
                </label>
                <select wire:model.live="sortBy"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition cursor-pointer">
                    <option value="name">{{ __('Name') }}</option>
                    <option value="products_count">{{ __('Products') }}</option>
                    <option value="inventory_value">{{ __('Inventory Value') }}</option>
                    <option value="updated_at">{{ __('Last Updated') }}</option>
                </select>
            </div>
        </div>
    </div>

    @include('livewire.partials.loading-overlay', [
        'wireTarget' => 'openProductsModal',
        'title' => __('Fetching products...'),
        'message' => __('Please wait while we load this category.'),
    ])

    <div x-cloak x-show="showEditLoading" x-transition.opacity class="fixed inset-0 z-[60] flex items-center justify-center px-4 py-6 bg-zinc-950/60">
        <div class="w-full max-w-sm bg-white/90 dark:bg-zinc-800/90 border border-zinc-200 dark:border-zinc-700 rounded-3xl shadow-2xl p-5">
            <div class="flex flex-col items-center gap-3 text-center">
                <div class="relative w-12 h-12 flex items-center justify-center">
                    <div class="absolute inset-0 rounded-full border-4 border-blue-200 dark:border-blue-900"></div>
                    <div class="absolute inset-0 rounded-full border-4 border-transparent border-t-blue-600 dark:border-t-blue-400 animate-spin"></div>
                </div>
                <div>
                    <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Fetching category...') }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Please wait while the edit form is loaded.') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div x-cloak x-show="showDeleteLoading" x-transition.opacity class="fixed inset-0 z-[60] flex items-center justify-center px-4 py-6 bg-zinc-950/60">
        <div class="w-full max-w-sm bg-white/90 dark:bg-zinc-800/90 border border-zinc-200 dark:border-zinc-700 rounded-3xl shadow-2xl p-5">
            <div class="flex flex-col items-center gap-3 text-center">
                <div class="relative w-12 h-12 flex items-center justify-center">
                    <div class="absolute inset-0 rounded-full border-4 border-blue-200 dark:border-blue-900"></div>
                    <div class="absolute inset-0 rounded-full border-4 border-transparent border-t-blue-600 dark:border-t-blue-400 animate-spin"></div>
                </div>
                <div>
                    <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Fetching category...') }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Please wait while the delete confirmation is loaded.') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 lg:hidden">
        @forelse($categories as $category)
            <div wire:key="mobile-category-{{ $category['id'] }}"
                 class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm overflow-hidden">
                <div class="h-1 w-full {{ $category['products_count'] > 0 ? 'bg-blue-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"></div>
                <div class="p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-sm text-zinc-900 dark:text-zinc-100 truncate">{{ $category['name'] }}</p>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">#{{ $category['id'] }}</p>
                        </div>
                        <button type="button"
                                @click="openProductsModal({{ $category['id'] }})"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 shrink-0 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors cursor-pointer"
                                title="{{ __('View products in this category') }}">
                            <i class="fas fa-box text-xs"></i>{{ $category['products_count'] }}
                        </button>
                    </div>

                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $category['description'] ?: __('No description provided.') }}
                    </p>

                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-xl px-3 py-2 text-center">
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('Products') }}</p>
                            <p class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $category['products_count'] }}</p>
                        </div>
                        <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-xl px-3 py-2 text-center">
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('Value') }}</p>
                            <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400">₱{{ number_format($category['inventory_value'], 2) }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5 pt-1 border-t border-zinc-100 dark:border-zinc-700 flex-wrap">
                        <button @click="openEditModal({{ $category['id'] }})"
                            class="prod-card-btn text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20">
                            <i class="fas fa-edit"></i>{{ __('Edit') }}
                        </button>
                        <button @click="openDeleteModal({{ $category['id'] }})"
                            class="prod-card-btn text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 ml-auto">
                            <i class="fas fa-trash"></i>{{ __('Delete') }}
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="sm:col-span-2 flex flex-col items-center justify-center py-20 text-zinc-400 dark:text-zinc-500">
                <i class="fas fa-tags text-5xl mb-4 opacity-40"></i>
                <p class="text-sm">{{ __('No categories found.') }}</p>
                <p class="text-xs mt-1 opacity-70">{{ __('Add your first category to get started.') }}</p>
            </div>
        @endforelse
    </div>

    <div class="hidden lg:block bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-100 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900/60">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider w-16">#</th>
                        <th wire:click="sortByField('name')" class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800 select-none">
                            {{ __('Category') }}
                            @if($sortBy === 'name')
                                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-blue-500 ml-1"></i>
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Description') }}</th>
                        <th wire:click="sortByField('products_count')" class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800 select-none">
                            {{ __('Products') }}
                            @if($sortBy === 'products_count')
                                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-blue-500 ml-1"></i>
                            @endif
                        </th>
                        <th wire:click="sortByField('inventory_value')" class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800 select-none">
                            {{ __('Value') }}
                            @if($sortBy === 'inventory_value')
                                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-blue-500 ml-1"></i>
                            @endif
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Updated') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @forelse($categories as $category)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">

                            {{-- ID --}}
                            <td class="px-4 py-3 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $category['id'] }}
                            </td>

                            {{-- Name --}}
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $category['name'] }}</div>
                            </td>

                            {{-- Description --}}
                            <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $category['description'] ?: __('No description provided.') }}
                            </td>

                            {{-- Product Count --}}
                            <td class="px-4 py-3 text-center">
                                <button type="button"
                                        @click="openProductsModal({{ $category['id'] }})"
                                        class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 min-w-12 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors cursor-pointer"
                                        title="{{ __('View products in this category') }}">
                                    <i class="fas fa-shopping-bag text-[10px] mr-1"></i>
                                    {{ $category['products_count'] }}
                                </button>
                            </td>

                            {{-- Inventory Value --}}
                            <td class="px-4 py-3 text-right text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                ₱{{ number_format($category['inventory_value'], 2) }}
                            </td>

                            {{-- Updated At --}}
                            <td class="px-4 py-3 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                <span title="{{ optional($category['updated_at'])->format('M d, Y h:i A') }}">
                                {{ optional($category['updated_at'])->format('M d, Y') }}
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="openEditModal({{ $category['id'] }})" class="tbl-action-btn text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20">
                                        <i class="fas fa-pen"></i>{{ __('Edit') }}
                                    </button>
                                    <button @click="openDeleteModal({{ $category['id'] }})" class="tbl-action-btn text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20">
                                        <i class="fas fa-trash"></i>{{ __('Delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center text-zinc-400 dark:text-zinc-500">
                                <i class="fas fa-tags text-4xl mb-3 opacity-40"></i>
                                <p class="text-sm">{{ __('No categories found.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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

    <div x-cloak x-show="showProductsModal" x-transition.opacity @click.self="closeProductsModal()" class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 bg-zinc-950/60">
        <div class="w-full max-w-3xl bg-white dark:bg-zinc-800 rounded-3xl shadow-2xl border border-zinc-100 dark:border-zinc-700 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-100 dark:border-zinc-700">
                <div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Products in :name', ['name' => $selectedCategoryName ?: __('this category')]) }}</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Click outside the modal to close it.') }}</p>
                </div>
                <button @click="closeProductsModal()" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-5">
                @if(!empty($selectedCategoryProducts))
                    <div class="space-y-3 max-h-[70vh] overflow-y-auto pr-1">
                        @foreach($selectedCategoryProducts as $product)
                            <div class="flex items-center justify-between gap-3 rounded-2xl border border-zinc-100 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-700/40 px-4 py-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-zinc-900 dark:text-zinc-100 truncate">{{ $product['name'] }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">#{{ $product['id'] }}</p>
                                </div>

                                <div class="flex items-center gap-2 shrink-0 text-sm">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 dark:bg-zinc-700 px-2.5 py-1 text-zinc-700 dark:text-zinc-200">
                                        <i class="fas fa-box text-[10px]"></i>
                                        {{ $product['stocks'] }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 dark:bg-emerald-900/30 px-2.5 py-1 text-emerald-700 dark:text-emerald-300 font-semibold">
                                        ₱{{ number_format($product['price'], 2) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-10 text-center text-zinc-400 dark:text-zinc-500">
                        <i class="fas fa-box-open text-4xl mb-3 opacity-40"></i>
                        <p class="text-sm">{{ __('No products are assigned to this category yet.') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div x-cloak x-show="showCreateModal" x-transition.opacity @click.self="closeCreateModal()" class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 bg-zinc-950/60">
        <div class="w-full max-w-2xl bg-white dark:bg-zinc-800 rounded-3xl shadow-2xl border border-zinc-100 dark:border-zinc-700 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-100 dark:border-zinc-700">
                <div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Create Category') }}</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Add a new category for products.') }}</p>
                </div>
                <button @click="closeCreateModal()" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form wire:submit.prevent="createCategory" class="p-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 mb-1.5">{{ __('Category Name') }}</label>
                    <input type="text" wire:model.live="name" class="w-full px-3 py-2 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition" placeholder="{{ __('e.g. Beverages') }}">
                    @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 mb-1.5">{{ __('Description') }}</label>
                    <textarea wire:model.live="description" rows="4" class="w-full px-3 py-2 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition" placeholder="{{ __('Optional category notes or instructions...') }}"></textarea>
                    @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="closeCreateModal()" class="px-4 py-2 rounded-xl border border-zinc-200 dark:border-zinc-600 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition-colors shadow-md shadow-blue-500/20">
                        {{ __('Save Category') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div x-cloak x-show="showEditModal" x-transition.opacity @click.self="closeEditModal()" class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 bg-zinc-950/60">
        <div class="w-full max-w-2xl bg-white dark:bg-zinc-800 rounded-3xl shadow-2xl border border-zinc-100 dark:border-zinc-700 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-100 dark:border-zinc-700">
                <div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Edit Category') }}</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Renaming a category updates matching products automatically.') }}</p>
                </div>
                <button @click="closeEditModal()" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form wire:submit.prevent="updateCategory" class="p-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 mb-1.5">{{ __('Category Name') }}</label>
                    <input type="text" wire:model.live="name" class="w-full px-3 py-2 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                    @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 mb-1.5">{{ __('Description') }}</label>
                    <textarea wire:model.live="description" rows="4" class="w-full px-3 py-2 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition"></textarea>
                    @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="closeEditModal()" class="px-4 py-2 rounded-xl border border-zinc-200 dark:border-zinc-600 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition-colors shadow-md shadow-blue-500/20">
                        {{ __('Update Category') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div x-cloak x-show="showDeleteModal" x-transition.opacity @click.self="closeDeleteModal()" class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 bg-zinc-950/60">
        <div class="w-full max-w-lg bg-white dark:bg-zinc-800 rounded-3xl shadow-2xl border border-zinc-100 dark:border-zinc-700 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-100 dark:border-zinc-700">
                <div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Delete Category') }}</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('This is only allowed when no products are assigned to it.') }}</p>
                </div>
                <button @click="closeDeleteModal()" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-5 space-y-4">
                <div class="rounded-2xl border border-red-200 dark:border-red-900/40 bg-red-50 dark:bg-red-900/20 p-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/40 flex items-center justify-center shrink-0">
                            <i class="fas fa-triangle-exclamation text-red-600 dark:text-red-400"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-red-800 dark:text-red-200">{{ __('Delete :name?', ['name' => $selectedCategoryName ?: __('this category')]) }}</p>
                            <p class="text-sm text-red-700/80 dark:text-red-200/80 mt-1">{{ __('Products will keep their current category label until you reassign them, so only delete empty categories.') }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <button type="button" @click="closeDeleteModal()" class="px-4 py-2 rounded-xl border border-zinc-200 dark:border-zinc-600 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        {{ __('Cancel') }}
                    </button>
                    <button wire:click="deleteCategory" class="px-4 py-2 rounded-xl bg-red-600 text-white text-sm font-semibold hover:bg-red-700 transition-colors shadow-md shadow-red-500/20">
                        {{ __('Delete Category') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
