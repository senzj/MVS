@php
    
@endphp

@if($created_at && !$created_at->isToday())
    @php 
        $days = max(1, (int)$created_at->diffInDays(now())); 
    @endphp

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