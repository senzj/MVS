{{--
    Order row for the history page (list view).
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

<div wire:key="list-order-{{ $order->id }}"
    class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">

    {{-- Status colour strip --}}
    <div class="h-1 w-full {{ $strip }}"></div>

    <button
        wire:click="openOrder({{ $order->id }})"
        class="cursor-pointer w-full text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 transition-all duration-200"
        title="{{ __('View Order') }} {{ $order->receipt_number }}">

        <div class="p-4">

            {{-- Header Row --}}
            <div class="flex justify-between items-start mb-3">
                <div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">{{ __('Receipt #') }}</div>
                    <div class="font-mono font-semibold text-lg text-zinc-900 dark:text-zinc-100">
                        {{ $order->receipt_number ?? '—' }}
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                        <i class="fas fa-clock mr-1"></i>{{ $timeLabel }}
                    </div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        {{ $order->created_at->timezone($tz)->locale($loc)->isoFormat('ll') }}
                    </div>
                </div>
            </div>

            {{-- Customer + Status --}}
            <div class="flex justify-between items-center mb-3">
                <div class="flex items-center text-sm text-zinc-600 dark:text-zinc-300">
                    <i class="fas fa-user mr-2 text-zinc-400"></i>
                    <span class="font-medium">{{ $order->customer->name ?? __('Walk-In') }}</span>
                </div>
                @include('livewire.partials.orders.status.order-badge', ['order' => $order])
            </div>

            {{-- Payment + Total --}}
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    @include('livewire.partials.orders.status.payment-badge', [
                        'status' => $order->payment_status,
                        'size'   => 'sm',
                    ])
                    <div class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">
                        {{ $order->payment_type ?? __('N/A') }}
                    </div>
                    @if($order->employee)
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                            <i class="fas fa-user-tie mr-1"></i>{{ $order->employee->name }}
                        </div>
                    @endif
                </div>
                <div class="text-right">
                    <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                        ₱{{ number_format($order->order_total, 2) }}
                    </div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Total Amount') }}</div>
                </div>
            </div>

        </div>
    </button>
</div>
