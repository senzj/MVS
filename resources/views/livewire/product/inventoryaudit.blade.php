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

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 mb-5">
        <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Total Movements') }}</p>
            <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($stats['total'] ?? 0) }}</p>
            <p class="mt-1 text-xs text-zinc-500">{{ __('Showing total inventory movements') }}</p>
        </div>

        <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Added Stocks') }}</p>
            <p class="mt-2 text-2xl font-bold text-emerald-600">{{ number_format($stats['added'] ?? 0) }}</p>
            <p class="mt-1 text-xs text-zinc-500">{{ __('Total units added (restock/refund)') }}</p>
        </div>

        <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Removed Stocks') }}</p>
            <p class="mt-2 text-2xl font-bold text-red-600">{{ number_format($stats['removed'] ?? 0) }}</p>
            <p class="mt-1 text-xs text-zinc-500">{{ __('Total units removed (sales/adjustments)') }}</p>
        </div>

        <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Most Adjusted Product') }}</p>
            <p class="mt-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $stats['most_adjusted_product'] ?? __('N/A') }}</p>
            <p class="mt-1 text-xs text-zinc-500">{{ __('Product with highest total adjustments') }}</p>
        </div>
    </div>

    {{-- Inventory movements chart (additions vs deductions) --}}
    <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm mb-5">
        <div class="flex items-center justify-between mb-2">
            <div>
                <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Inventory Movements Over Time') }}</div>
                <div class="text-xs text-zinc-500">{{ __('Shows additions and deductions to help detect unusual activity and supply patterns.') }}</div>
            </div>
        </div>

        <script id="inventory-audit-chart-data" type="application/json">@json($chart)</script>
        <div class="h-45">
            <canvas id="inventoryAuditLineChart"></canvas>
        </div>
        <script>
            window.dispatchEvent(new CustomEvent('inventory-audit-chart-data', {
                detail: { data: @json($chart) }
            }));
        </script>
    </div>

    {{-- Stock Distribution and Movement Type Distribution Charts --}}
    <div class="grid grid-cols-1 gap-4 mb-5 lg:grid-cols-2">
        {{-- Stock Distribution by Product (Donut Chart) --}}
        <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm">
            <div class="mb-3">
                <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Stock Distribution by Product') }}</div>
                <div class="text-xs text-zinc-500">{{ __('Top 10 products by current stock quantity.') }}</div>
            </div>

            <script id="stock-distribution-chart-data" type="application/json">@json($stockChart)</script>
            <div class="h-64">
                <canvas id="stockDistributionChart"></canvas>
            </div>
            <script>
                window.dispatchEvent(new CustomEvent('stock-distribution-chart-data', {
                    detail: { data: @json($stockChart) }
                }));
            </script>
        </div>

        {{-- Movement Type Distribution (Donut Chart) --}}
        <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 p-4 shadow-sm">
            <div class="mb-3">
                <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Movement Type Distribution') }}</div>
                <div class="text-xs text-zinc-500">{{ __('Breakdown of all inventory movement types.') }}</div>
            </div>

            <script id="movement-type-chart-data" type="application/json">@json($typeChart)</script>
            <div class="h-64">
                <canvas id="movementTypeChart"></canvas>
            </div>
            <script>
                window.dispatchEvent(new CustomEvent('movement-type-chart-data', {
                    detail: { data: @json($typeChart) }
                }));
            </script>
        </div>
    </div>

    <div class="rounded-2xl bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 shadow-sm p-4 mb-5 space-y-4">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-5">
            <div class="col-span-1">
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Search Order Number') }}</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by order or remarks') }}" class="w-full px-3 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
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

    {{-- Cards for mobile & tablet (hidden on xl and above) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 xl:hidden">
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

            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 overflow-hidden">
                @php
                    $strip = match ($movement->type) {
                        'order_created', 'order_updated' => 'bg-red-400',
                        'order_cancelled', 'refund', 'restock' => 'bg-emerald-500',
                        'manual_adjustment' => 'bg-indigo-400',
                        default => 'bg-zinc-400',
                    };
                @endphp
                <div class="h-1 w-full {{ $strip }}"></div>
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $referenceLabel }}</p>
                            @php
                                $loc = app()->getLocale() === 'cn' ? 'zh_CN' : app()->getLocale();
                                $formattedDate = $movement->created_at->locale($loc)->isoFormat('MMM DD, YYYY | HH:mm:ss A');
                            @endphp
                            <p class="text-xs text-zinc-500 mt-1">{{ $formattedDate }}</p>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold {{ $typeColor }}">{{ $typeLabels[$movement->type] ?? ucfirst(str_replace('_', ' ', $movement->type)) }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mt-3 text-sm">
                        <div>
                            <div class="text-xs text-zinc-500">{{ __('Product') }}</div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $movement->product?->name ?? __('Unknown product') }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-zinc-500">{{ __('Quantity') }}</div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($movement->quantity) }}</div>
                        </div>
                    </div>

                    <div class="mt-3 text-sm text-zinc-500 max-w-full truncate">{{ $remarks ?? __('N/A') }}</div>
                </div>
            </div>

        @empty
            <div class="col-span-1">
                <div class="flex flex-col items-center text-zinc-400 dark:text-zinc-500 py-8">
                    <i class="fas fa-boxes-stacked text-4xl mb-3 opacity-40"></i>
                    <p class="text-sm">{{ __('No inventory movements found.') }}</p>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Cards pagination (mobile/tablet) --}}
    <div class="px-4 py-3 border-t border-zinc-100 dark:border-zinc-700 xl:hidden">
        {{ $movements->links() }}
    </div>

    {{-- Table for desktop (xl and above) --}}
    <div class="hidden xl:block bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-100 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900/60">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 uppercase tracking-wider">{{ __('Order Number') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 uppercase tracking-wider">{{ __('Product') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 uppercase tracking-wider">{{ __('Movement Type') }}</th>
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
                                @php
                                    $loc = app()->getLocale() === 'cn' ? 'zh_CN' : app()->getLocale();
                                    $tableDate = $movement->created_at->locale($loc)->isoFormat('MMM DD, YYYY | HH:mm:ss A');
                                @endphp
                                <div class="flex flex-col items-center gap-1">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $referenceLabel ?: 'N/A' }}</span>
                                    <span class="text-xs text-zinc-500">{{ $tableDate }}</span>
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

        <div class="px-4 py-3 border-t border-zinc-100 dark:border-zinc-700 hidden xl:block">
            {{ $movements->links() }}
        </div>
    </div>

    @include('livewire.partials.loading-overlay',
        ['wireTarget' => implode(', ', [
            'search',
            'typeFilter',
            'productFilter',
            'dateFrom',
            'dateTo',
            'clearFilters'
        ])
        ]
    )
</div>
