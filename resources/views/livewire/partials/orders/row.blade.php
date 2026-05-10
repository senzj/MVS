{{--
    Single order <tr> for the desktop table.
    Props: $order, $tab ('ongoing' | 'completed')
--}}

@php
    $loc = app()->getLocale() === 'cn' ? 'zh_CN' : app()->getLocale();
@endphp

<tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/40 transition-colors {{ $tab === 'completed' ? 'opacity-90' : '' }}">

    {{-- Receipt --}}
    <td class="px-4 py-3 whitespace-nowrap">
        <span class="font-mono text-sm text-zinc-800 dark:text-zinc-200">
            <i class="fas fa-receipt mr-1 text-zinc-400"></i>{{ $order->receipt_number }}
        </span>
    </td>

    {{-- Customer --}}
    <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-800 dark:text-zinc-200">
        @if($order->order_type === 'walk_in')
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                <i class="fas fa-walking text-[10px]"></i>{{ __('Walk-In') }}
            </span>
        @else
            <i class="fas fa-user-circle mr-1 text-zinc-400"></i>{{ $order->customer->name ?? __('N/A') }}
        @endif
    </td>

    {{-- Status --}}
    <td class="px-4 py-3 whitespace-nowrap text-center">
        <div class="flex flex-col items-center gap-1">
            @include('livewire.partials.orders.status-badge', ['order' => $order])
        </div>
    </td>

    {{-- Delivered by --}}
    <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-800 dark:text-zinc-200">
        @if($order->order_type === 'walk_in')
            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                <i class="fas fa-walking"></i> Walk-In
            </span>
        @else
            <i class="fas fa-user-tie mr-1 text-zinc-400"></i>{{ $order->employee->name ?? __('N/A') }}
        @endif
    </td>

    {{-- Date --}}
    <td class="px-4 py-3 whitespace-nowrap text-center">
        <time class="text-xs text-zinc-500 dark:text-zinc-400" datetime="{{ $order->updated_at->toIso8601String() }}">
            <span class="block">{{ $order->updated_at->locale($loc)->isoFormat('LL') }}</span>
            <span class="block">{{ $order->updated_at->locale($loc)->isoFormat('hh:mm A') }}</span>
        </time>
    </td>

    {{-- Actions --}}
    <td class="px-4 py-3 whitespace-nowrap w-56">
        @include('livewire.partials.orders.action', ['order' => $order, 'style' => 'table'])
    </td>

</tr>
