@section('title', __('Product Inventory'))

<div class="w-full max-w-full overflow-x-hidden px-2 sm:px-4 pb-8"
    x-data="{
        showFormModal: false,
        formMode: 'create',
        showArchiveModal: false,
        showDeleteModal: false,
        editLoading: false,

        openCreateModal() {
            this.formMode = 'create';
            $wire.openCreateModal();
            this.showFormModal = true;
        },
        openEditModal(id) {
            this.formMode = 'edit';
            this.editLoading = true;
            this.showFormModal = true;
            $wire.openEditModal(id);
        },
        closeFormModal() {
            this.showFormModal = false;
            this.editLoading = false;
            $wire.resetForm();
        },

        openRestockModal(id) {
            Livewire.dispatch('open-restock-modal', { id: id });
        },
    }"

    @close-form-modal.window="closeFormModal()"
    @edit-product-loaded.window="editLoading = false"
    >

    {{-- HEADER --}}
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

    {{-- QUICK STATS  (2×2 on mobile, 4-col on md+) --}}
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

    {{-- FILTERS & SEARCH --}}
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
                           placeholder="{{ __('Search products') }}"
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
            <div class="flex items-end lg:col-span-1 lg:justify-end">
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
        'wireTarget' => 'categoryFilter,stockFilter,search,sortByField,openEditModal,openDeleteModal,save,archiveProduct,deleteProduct,makeAvailable',
        'title' => __('Updating...'),
        'message' => __('Please wait while we process your request'),
    ])

    @php
        $categoryNames = \App\Models\Product::getCategories();
    @endphp

    {{-- PRODUCT LIST --}}
    {{-- ── Mobile Cards (< lg) ── --}}
    @include('livewire.partials.products.orientation.mobile')

    {{-- ── Desktop Table (≥ lg) ── --}}
    @include('livewire.partials.products.orientation.desktop')

    {{-- Mobile pagination --}}
    <div class="lg:hidden mt-3">
        @if($products->hasPages())
            {{ $products->links() }}
        @endif
    </div>

    {{-- MODALS --}}
    @include('livewire.partials.products.modal.form')
    <livewire:partials.products.modal.restock />

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
