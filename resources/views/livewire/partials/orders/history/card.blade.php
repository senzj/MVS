{{--
    Order card for the history page (grid view).
    Props: $order
--}}
@php
    $loc = app()->getLocale() === 'cn' ? 'zh_CN' : app()->getLocale();
    $tz  = config('app.timezone', 'UTC');
    $timeLabel = $order->created_at->timezone($tz)->locale($loc)->isoFormat('LT');

    $stripColors = [
        'pending'    => 'bg-yellow-400',
        'preparing'  => 'bg-orange-400',
        'in_transit' => 'bg-indigo-500',
        'delivered'  => 'bg-purple-500',
        'completed'  => 'bg-green-500',
        'cancelled'  => 'bg-red-500',
    ];
    $strip = $stripColors[$order->status] ?? 'bg-zinc-400';

    $pay_type = [
        'cash' => 'text-green-500 bg-green-500/50',
        'gcash' => 'text-blue-500 bg-blue-500/50',
    ];

    $payment_type_status = $pay_type[$order->payment_type]
        ?? 'text-gray-500 bg-gray-500/50';
@endphp

<div wire:key="grid-order-{{ $order->id }}"
    class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm hover:shadow-lg transition-all duration-200 dark:ring-1 dark:ring-white/10 hover:dark:ring-white/20 overflow-hidden">

    {{-- Status colour strip --}}
    <div class="h-1 w-full {{ $strip }}"></div>

    <button
        wire:click="openOrder({{ $order->id }})"
        class="cursor-pointer text-left w-full p-4 space-y-3"
        title="{{ __('View order') }}">

        <div class="flex items-start justify-between">
            <div class="flex items-center">
                <i class="fas fa-receipt mr-1"></i>

                <div class="flex gap-2 items-center">
                    <div class="font-mono font-semibold text-lg text-zinc-900 dark:text-zinc-100">
                        {{ $order->receipt_number ?? '—' }}
                    </div>

                    <div class="text-[10px] font-semibold text-zinc-900 dark:text-zinc-100 uppercase tracking-wide {{ $payment_type_status }} p-1 rounded-full">
                        {{ $order->payment_type ?? __('N/A') }}
                    </div>
                </div>
            </div>

            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                <i class="fas fa-clock mr-1"></i>{{ $timeLabel }}
                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                    {{ $order->created_at->timezone($tz)->locale($loc)->isoFormat('ll') }}
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 text-sm justify-between">
            <div class="">
                <div class="text-zinc-600 dark:text-zinc-300">
                    <i class="fas fa-user mr-1 text-zinc-400"></i>{{ $order->customer->name ?? __('Walk-In') }}
                </div>
            </div>

            @include('livewire.partials.orders.status.order-badge', ['order' => $order])
        </div>

        <div class="flex items-center justify-between">
            <div class="flex items-center gap-1">
                @include('livewire.partials.orders.status.payment-badge', [
                    'status' => $order->payment_status,
                    'size'   => 'sm',
                ])

                <div class="text-xs text-zinc-900 dark:text-zinc-100 rounded-full bg-gray-500/50 p-1">
                    <i class="fas fa-user-tie mr-1"></i>{{ $order->employee->name ?? __('Unassigned') }}
                </div>
            </div>

            <div class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                {{ config('storeconfig.currency_symbol') }}{{ number_format($order->order_total, 2) }}
            </div>
        </div>

    </button>
</div>
