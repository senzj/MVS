{{--
    Status badge
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

<span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full
             bg-{{ $order?->status_color ?: 'amber' }}-100 text-{{ $order?->status_color ?: 'amber' }}-800
             dark:bg-{{ $order?->status_color ?: 'amber' }}-900/30 dark:text-{{ $order?->status_color ?: 'amber' }}-300">
    <i class="fas {{ $meta['icon'] }}"></i>
    {{ $meta['label'] }}
</span>
