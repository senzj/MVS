{{--
    payment-status-badge.blade.php
    ================================
    Reusable badge for the payment_status enum: unpaid | paid | refunded

    Props:
        $status  – string  (the order's payment_status value)
        $size    – 'sm' | 'md'  (default: 'sm')
--}}

@php
    $size   = $size ?? 'sm';
    $status = $status ?? 'unpaid';

    $cfg = match ($status) {
        'paid'     => [
            'bg'   => 'bg-green-100 dark:bg-green-900/30',
            'text' => 'text-green-800 dark:text-green-300',
            'icon' => 'fas fa-check-circle',
            'label'=> __('Paid'),
        ],
        'refunded' => [
            'bg'   => 'bg-purple-100 dark:bg-purple-900/30',
            'text' => 'text-purple-800 dark:text-purple-300',
            'icon' => 'fas fa-undo',
            'label'=> __('Refunded'),
        ],
        default    => [   // unpaid
            'bg'   => 'bg-red-100 dark:bg-red-900/30',
            'text' => 'text-red-800 dark:text-red-300',
            'icon' => 'fas fa-exclamation-circle',
            'label'=> __('Unpaid'),
        ],
    };

    $sizeClass = $size === 'md'
        ? 'px-2.5 py-1 text-sm gap-1.5'
        : 'px-2 py-0.5 text-xs gap-1';
@endphp

<span class="inline-flex items-center font-semibold rounded-full {{ $cfg['bg'] }} {{ $cfg['text'] }} {{ $sizeClass }}">
    <i class="{{ $cfg['icon'] }} shrink-0" aria-hidden="true"></i>
    {{ $cfg['label'] }}
</span>
