{{--
    vieworders.blade.php
    --------------------
    Rendered inside the universal modal when $modalMode === 'view'.
    Props: $order  (Order model with orderItems.product, customer, employee loaded)
--}}

@php
    $loc = app()->getLocale() === 'cn' ? 'zh_CN' : app()->getLocale();
    $statusColors = [
        'pending'    => 'amber',
        'preparing'  => 'blue',
        'in_transit' => 'orange',
        'delivered'  => 'teal',
        'completed'  => 'green',
        'cancelled'  => 'red',
    ];
    $statusColor = $statusColors[$order->status] ?? 'zinc';
    $statusLabel = __([
        'preparing'  => 'Preparing',
        'pending'    => 'Pending',
        'in_transit' => 'In transit',
        'delivered'  => 'Delivered',
        'completed'  => 'Completed',
        'cancelled'  => 'Cancelled',
    ][$order->status] ?? ucfirst(str_replace('_', ' ', $order->status)));
@endphp

<div class="space-y-4">
    {{-- Receipt header --}}
    <div class="text-center space-y-2 border-b border-dashed border-zinc-300 dark:border-zinc-600 pb-4">
        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-[0.3em]">
                    Order Receipt
            </p>
        </div>
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-zinc-200 dark:border-zinc-700 text-xs font-semibold text-zinc-600 dark:text-zinc-300">
            <span>#{{ $order->receipt_number }}</span>
            <span class="text-zinc-300 dark:text-zinc-600">•</span>
            <span>{{ $order->created_at->locale($loc)->isoFormat('MMM D, YYYY · h:mm A') }}</span>
        </div>
    </div>

    {{-- Quick details --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-sm">
        <div class="rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-700 p-2.5">
                <p class="text-[10px] uppercase tracking-widest text-zinc-500 dark:text-zinc-400">
                    {{ __('Order Type') }}
                </p>
                <p class="mt-0.5 font-semibold text-zinc-900 dark:text-zinc-100 text-sm">{{ $order->order_type === 'deliver' ? __('Delivery') : __('Walk-In') }}</p>
        </div>
        <div class="rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-700 p-2.5">
                <p class="text-[10px] uppercase tracking-widest text-zinc-500 dark:text-zinc-400">
                    {{ __('Payment') }}
                </p>
            <p class="mt-0.5 font-semibold text-zinc-900 dark:text-zinc-100 text-sm">
                    {{ strtolower($order->payment_type ?? '') === 'cash' ? __('Cash') : __('GCash / Online') }}
            </p>

            <p class="mt-0.5 inline-flex items-center gap-1 font-semibold text-sm {{ $order->is_paid ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                <i class="fas fa-{{ $order->is_paid ? 'check-circle' : 'exclamation-circle' }}"></i>
                    {{ $order->is_paid ? __('Paid') : __('Unpaid') }}
            </p>
        </div>
        <div class="rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-700 p-2.5">
                <p class="text-[10px] uppercase tracking-widest text-zinc-500 dark:text-zinc-400">
                    {{ __('Status') }}
                </p>
            <p class="mt-0.5 inline-flex items-center gap-2 font-semibold text-sm text-{{ $statusColor }}-700 dark:text-{{ $statusColor }}-300">
                <span class="h-2 w-2 rounded-full bg-{{ $statusColor }}-500"></span>
                {{ $statusLabel }}
            </p>
        </div>
    </div>

    {{-- Delivery + Customer --}}
    @if($order->order_type === 'deliver')
        <div class="grid grid-cols-1 gap-3">
            <div class="rounded-xl bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-700 p-3">
                    <p class="text-[11px] uppercase tracking-widest text-zinc-500 dark:text-zinc-400 mb-1">
                    {{ __('Delivery Person') }}
                    </p>
                    <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $order->employee?->name ?? 'N/A' }}</p>
            </div>

            <div class="rounded-xl bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-700 p-3">
                    <p class="text-[11px] uppercase tracking-widest text-zinc-500 dark:text-zinc-400 mb-1">
                    {{ __('Customer') }}
                    </p>
                @if($order->customer)
                    <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $order->customer->name }}</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $order->customer->contact_number ?? __('Not Provided') }}</p>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ implode(', ', array_filter([$order->customer->unit, $order->customer->address])) ?: __('Not Provided') }}
                    </p>
                @else
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No customer') }}</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Items --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">

        <div class="hidden sm:block bg-white dark:bg-zinc-800">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900/60">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            <div class="gap-1 flex items-center">
                                    {{ __('Item') }}
                                <p class="text-xs text-zinc-400 dark:text-zinc-600">({{ $order->orderItems->count() }})</p>
                            </div>
                        </th>
                            <th class="px-3 py-2 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 w-14">{{ __('Qty') }}</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 w-24">{{ __('Price') }}</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 w-28">{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->orderItems as $item)
                        <tr class="align-top {{ $loop->odd ? 'bg-zinc-50/80 dark:bg-zinc-900/45' : 'bg-white dark:bg-zinc-800' }}">
                            <td class="px-3 py-2.5 min-w-0">
                                <p class="font-semibold text-zinc-900 dark:text-zinc-100 truncate">
                                    {{ $item->product?->name ?? '#' . $item->product_id }}
                                </p>
                                @if($item->product?->category)
                                    <p class="text-[11px] uppercase tracking-wide text-zinc-400 mt-0.5">
                                        {{ __(\App\Models\Product::getCategories()[$item->product->category] ?? ucfirst(str_replace('_', ' ', $item->product->category))) }}
                                    </p>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-center font-medium text-zinc-700 dark:text-zinc-300 tabular-nums">{{ $item->quantity }}</td>
                            <td class="px-3 py-2.5 text-right font-mono text-zinc-700 dark:text-zinc-300 tabular-nums">₱{{ number_format($item->unit_price, 2) }}</td>
                            <td class="px-3 py-2.5 text-right font-mono font-semibold text-zinc-900 dark:text-zinc-100 tabular-nums">₱{{ number_format($item->total_price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="sm:hidden bg-white dark:bg-zinc-800">
            @foreach($order->orderItems as $item)
                <div class="px-3 py-2.5 space-y-2 {{ $loop->odd ? 'bg-zinc-50/80 dark:bg-zinc-900/45' : 'bg-white dark:bg-zinc-800' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 truncate">
                                {{ $item->product?->name ?? '#' . $item->product_id }}
                            </p>
                            @if($item->product?->category)
                                <p class="text-[11px] uppercase tracking-wide text-zinc-400 mt-0.5">{{ __(\App\Models\Product::getCategories()[$item->product->category] ?? ucfirst(str_replace('_', ' ', $item->product->category))) }}</p>
                            @endif
                        </div>
                        <p class="shrink-0 text-right font-mono text-sm font-semibold text-zinc-900 dark:text-zinc-100">₱{{ number_format($item->total_price, 2) }}</p>
                    </div>
                    <div class="flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400">
                            <span>{{ __('Qty') }}: <span class="font-medium text-zinc-700 dark:text-zinc-300 tabular-nums">{{ $item->quantity }}</span></span>
                            <span>{{ __('Price') }}: <span class="font-medium text-zinc-700 dark:text-zinc-300 tabular-nums">₱{{ number_format($item->unit_price, 2) }}</span></span>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Total --}}
        <div class=" bg-zinc-50 dark:bg-zinc-900/50 p-4">
            <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-500 dark:text-zinc-400">{{ __('Total Amount') }}</span>
                <span class="text-2xl font-black font-mono text-zinc-900 dark:text-zinc-100">₱{{ number_format($order->order_total, 2) }}</span>
            </div>
        </div>
    </div>
</div>
