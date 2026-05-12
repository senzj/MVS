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
@endphp

<div wire:key="grid-order-{{ $order->id }}"
    class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm hover:shadow-lg transition-all duration-200 dark:ring-1 dark:ring-white/10 hover:dark:ring-white/20 overflow-hidden">

    {{-- Status colour strip --}}
    <div class="h-1 w-full {{ $strip }}"></div>

    <button
        wire:click="openOrder({{ $order->id }})"
        class="cursor-pointer text-left w-full p-4"
        title="{{ __('View order') }}">

        <div class="flex items-start justify-between">
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Receipt #') }}</div>
                <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $order->receipt_number ?? '—' }}</div>
            </div>
            @include('livewire.partials.orders.status.order-badge', ['order' => $order])
        </div>

        <div class="mt-3 flex items-center gap-3 text-sm">
            <div class="text-zinc-600 dark:text-zinc-300">
                <i class="fas fa-user mr-1 text-zinc-400"></i>{{ $order->customer->name ?? __('Walk-In') }}
            </div>
            <div class="text-zinc-600 dark:text-zinc-300">
                <i class="fas fa-user-tie mr-1 text-zinc-400"></i>{{ $order->employee->name ?? __('Unassigned') }}
            </div>
            <div class="ml-auto text-zinc-500 dark:text-zinc-400">
                <i class="fas fa-clock mr-1 text-zinc-400"></i>{{ $timeLabel }}
            </div>
        </div>

        <div class="mt-2 flex items-center justify-between">
            <div class="text-sm text-zinc-600 dark:text-zinc-300">
                <i class="fas fa-money-bill mr-1 text-zinc-400"></i>
                ₱{{ number_format($order->order_total, 2) }}
            </div>
            @include('livewire.partials.orders.status.payment-badge', [
                'status' => $order->payment_status,
                'size'   => 'sm',
            ])
        </div>

    </button>
</div>
