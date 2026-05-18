@section('title', __('Orders Records'))

@push('scripts')
    <script id="payment-status-chart-data" type="application/json">@json($paymentStatusChart ?? ['labels' => [], 'datasets' => []])</script>
    <script id="payment-methods-chart-data" type="application/json">@json($paymentMethodsChart ?? ['labels' => [], 'datasets' => []])</script>
    <script>
        // Localized strings for orders charts
        window.__ordersI18n = {
            orders: '{{ __('Orders') }}'
        };
    </script>
@endpush

<div id="order-history-content"
    class="w-full max-w-full overflow-x-hidden px-2 sm:px-4 pb-8"
    x-data="{
        showOrderModal: false,
        viewMode: 'list', //list or grid
        showFilters: false,
        currentYear: '',
        currentDay: '',

        // i18n fallbacks
        i18n: {
            allYears: '{{ __('All Years') }}',
            allDays: '{{ __('All Days') }}',
        },

        // Date indicator auto-hide
        showDateIndicator: false,
        dateIndicatorTimeout: null,

        // Scroll to top
        showScrollTop: false,
        scrollToTop() { window.scrollTo({ top: 0, behavior: 'smooth' }); },

        // Infinite scroll
        isNearBottom: false,
        checkScroll() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;
            this.isNearBottom = (scrollTop + windowHeight) >= (documentHeight - 200);
        }
    }"
    x-on:history-open.window="showOrderModal = true"
    x-on:history-close.window="showOrderModal = false"
    x-on:orders-loaded.window="
        // Optional: Show a brief success message when more orders load
        console.log('More orders loaded');
    "
    x-init="
        // Track current year/month in view
        $el._observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    let year = entry.target.getAttribute('data-year');
                    let day = entry.target.getAttribute('data-day');
                    if (year) currentYear = year;
                    if (day) currentDay = day;
                    // Show date indicator when year/day changes
                    showDateIndicator = true;

                    // Clear existing timeout
                    if (dateIndicatorTimeout) {
                        clearTimeout(dateIndicatorTimeout);
                    }

                    // Set new timeout to hide after 3 seconds (quicker feedback)
                    dateIndicatorTimeout = setTimeout(() => {
                        showDateIndicator = false;
                    }, 3000);
                }
            });
        }, {
            // Trigger earlier as the headers enter upper viewport
            rootMargin: '-70% 0px -30% 0px',
            threshold: [0, 0.01, 0.1]
        });

        // Observe year and day headers (day contains month text)
        document.querySelectorAll('[data-year], [data-day]').forEach(el => {
            $el._observer.observe(el);
        });

        // Fixed scroll-to-top visibility logic
        $el._toggleScrollTop = () => {
            let scrollY = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
            showScrollTop = scrollY > 150; // Show button when scrolled more than 150px
        };

        // Separate function for date indicator on scroll
        // Throttled update to determine nearest visible header (runs inside rAF)
        $el._scrollRaf = null;
        $el._updateVisibleDateOnScroll = () => {
            const headers = document.querySelectorAll('[data-day], [data-year]');
            let closest = null;
            let closestDist = Infinity;
            const targetY = 96; // distance from top to prefer (px)

            headers.forEach(h => {
                const rect = h.getBoundingClientRect();
                // only consider headers that are on or near the viewport
                if (rect.bottom >= -40 && rect.top <= (window.innerHeight || document.documentElement.clientHeight) + 40) {
                    const dist = Math.abs(rect.top - targetY);
                    if (dist < closestDist) {
                        closestDist = dist;
                        closest = h;
                    }
                }
            });

            if (closest) {
                const y = closest.getAttribute('data-year');
                const d = closest.getAttribute('data-day');
                if (y) currentYear = y;
                if (d) currentDay = d;
            }
        };

        $el._handleDateIndicatorOnScroll = () => {
            if (currentYear || currentDay) {
                showDateIndicator = true;
                if (dateIndicatorTimeout) clearTimeout(dateIndicatorTimeout);
                dateIndicatorTimeout = setTimeout(() => {
                    showDateIndicator = false;
                }, 2000); // visibility after scroll
            }
        };

        // Combined scroll handler (throttled for rAF)
        $el._handleScroll = () => {
            $el._toggleScrollTop();

            // Throttle expensive DOM reads via rAF
            if ($el._scrollRaf) cancelAnimationFrame($el._scrollRaf);
            $el._scrollRaf = requestAnimationFrame(() => {
                $el._updateVisibleDateOnScroll();
                $el._handleDateIndicatorOnScroll();
                $el._scrollRaf = null;
            });

            checkScroll();

            // Load more when near bottom and has more pages
            if (isNearBottom && $wire.hasMorePages && !$wire.isLoading) {
                $wire.loadMore();
            }
        };

        // Use passive scroll listener for better performance
        window.addEventListener('scroll', $el._handleScroll, { passive: true });

        // Fire once on load to set initial state
        $el._toggleScrollTop();

        // Also make sure to cleanup on component destroy
        $el._cleanup = () => {
            window.removeEventListener('scroll', $el._handleScroll);
            if (dateIndicatorTimeout) clearTimeout(dateIndicatorTimeout);
            if ($el._observer) $el._observer.disconnect();
            if ($el._scrollRaf) cancelAnimationFrame($el._scrollRaf);
        };
    ">

    {{-- css --}}
    @push('styles')
        <style>
            [x-cloak] {
                display: none !important;
            }

            /* Date indicator styling */
            .date-indicator {
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
            }

            /* Quick actions panel styling */
            .quick-actions-panel {
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
            }

            /* Floating button styling */
            .floating-search-btn {
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
            }
        </style>
    @endpush

    {{-- HEADER --}}
    <div class="flex items-start justify-between gap-3 py-2 mb-5">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <i class="fas fa-timeline text-blue-500"></i>
                {{ __('Order Records') }}
            </h2>
            @include('livewire.partials.clock')
        </div>

        {{-- View Toggle and Controls --}}
        <div class="flex items-center gap-3">
            {{-- View Toggle --}}
            <div class="flex items-center gap-2">
                <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Layout') }}:</span>
                <div class="flex items-center bg-zinc-100 dark:bg-zinc-700 rounded-lg p-1">
                    <button
                        @click="viewMode = 'list'"
                        :class="viewMode === 'list' ? 'bg-white dark:bg-zinc-600 shadow-sm text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-600'"
                        class="cursor-pointer flex items-center justify-center p-2 rounded-md transition-all duration-200"
                        title="List View"
                    >
                        <i class="fas fa-list text-xs"></i>
                    </button>

                    <button
                        @click="viewMode = 'grid'"
                        :class="viewMode === 'grid' ? 'bg-white dark:bg-zinc-600 shadow-sm text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-600'"
                        class="cursor-pointer flex items-center justify-center p-2 rounded-md transition-all duration-200"
                        title="Grid View"
                    >
                        <i class="fas fa-th text-xs"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts and Analytics --}}
    <div class="w-full my-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200/50 dark:border-zinc-700/50 p-3">
            <div class="text-sm text-zinc-500 dark:text-zinc-400 mb-2">{{ __('Payment Status') }}</div>
            <canvas id="paymentStatusChart" class="w-full" style="max-height: 192px;"></canvas>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200/50 dark:border-zinc-700/50 p-3">
            <div class="text-sm text-zinc-500 dark:text-zinc-400 mb-2">{{ __('Payment Method') }}</div>
            <canvas id="paymentMethodsChart" class="w-full" style="max-height: 192px;"></canvas>
        </div>
    </div>

    {{-- Search and Filters Section --}}
    <div class="bg-white/60 dark:bg-zinc-800/60 rounded-lg shadow-sm border border-zinc-200/50 dark:border-zinc-700/50 mb-0.5"
            style="backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);">
        <div class="p-4">
            <div class="">

                {{-- Search & filter --}}
                <div class="flex flex-col sm:flex-row gap-4">
                    {{-- search bar --}}
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-zinc-400"></i>
                        </div>
                        <input
                            wire:model.live.debounce.300ms="search"
                            id="order-search"
                            type="text"
                            placeholder="{{ __('Search by receipt number or customer name') }}"
                            class="w-full pl-10 pr-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 placeholder-zinc-500 dark:placeholder-zinc-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200"
                        >
                        @if($search)
                            <button
                                wire:click="$set('search', '')"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors duration-200"
                            >
                                <i class="fas fa-times"></i>
                            </button>
                        @endif
                    </div>

                    {{-- Filter Toggle Button --}}
                    <button
                        @click="showFilters = !showFilters"
                        :class="showFilters ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300'"
                        class="px-4 py-2.5 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-all duration-200 flex items-center gap-2"
                    >
                        <i class="fas fa-filter"></i>
                        <span>{{ __('Filter') }}</span>
                        <i class="fas fa-chevron-down transition-transform duration-200" :class="showFilters ? 'rotate-180' : ''"></i>
                    </button>

                </div>

                {{-- Description and Loaded Count --}}
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                    {{ __('Browse and search orders by various criteria.') }}
                    @if($totalOrders > 0)
                        <span class="font-medium">
                            {{ number_format($loadedOrders) }} {{ __('of') }} {{ number_format($totalOrders) }} {{ __('orders loaded') }}
                            @if($hasMorePages)
                                <span class="text-xs text-zinc-500">({{ __('scroll for more') }})</span>
                            @endif
                        </span>
                    @endif
                </p>
            </div>

            {{-- Filters Panel --}}
            <div x-cloak
                x-show="showFilters"
                x-transition:enter="transition-all duration-300 ease-out"
                x-transition:enter-start="opacity-0 max-h-0"
                x-transition:enter-end="opacity-100 max-h-96"
                x-transition:leave="transition-all duration-300 ease-in"
                x-transition:leave-start="opacity-100 max-h-96"
                x-transition:leave-end="opacity-0 max-h-0"
                class="absolute top-full left-0 right-0 z-[60] bg-white/95 dark:bg-zinc-800/95 rounded-lg shadow-xl border border-zinc-200/50 dark:border-zinc-700/50 mt-2 backdrop-blur-md overflow-hidden">
                <div class="p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                        {{-- Status Filter --}}
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1" for="floatingStatusFilter">{{ __('Order Status') }}</label>
                            <select wire:model.live="statusFilter" id="floatingStatusFilter" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                                <option value="">{{ __('All Statuses') }}</option>
                                <option value="pending">{{ __('Pending') }}</option>
                                <option value="in_transit">{{ __('In transit') }}</option>
                                <option value="delivered">{{ __('Delivered') }}</option>
                                <option value="completed">{{ __('Completed') }}</option>
                                <option value="cancelled">{{ __('Cancelled') }}</option>
                            </select>
                        </div>

                        {{-- Payment Filter --}}
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1" for="floatingPaymentFilter">{{ __('Payment Status') }}</label>
                            <select wire:model.live="paymentFilter" id="floatingPaymentFilter" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                                <option value="">{{ __('All Payments') }}</option>
                                <option value="paid">{{ __('Paid') }}</option>
                                <option value="unpaid">{{ __('Unpaid') }}</option>
                                <option value="refunded">{{ __('Refunded') }}</option>
                            </select>
                        </div>

                        {{-- Year Filter --}}
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1" for="floatingYearFilter">{{ __('Year') }}</label>
                            <select wire:model.live="yearFilter" id="floatingYearFilter" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                                <option value="">{{ __('All Years') }}</option>
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Month Filter --}}
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1" for="floatingMonthFilter">{{ __('Month') }}</label>
                            <select wire:model.live="monthFilter" id="floatingMonthFilter" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200{{ !$yearFilter ? ' opacity-50 cursor-not-allowed' : '' }}" @if(!$yearFilter) disabled title="Select a year first" @endif>
                                <option value="">{{ __('All Months') }}</option>
                                @if($yearFilter)
                                    @foreach($availableMonths as $month)
                                        <option value="{{ $month['value'] }}">{{ $month['label'] }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        {{-- Day Filter --}}
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1" for="floatingDayFilter">{{ __('Day') }}</label>
                            <select wire:model.live="dayFilter" id="floatingDayFilter" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200{{ !$monthFilter ? ' opacity-50 cursor-not-allowed' : '' }}" @if(!$monthFilter) disabled title="Select year then month first" @endif>
                                <option value="">{{ __('All Days') }}</option>
                                @if($monthFilter)
                                    @foreach($availableDays as $day)
                                        <option value="{{ $day }}">{{ $day }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        {{-- Sort Options --}}
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1" for="floatingSortbyFilter">{{ __('Sort by') }}</label>
                            <select wire:model.live="sortBy" id="floatingSortbyFilter" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                                <option value="created_at">{{ __('Date created') }}</option>
                                <option value="receipt_number">{{ __('Receipt Number') }}</option>
                                <option value="order_total">{{ __('Total Amount') }}</option>
                                <option value="status">{{ __('Order Status') }}</option>
                            </select>
                        </div>
                    </div>

                    {{-- Filter Actions --}}
                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-zinc-200/50 dark:border-zinc-700/50">
                        <div class="flex items-center gap-2">
                            <button
                                wire:click="toggleSortDirection"
                                class="px-3 py-1.5 text-sm bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-all duration-200 flex items-center gap-2"
                            >
                                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                <span>{{ $sortDirection === 'asc' ? __('Ascending') : __('Descending') }}</span>
                            </button>
                        </div>

                        <button
                            wire:click="clearFilters"
                            class="px-4 py-2 text-sm bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-all duration-200 flex items-center gap-2"
                        >
                            <i class="fas fa-trash-alt"></i>
                            <span>{{ __('Clear All') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Scroll To Top Button --}}
    <div
        x-cloak
        x-show="showScrollTop"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
        class="fixed bottom-10 right-10 z-50">
        <button
            @click="scrollToTop()"
            class="cursor-pointer floating-search-btn w-12 h-12 rounded-full bg-gray-300 text-gray-500 shadow-lg hover:bg-gray-400/60 hover:scale-105 active:scale-95 transition-all duration-200 flex items-center justify-center group"
            title="Scroll to Top"
        >
            <i class="fas fa-arrow-up text-sm group-hover:scale-110 transition-transform duration-200"></i>
        </button>
    </div>

    {{-- Date indicator --}}
    <div
        x-cloak
        x-show="showDateIndicator && (currentYear || currentDay)"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed md:top-66 lg:top-68 right-10 z-30 date-indicator">

        <div class="relative">
            {{-- Date indicator box --}}
            <div class="bg-white/30 dark:bg-zinc-800/30 border border-zinc-200/50 dark:border-zinc-700/50 rounded-xl p-3 min-w-[140px]">
                <div class="text-center">

                    {{-- year --}}
                    <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100 mt-0.5"
                         x-text="currentYear || i18n.allYears"></div>

                    {{-- Month + Day --}}
                    <div class="text-xs font-medium text-zinc-600 dark:text-zinc-400 uppercase tracking-wide"
                         x-text="currentDay || i18n.allDays"></div>

                </div>
            </div>

            {{-- Left pointing triangle arrow --}}
            <div class="absolute top-1/2 -left-2 transform -translate-y-1/2">
                <div class="w-0 h-0 border-r-[8px] border-r-white/90 dark:border-r-zinc-800/90
                           border-t-[8px] border-t-transparent
                           border-b-[8px] border-b-transparent
                           drop-shadow-sm"></div>
                {{-- Border for the arrow --}}
                <div class="absolute top-1/2 -left-[1px] transform -translate-y-1/2">
                    <div class="w-0 h-0 border-r-[9px] border-r-zinc-200/50 dark:border-r-zinc-700/50
                               border-t-[9px] border-t-transparent
                               border-b-[9px] border-b-transparent">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Orders History Content --}}
    <div id="orders-content" class="container mx-auto mt-4 max-w-full">

        @php
            // Defensive guards: ensure variables used by foreach are iterable to avoid runtime errors
            if (!is_iterable($grouped)) {
                $grouped = [];
            }
            if (!is_iterable($availableYears)) {
                $availableYears = [];
            }
            if (!is_iterable($availableMonths)) {
                $availableMonths = [];
            }
            if (!is_iterable($availableDays)) {
                $availableDays = [];
            }
        @endphp

        {{-- Orders List --}}
        <div wire:loading.remove wire:target="updatedSearch,updatedStatusFilter,updatedPaymentFilter,updatedYearFilter,updatedMonthFilter,updatedDayFilter,updatedSortBy,updatedSortDirection,clearFilters">
        @forelse($grouped as $year => $months)
            {{-- Year header --}}
            <div class="first:mt-0 item-left" data-year="{{ $year }}">
                <h1 class="text-4xl font-bold text-zinc-900 dark:text-zinc-100">{{ $year }}</h1>
            </div>

            @foreach($months as $monthKey => $days)
                @php
                    $loc = app()->getLocale() === 'cn' ? 'zh_CN' : app()->getLocale();
                    $monthLabel = \Carbon\Carbon::createFromFormat('Y-m', $monthKey)->locale($loc)->isoFormat('MMMM');
                @endphp

                {{-- Month header --}}
                <div class="mt-4 item-left" data-month="{{ $monthLabel }}" data-year="{{ $year }}">
                    <h2 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-200">{{ $monthLabel }}</h2>
                </div>

                @foreach($days as $dayKey => $orders)
                    @php
                        $dayLabel = \Carbon\Carbon::parse($dayKey)->timezone($tz)->locale($loc)->isoFormat('MMMM D (dddd)');
                    @endphp

                    {{-- Day header --}}
                    <div class="mt-1.5 ml-1 item-left" data-day="{{ $dayLabel }}" data-year="{{ $year }}">
                        <h5 class="text-base font-medium text-zinc-700 dark:text-zinc-300">{{ $dayLabel }}</h5>
                    </div>

                    {{-- Orders Container --}}
                    <div class="mt-2">

                        {{-- Grid View --}}
                        <div x-cloak x-show="viewMode === 'grid'" class="mb-10 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                            @foreach($orders as $o)
                                @include('livewire.partials.orders.history.card', ['order' => $o])
                            @endforeach
                        </div>

                        {{-- List View --}}
                        <ul x-show="viewMode === 'list'" role="list" class="mb-10 space-y-3">
                            @foreach($orders as $o)
                                <li wire:key="list-order-{{ $o->id }}">
                                    @include('livewire.partials.orders.history.list', ['order' => $o])
                                </li>
                            @endforeach
                        </ul>

                    </div>
                @endforeach {{-- foreach day --}}
            @endforeach {{-- foreach month --}}
        @empty
            <div class="flex items-center justify-center min-h-[60vh]">
                <div class="text-center text-zinc-600 dark:text-zinc-400">
                    <i class="fas fa-search text-5xl mb-3"></i>
                    <p class="text-lg font-medium">{{ __('No orders found matching your criteria.') }}</p>
                    <p class="text-sm mt-2">{{ __('Try adjusting your search terms or filters.') }}</p>
                    @if($search || $statusFilter || $paymentFilter || $yearFilter)
                        <button
                            wire:click="clearFilters"
                            class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200"
                        >
                            {{ __('Clear All Filters') }}
                        </button>
                    @endif
                </div>
            </div>
        @endforelse

        {{-- Load More Button (fallback for users who prefer clicking) --}}
        {{-- @if($hasMorePages && !$isLoading)
            <div class="flex justify-center items-center py-8">
                <button
                    wire:click="loadMore"
                    class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200 flex items-center gap-2"
                >
                    <i class="fas fa-chevron-down"></i>
                    <span>Load More Orders</span>
                </button>
            </div>
        @endif --}}

        {{-- End of Results Indicator --}}
        @if(!$hasMorePages && $totalOrders > 0)
            <div class="flex justify-center py-8">
                <div class="text-center text-zinc-500 dark:text-zinc-400">
                    <i class="fas fa-box-open text-2xl mb-2"></i>
                    <p class="text-sm">{{ __('You\'ve reached the end of the results') }}</p>
                    <p class="text-xs">{{ number_format($totalOrders) }} {{ __('orders total') }}</p>
                </div>
            </div>
        @endif
        </div>
    </div>

    {{-- Order Details Modal (same as before) --}}
    @include('livewire.partials.orders.history.info')


    @include('livewire.partials.loading-overlay', [
        'wireTarget' => implode(', ', [
            'loadMore',
            'search',
            'statusFilter',
            'paymentFilter',
            'yearFilter',
            'monthFilter',
            'dayFilter',
            'sortBy',
            'sortDirection',
            'updatedSearch',
            'updatedStatusFilter',
            'updatedPaymentFilter',
            'updatedYearFilter',
            'updatedMonthFilter',
            'updatedDayFilter',
            'updatedSortBy',
            'updatedSortDirection',
            'clearFilters'
        ])
    ])

</div>
