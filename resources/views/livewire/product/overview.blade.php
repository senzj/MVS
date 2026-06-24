@section('title', __('Product') . " | " . $product->name)

<div class="w-full max-w-full overflow-x-hidden px-2 sm:px-4 pb-8"
    x-data="{
        showFormModal: false,
        formMode: 'edit',
        editLoading: false,
        showArchiveModal: false,
        showDeleteModal: false,

        openEditModal() {
            this.editLoading = true;
            $wire.openEditModal();
            this.showFormModal = true;
        },
        closeFormModal() {
            this.showFormModal = false;
            this.editLoading = false;
        },
        openRestockModal(id) {
            Livewire.dispatch('open-restock-modal', { id: id });
        },
        openArchiveModal() { this.showArchiveModal = true; },
        closeArchiveModal() { this.showArchiveModal = false; },
        openDeleteModal() { this.showDeleteModal = true; },
        closeDeleteModal() { this.showDeleteModal = false; },
    }"
    @close-form-modal.window="closeFormModal()"
    @close-archive-modal.window="closeArchiveModal()"
    @close-delete-modal.window="closeDeleteModal()"
    @edit-product-loaded.window="editLoading = false">

    {{-- Back --}}
    <div class="mb-4">
        <a href="{{ route('products') }}" wire:navigate
           class="inline-flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200">
            <i class="fas fa-arrow-left"></i>{{ __('Back to Products') }}
        </a>
    </div>

    @php
        $isOutOfStock = empty($product->stocks) || (int) $product->stocks === 0;
        $catLabel = $categories[$product->category] ?? __('Uncategorized');
        $profit = (float) $product->price - (float) ($product->cost ?? 0);
        $margin = ($product->price > 0 && ($product->cost ?? 0) > 0) ? ($profit / $product->price) * 100 : null;
        $loc = app()->getLocale() === 'cn' ? 'zh_CN' : app()->getLocale();
    @endphp

    {{-- HEADER CARD --}}
    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-5 mb-4">
        <div class="flex flex-col sm:flex-row gap-5">

            <div class="w-32 h-32 rounded-2xl overflow-hidden border border-zinc-100 dark:border-zinc-700 bg-white/60 dark:bg-zinc-900/40 flex items-center justify-center shrink-0 mx-auto sm:mx-0">
                @if($product->image_url)
                    <img src="{{ $product->image_url }}" class="w-full h-full object-cover">
                @else
                    <i class="fas fa-box text-3xl text-zinc-300 dark:text-zinc-500"></i>
                @endif
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div>
                        <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ $product->name }}</h2>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">{{ __('ID') }}: {{ $product->id }}</p>
                    </div>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                            <i class="fas fa-tag text-xs"></i>{{ __($catLabel) }}
                        </span>
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
                </div>

                @if($product->description)
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-2">{{ $product->description }}</p>
                @endif

                {{-- Actions --}}
                <div class="flex items-center gap-2 mt-4 flex-wrap">
                    <button @click="openRestockModal({{ $product->id }})"
                        class="prod-card-btn bg-green-50 text-green-700 hover:bg-green-100 dark:bg-green-900/20 dark:text-green-400">
                        <i class="fas fa-truck-ramp-box"></i>{{ __('Restock') }}
                    </button>

                    <button @click="openEditModal()"
                        class="prod-card-btn bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400">
                        <i class="fas fa-edit"></i>{{ __('Edit') }}
                    </button>

                    @if($product->is_in_stock)
                        <button @click="openArchiveModal()"
                            class="prod-card-btn bg-yellow-50 text-yellow-700 hover:bg-yellow-100 dark:bg-yellow-900/20 dark:text-yellow-400">
                            <i class="fas fa-eye-slash"></i>{{ __('Hide') }}
                        </button>
                    @elseif($isOutOfStock)
                        <span class="prod-card-btn text-zinc-400 opacity-50 cursor-not-allowed">
                            <i class="fas fa-ban"></i>{{ __('Out of Stock') }}
                        </span>
                    @else
                        <button wire:click="makeAvailable"
                            class="prod-card-btn bg-orange-50 text-orange-700 hover:bg-orange-100 dark:bg-orange-900/20 dark:text-orange-400">
                            <i class="fas fa-eye"></i>{{ __('Show') }}
                        </button>
                    @endif

                    @if(($product->order_items_count ?? 0) === 0)
                        <button @click="openDeleteModal()"
                            class="prod-card-btn bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400">
                            <i class="fas fa-trash"></i>{{ __('Delete') }}
                        </button>
                    @else
                        <span class="prod-card-btn text-zinc-400 opacity-50 cursor-not-allowed" title="{{ __('Cannot delete - has ongoing order') }}">
                            <i class="fas fa-ban"></i>{{ __('Pending Orders') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- STATS --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">

        {{-- Price --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Price') }}</p>
            <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100 mt-0.5">{{ config('storeconfig.currency_symbol') }}{{ number_format($product->price, 2) }}</p>
        </div>

        {{-- Stock --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Stock') }}</p>
            <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100 mt-0.5">{{ number_format($product->stocks) }}</p>
        </div>

        {{-- Average Cost --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Average Cost') }}</p>
            <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100 mt-0.5">{{ config('storeconfig.currency_symbol') }}{{ number_format($product->cost ?? 0, 2) }}</p>
        </div>

        {{-- Profit / Margin --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Profit / Margin') }}</p>
            <p class="text-xl font-bold {{ $profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} mt-0.5">
                {{ config('storeconfig.currency_symbol') }}{{ number_format($profit, 2) }}
                @if($margin !== null)<span class="text-xs font-normal text-zinc-400">({{ number_format($margin, 1) }}%)</span>@endif
            </p>
        </div>

        {{-- Total Sold --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Total Sold') }}</p>
            <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100 mt-0.5">{{ number_format($product->sold) }}</p>
        </div>

        {{-- Total Restocked --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Total Restocked') }}</p>
            <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100 mt-0.5">{{ number_format($totalRestocked) }}</p>
        </div>

        {{-- Total Spend on Restocks this Month --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-4 col-span-2">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Total Spend on Restocks this Month') }}</p>
            <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100 mt-0.5">{{ config('storeconfig.currency_symbol') }}{{ number_format($totalSpent, 2) }}</p>
        </div>

        {{-- Total Spend on Restocks this Year --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-4 col-span-2">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Total Spend on Restocks this Year') }}</p>
            <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100 mt-0.5">{{ config('storeconfig.currency_symbol') }}{{ number_format($totalSpent, 2) }}</p>
        </div>
    </div>

    {{-- RESTOCK HISTORY --}}
    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm overflow-hidden mb-4">
        <div class="px-4 py-3 border-b border-zinc-100 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-900/50">
            <h3 class="text-sm text-center font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Restock History') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-100 dark:divide-zinc-700 text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900/60">
                    <tr>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-zinc-500 uppercase">{{ __('Date') }}</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-zinc-500 uppercase">{{ __('Qty') }}</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-zinc-500 uppercase">{{ __('Unit Cost') }}</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-zinc-500 uppercase">{{ __('Total') }}</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-zinc-500 uppercase">{{ __('Added By') }}</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-zinc-500 uppercase">{{ __('Notes') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @forelse($restocks as $r)
                        <tr>
                            {{-- Date --}}
                            <td class="px-4 py-2.5 text-center text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                                {{ $r->created_at->locale($loc)->isoFormat('LL | hh:mm:ss A') }}
                            </td>

                            {{-- Quantity --}}
                            <td class="px-4 py-2.5 text-center font-medium">
                                {{ $r->quantity }} {{ $r->unit_type }}
                            </td>

                            {{-- Unit Cost --}}
                            <td class="px-4 py-2.5 text-center">
                                {{ config('storeconfig.currency_symbol') }}{{ number_format($r->unit_cost, 2) }}
                            </td>

                            {{-- Total Cost --}}
                            <td class="px-4 py-2.5 text-center font-semibold">
                                {{ config('storeconfig.currency_symbol') }}{{ number_format($r->total_cost, 2) }}
                            </td>

                            {{-- Added By --}}
                            <td class="px-4 py-2.5 text-center text-zinc-500">
                                {{ $r->user_name ?: __('System') }}
                            </td>

                            {{-- Notes --}}
                            <td class="px-4 py-2.5 text-center text-zinc-500">
                                {{ $r->notes ?: '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-400 text-sm">{{ __('No restocks recorded yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($restocks->hasPages())
            <div class="px-4 py-3 border-t border-zinc-100 dark:border-zinc-700">{{ $restocks->links() }}</div>
        @endif
    </div>

    {{-- MOVEMENT / SOLD HISTORY --}}
    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm overflow-hidden mb-4">
        <div class="px-4 py-3 border-b border-zinc-100 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-900/50">
            <h3 class="text-sm text-center font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Stock Movement History') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-100 dark:divide-zinc-700 text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900/60">
                    <tr>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-zinc-500 uppercase">{{ __('Date') }}</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-zinc-500 uppercase">{{ __('Movement Type') }}</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-zinc-500 uppercase">{{ __('Qty') }}</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-zinc-500 uppercase">{{ __('Stock') }}</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-zinc-500 uppercase">{{ __('Added By') }}</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-zinc-500 uppercase">{{ __('Notes') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @forelse($movements as $m)
                        <tr>
                            <td class="px-4 py-2.5 text-center text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                                {{ $m->created_at->locale($loc)->isoFormat('LL | hh:mm:ss A') }}
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">
                                    {{ __(str_replace('_', ' ', ucfirst($m->type))) }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-center font-medium">{{ $m->quantity }}</td>
                            <td class="px-4 py-2.5 text-center text-zinc-500">{{ $m->before_stocks }} → {{ $m->after_stocks }}</td>
                            <td class="px-4 py-2.5 text-center text-zinc-500">{{ $m->user_name ?: __('System') }}</td>
                            <td class="px-4 py-2.5 text-center text-zinc-500">{{ $m->remarks ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-400 text-sm">{{ __('No movements recorded yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($movements->hasPages())
            <div class="px-4 py-3 border-t border-zinc-100 dark:border-zinc-700">{{ $movements->links() }}</div>
        @endif
    </div>

    {{-- MODALS --}}
    @include('livewire.partials.products.modal.form')
    @include('livewire.partials.products.modal.archive')
    @include('livewire.partials.products.modal.delete')
    <livewire:partials.products.modal.restock />

    {{-- Loading Overlay --}}
    @include('livewire.partials.loading-overlay', [
        'wireTarget' => 'save,archiveProduct,deleteProduct,makeAvailable,image',
        'title'      => __('Updating...'),
        'message'    => __('Please wait while we process your request'),
    ])

<style>
    .prod-card-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.5rem 0.875rem;
        border-radius: 0.625rem;
        font-size: 0.8125rem;
        font-weight: 500;
        transition: background-color 0.15s;
        cursor: pointer;
        white-space: nowrap;
    }
</style>
</div>
