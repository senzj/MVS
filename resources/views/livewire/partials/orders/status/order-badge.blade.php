{{--
    Status badge + optional "old order" pill.
    Props: $order, $statusKey (fallback if no order)
--}}
@php
    $status = $order?->status ?: ($statusKey ?: 'pending');
    $statusLabels = [
        'pending'    => ['label' => __('Pending'),    'icon' => 'fa-clock'],
        'preparing'  => ['label' => __('Preparing'),  'icon' => 'fa-hourglass-start'],
        'in_transit' => ['label' => __('In transit'), 'icon' => 'fa-truck-fast'],
        'delivered'  => ['label' => __('Delivered'),  'icon' => 'fa-box-open'],
        'completed'  => ['label' => __('Completed'),  'icon' => 'fa-check-circle'],
        'cancelled'  => ['label' => __('Cancelled'),  'icon' => 'fa-times-circle'],
    ];
    $meta = $statusLabels[$status] ?? [
        'label' => ucfirst(str_replace('_', ' ', $status)),
        'icon'  => 'fa-circle',
    ];
@endphp

@if($order?->created_at && !$order->created_at->isToday())
    @php $days = max(1, (int)$order->created_at->diffInDays(now())); @endphp
    <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full
                 bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300">
        <i class="fas fa-calendar-alt"></i>
        @if($days <= 7)
            {{ trans_choice('days_ago', $days, ['count' => $days]) }}
        @else
            Old Order
        @endif
    </span>
@endif

<span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full
             bg-{{ $order?->status_color ?: 'amber' }}-100 text-{{ $order?->status_color ?: 'amber' }}-800
             dark:bg-{{ $order?->status_color ?: 'amber' }}-900/30 dark:text-{{ $order?->status_color ?: 'amber' }}-300">
    <i class="fas {{ $meta['icon'] }}"></i>
    {{ $meta['label'] }}
</span>
