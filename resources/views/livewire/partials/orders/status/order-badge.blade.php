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
    // Resolve color: prefer model attribute, otherwise use same mapping as Order model
    $color = $order?->status_color ?? match ($status) {
        'pending'    => 'amber',
        'preparing'  => 'yellow',
        'in_transit' => 'indigo',
        'delivered'  => 'purple',
        'completed'  => 'green',
        'cancelled'  => 'red',
        default      => 'gray',
    };
@endphp

<span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full
             bg-{{ $color }}-100 text-{{ $color }}-800
             dark:bg-{{ $color }}-900/30 dark:text-{{ $color }}-300">
    <i class="fas {{ $meta['icon'] }}"></i>
    {{ $meta['label'] }}
</span>
