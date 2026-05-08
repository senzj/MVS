{{--
    Reusable order table partial.

    Props:
        $orders   – Collection of Order models
        $tab      – 'ongoing' | 'completed'  (controls which action buttons appear)
        $empty    – (optional) string, custom empty-state message
--}}

@php
    $emptyIcon    = $tab === 'ongoing' ? 'fa-inbox'   : 'fa-archive';
    $emptyMessage = $empty
        ?? ($tab === 'ongoing'
            ? __('No ongoing orders today.')
            : __('No completed or cancelled orders today.'));
@endphp

@if($orders->isEmpty())
    <div class="flex flex-col items-center justify-center py-20 text-zinc-400 dark:text-zinc-500">
        <i class="fas {{ $emptyIcon }} text-5xl mb-4 opacity-40"></i>
        <p class="text-sm">{{ $emptyMessage }}</p>
    </div>
@else

    {{-- Mobile Cards (< lg) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 lg:hidden">
        @foreach($orders as $order)
            @include('livewire.partials.orders.card', ['order' => $order, 'tab' => $tab])
        @endforeach
    </div>

    {{-- Desktop Table (≥ lg) --}}
    <div class="hidden lg:block bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900/60">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Order Number') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Customer Name') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Delivered By') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Date & Time') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider w-56">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @foreach($orders as $order)
                        @include('livewire.partials.orders.row', ['order' => $order, 'tab' => $tab])
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

@endif
