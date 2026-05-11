@section('title', __('Inventory Audit'))

<div class="w-full max-w-full overflow-x-hidden px-2 sm:px-4 pb-8">
    <div class="flex items-start justify-between gap-3 py-2 mb-5">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <i class="fas fa-chart-line text-blue-500"></i>
                {{ __('Inventory Audit Logs') }}
            </h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Track every stock increase, decrease, refund, and restock in one place.') }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-5">
        <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Total Movements') }}</p>
            <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($stats['total'] ?? 0) }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Stock Deducted') }}</p>
            <p class="mt-2 text-2xl font-bold text-red-600">{{ number_format($stats['deducted'] ?? 0) }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Stock Restored') }}</p>
            <p class="mt-2 text-2xl font-bold text-emerald-600">{{ number_format($stats['restored'] ?? 0) }}</p>
        </div>
    </div>

    <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 shadow-sm p-4 mb-5 space-y-4">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-5">
            <div class="col-span-1">
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Search product') }}</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search movements...') }}" class="w-full px-3 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Movement Type') }}</label>
                <select wire:model.live="typeFilter" class="w-full px-3 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                    @foreach($movementTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Product') }}</label>
                <select wire:model.live="productFilter" class="w-full px-3 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                    <option value="all">{{ __('All Products') }}</option>
                    @foreach($products as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-1">
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Date From') }}</label>
                <input type="date" wire:model.live="dateFrom" class="w-full px-3 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
            </div>

            <div class="lg:col-span-1">
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Date To') }}</label>
                <input type="date" wire:model.live="dateTo" class="w-full px-3 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
            </div>
        </div>

        <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Showing') }} <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $movements->total() }}</span> {{ __('movements') }}
            </p>
            <button type="button" wire:click="clearFilters" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-zinc-100 dark:bg-zinc-700 text-sm font-semibold text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition">
                <i class="fas fa-times-circle text-sm"></i>
                {{ __('Clear filters') }}
            </button>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-100 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900/60">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 uppercase tracking-wider">{{ __('Order') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 uppercase tracking-wider">{{ __('Product') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 uppercase tracking-wider">{{ __('Type') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 uppercase tracking-wider">{{ __('Quantity') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 uppercase tracking-wider">{{ __('Before') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 uppercase tracking-wider">{{ __('After') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 uppercase tracking-wider">{{ __('User') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 uppercase tracking-wider">{{ __('Remarks') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @forelse($movements as $movement)
                        @php
                            $typeLabels = [
                                'order_created' => __('Order Created'),
                                'order_updated' => __('Order Updated'),
                                'order_cancelled' => __('Order Cancelled'),
                                'refund' => __('Refund'),
                                'manual_adjustment' => __('Manual Adjustment'),
                                'restock' => __('Restock'),
                            ];

                            $typeColor = match ($movement->type) {
                                'order_created', 'order_updated' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                'order_cancelled', 'refund', 'restock' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                                default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300',
                            };

                            $referenceLabel = match (class_basename((string) $movement->reference_type)) {
                                'Order' => $movement->reference?->receipt_number ? '#' . $movement->reference->receipt_number : 'Order #' . ($movement->reference_id ?? '-'),
                                default => $movement->reference_type ? class_basename($movement->reference_type) . ' #' . ($movement->reference_id ?? '-') : __('N/A'),
                            };

                            $remarks = match ($movement->type) {
                                'order_created' => $movement->reference?->receipt_number
                                    ? __('Order #:receipt created', ['receipt' => $movement->reference->receipt_number])
                                    : $movement->remarks,
                                'order_updated' => $movement->reference?->receipt_number
                                    ? __('Order #:receipt updated', ['receipt' => $movement->reference->receipt_number])
                                    : $movement->remarks,
                                'order_cancelled' => $movement->reference?->receipt_number
                                    ? __('Order #:receipt cancelled', ['receipt' => $movement->reference->receipt_number])
                                    : $movement->remarks,
                                'refund' => $movement->reference?->receipt_number
                                    ? __('Refund processed for order #:receipt', ['receipt' => $movement->reference->receipt_number])
                                    : $movement->remarks,
                                'manual_adjustment' => $movement->reference?->receipt_number
                                    ? __('Manual adjustment for order #:receipt', ['receipt' => $movement->reference->receipt_number])
                                    : $movement->remarks,
                                'restock' => $movement->reference?->receipt_number
                                    ? __('Stock restored for order #:receipt', ['receipt' => $movement->reference->receipt_number])
                                    : $movement->remarks,
                                default => $movement->remarks,
                            };
                        @endphp
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/40">

                            {{-- Order Number and Date --}}
                            <td class="px-4 py-3 text-center text-sm text-zinc-500 whitespace-nowrap">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $referenceLabel ?: 'N/A' }}</span>
                                    <span class="text-xs text-zinc-500">{{ $movement->created_at->format('M d, Y h:i:s A') }}</span>
                                </div>
                            </td>

                            {{-- Product --}}
                            <td class="px-4 py-3 text-center text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $movement->product?->name ?? __('Unknown product') }}
                            </td>

                            {{-- Type --}}
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold {{ $typeColor }}">
                                    {{ $typeLabels[$movement->type] ?? ucfirst(str_replace('_', ' ', $movement->type)) }}
                                </span>
                            </td>

                            {{-- Quantity --}}
                            <td class="px-4 py-3 text-center text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ number_format($movement->quantity) }}
                            </td>

                            {{-- Before Stock Levels --}}
                            <td class="px-4 py-3 text-center text-sm text-zinc-500">
                                <div class="space-y-0.5">
                                    <div>
                                        <span class="text-xs">{{ __('Stock') }}:</span>
                                        <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ number_format($movement->before_stocks) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs">{{ __('Sold') }}:</span>
                                        <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ number_format($movement->before_sold) }}</span>
                                    </div>
                                </div>
                            </td>

                            {{-- After Stock Levels --}}
                            <td class="px-4 py-3 text-center text-sm text-zinc-500">
                                <div class="space-y-0.5">
                                    <div>
                                        <span class="text-xs">{{ __('Stock') }}:</span>
                                        <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ number_format($movement->after_stocks) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs">{{ __('Sold') }}:</span>
                                        <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ number_format($movement->after_sold) }}</span>
                                    </div>
                                </div>
                            </td>

                            {{-- User --}}
                            <td class="px-4 py-3 text-sm text-zinc-500">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $movement->user?->name ?? __('System') }}</span>
                            </td>

                            {{-- Remarks --}}
                            <td class="px-4 py-3 text-sm text-zinc-500 max-w-xs truncate">
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $remarks ?? __('N/A') }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center text-zinc-400 dark:text-zinc-500">
                                    <i class="fas fa-boxes-stacked text-5xl mb-4 opacity-40"></i>
                                    <p class="text-sm">{{ __('No inventory movements found.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-zinc-100 dark:border-zinc-700">
            {{ $movements->links() }}
        </div>
    </div>
</div>
