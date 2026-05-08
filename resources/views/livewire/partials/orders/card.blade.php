{{--
    Single order card for mobile layout.
    Props: $order, $tab ('ongoing' | 'completed')
--}}

@php
    $stripColors = [
        'pending'    => 'bg-yellow-400',
        'preparing'  => 'bg-orange-400',
        'in_transit' => 'bg-indigo-500',
        'delivered'  => 'bg-purple-500',
        'completed'  => 'bg-green-500',
        'cancelled'  => 'bg-red-500',
    ];
    $strip = $stripColors[$order->status] ?? 'bg-zinc-400';

    $customerBadgeClass = $order->order_type === 'walk_in'
        ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
        : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300';

    $loc = app()->getLocale() === 'cn' ? 'zh_CN' : app()->getLocale();
@endphp

<div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 overflow-hidden {{ $tab === 'completed' ? 'opacity-90' : '' }}">

    {{-- Status colour strip --}}
    <div class="h-1 w-full {{ $strip }}"></div>

    <div class="p-4 space-y-3">

        {{-- Receipt + Status --}}
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="font-mono text-xs text-zinc-400 dark:text-zinc-500">
                    <i class="fas fa-receipt mr-1"></i>{{ $order->receipt_number }}
                </p>
                <span class="mt-1 inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold {{ $customerBadgeClass }}">
                    @if($order->order_type === 'walk_in')
                        <i class="fas fa-walking text-[10px]"></i>{{ __('Walk-In') }}
                    @else
                        <i class="fas fa-user-circle text-[10px]"></i>{{ $order->customer->name ?? __('N/A') }}
                    @endif
                </span>
            </div>

            <div class="flex flex-col items-end gap-1 shrink-0">
                @include('livewire.partials.orders.status-badge', ['order' => $order])
            </div>
        </div>

        {{-- Info grid --}}
        <div class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-xs">
            <div class="text-zinc-500 dark:text-zinc-400">
                <i class="fas fa-{{ $order->is_paid ? 'check-circle text-green-500' : 'exclamation-triangle text-red-500' }} mr-1"></i>
                {{ $order->is_paid ? __('Paid') : __('Unpaid') }}
            </div>
            <div class="text-zinc-500 dark:text-zinc-400 truncate">
                @if($order->order_type === 'walk_in')
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[11px] font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                        <i class="fas fa-walking text-[10px]"></i>{{ __('Walk-In') }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[11px] font-semibold bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">
                        <i class="fas fa-user-tie text-[10px]"></i>{{ $order->employee->name ?? __('N/A') }}
                    </span>
                @endif
            </div>
            <div class="text-zinc-400 dark:text-zinc-500 col-span-2">
                <i class="fas fa-clock mr-1"></i>
                {{ $order->updated_at->locale($loc)->isoFormat('MMM D · hh:mm A') }}
            </div>
        </div>

        {{-- Actions --}}
        @include('livewire.partials.orders.action', ['order' => $order, 'style' => 'card'])

    </div>
</div>
