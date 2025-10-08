@section('title', 'Order Records')

<div id="order-history-content"
    class="container mx-auto max-w-7xl relative"
    x-data="{ 
        showOrderModal: false,
        viewMode: 'list',
        showFilters: false,
        currentYear: '',
        currentMonth: '',
        // i18n fallbacks
        i18n: {
            allYears: '{{ __('All Years') }}',
            allMonths: '{{ __('All Months') }}',
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
                    let month = entry.target.getAttribute('data-month');
                    if (year) currentYear = year;
                    if (month) currentMonth = month;
                    
                    // Show date indicator when year/month changes
                    showDateIndicator = true;
                    
                    // Clear existing timeout
                    if (dateIndicatorTimeout) {
                        clearTimeout(dateIndicatorTimeout);
                    }
                    
                    // Set new timeout to hide after 10 seconds
                    dateIndicatorTimeout = setTimeout(() => {
                        showDateIndicator = false;
                    }, 10000);
                }
            });
        }, {
            rootMargin: '-40% 0px -40% 0px'
        });

        // Observe year and month headers
        document.querySelectorAll('[data-year], [data-month]').forEach(el => {
            $el._observer.observe(el);
        });

        // Fixed scroll-to-top visibility logic
        $el._toggleScrollTop = () => {
            let scrollY = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
            showScrollTop = scrollY > 150; // Show button when scrolled more than 150px
        };

        // Separate function for date indicator on scroll
        $el._handleDateIndicatorOnScroll = () => {
            if (currentYear || currentMonth) {
                showDateIndicator = true;
                if (dateIndicatorTimeout) clearTimeout(dateIndicatorTimeout);
                dateIndicatorTimeout = setTimeout(() => { 
                    showDateIndicator = false; 
                }, 3000); // Reduced to 3 seconds for better UX
            }
        };

        // Combined scroll handler
        $el._handleScroll = () => {
            $el._toggleScrollTop();
            $el._handleDateIndicatorOnScroll();
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
        };
    ">

    {{-- css --}}
    <style>
        [x-cloak] { display: none !important; }
        
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

    {{-- Header --}}
    <div id="static-header" class="bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
        <div class="container mx-auto p-4 max-w-7xl">
            <div class="mb-3">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    {{-- Header --}}
                    <div>
                        <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ __('Orders History') }}</h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
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
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 16a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
                                    </svg>
                                </button>
                                
                                <button
                                    @click="viewMode = 'grid'"
                                    :class="viewMode === 'grid' ? 'bg-white dark:bg-zinc-600 shadow-sm text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-600'"
                                    class="cursor-pointer flex items-center justify-center p-2 rounded-md transition-all duration-200"
                                    title="Grid View"
                                >
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search and Filters Section --}}
            <div class="bg-white/60 dark:bg-zinc-800/60 rounded-lg shadow-sm border border-zinc-200/50 dark:border-zinc-700/50 mb-0.5"
                 style="backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);">
                <div class="p-4">
                    {{-- Search Bar --}}
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-zinc-400"></i>
                            </div>
                            <input
                                wire:model.live.debounce.300ms="search"
                                id="order-search"
                                type="text"
                                placeholder="{{ __('Search by receipt number or customer name...') }}"
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
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1" for="floatingStatusFilter">Status</label>
                                    <select wire:model.live="statusFilter" id="floatingStatusFilter" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                                        <option value="">All Statuses</option>
                                        <option value="pending">Pending</option>
                                        <option value="in_transit">In Transit</option>
                                        <option value="delivered">Delivered</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>

                                {{-- Payment Filter --}}
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1" for="floatingPaymentFilter">Payment</label>
                                    <select wire:model.live="paymentFilter" id="floatingPaymentFilter" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                                        <option value="">All Payments</option>
                                        <option value="paid">Paid</option>
                                        <option value="unpaid">Unpaid</option>
                                    </select>
                                </div>

                                {{-- Year Filter --}}
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1" for="floatingYearFilter">Year</label>
                                    <select wire:model.live="yearFilter" id="floatingYearFilter" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                                        <option value="">All Years</option>
                                        @foreach($availableYears as $year)
                                            <option value="{{ $year }}">{{ $year }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Month Filter --}}
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1" for="floatingMonthFilter">Month</label>
                                    <select wire:model.live="monthFilter" id="floatingMonthFilter" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200{{ !$yearFilter ? ' opacity-50 cursor-not-allowed' : '' }}" @if(!$yearFilter) disabled title="Select a year first" @endif>
                                        <option value="">All Months</option>
                                        @if($yearFilter)
                                            @foreach($availableMonths as $month)
                                                <option value="{{ $month['value'] }}">{{ $month['label'] }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                {{-- Day Filter --}}
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1" for="floatingDayFilter">Day</label>
                                    <select wire:model.live="dayFilter" id="floatingDayFilter" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200{{ !$monthFilter ? ' opacity-50 cursor-not-allowed' : '' }}" @if(!$monthFilter) disabled title="Select year then month first" @endif>
                                        <option value="">All Days</option>
                                        @if($monthFilter)
                                            @foreach($availableDays as $day)
                                                <option value="{{ $day }}">{{ $day }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                {{-- Sort Options --}}
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1" for="floatingSortbyFilter">Sort By</label>
                                    <select wire:model.live="sortBy" id="floatingSortbyFilter" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                                        <option value="created_at">Date Created</option>
                                        <option value="receipt_number">Receipt Number</option>
                                        <option value="order_total">Total Amount</option>
                                        <option value="status">Status</option>
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
                                        <span>{{ $sortDirection === 'asc' ? 'Ascending' : 'Descending' }}</span>
                                    </button>
                                </div>
                                
                                <button
                                    wire:click="clearFilters"
                                    class="px-4 py-2 text-sm bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-all duration-200 flex items-center gap-2"
                                >
                                    <i class="fas fa-trash-alt"></i>
                                    <span>Clear All Filters</span>
                                </button>
                            </div>
                        </div>
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
        x-show="showDateIndicator && (currentYear || currentMonth)" 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed md:top-66 lg:top-68 right-10 z-30 date-indicator">
        
        <div class="relative">
            {{-- Main indicator box --}}
            <div class="bg-white/40 dark:bg-zinc-800/40 border border-zinc-200/50 dark:border-zinc-700/50 rounded-xl shadow-lg p-3 min-w-[140px]">
                <div class="text-center">

                    {{-- year --}}
                    <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100 mt-0.5" 
                         x-text="currentYear || i18n.allYears"></div>

                    {{-- month --}}
                    <div class="text-xs font-medium text-zinc-600 dark:text-zinc-400 uppercase tracking-wide" 
                         x-text="currentMonth || i18n.allMonths"></div>

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

    {{-- Orders Content --}}
    <div id="orders-content" class="container mx-auto p-4 max-w-7xl">
        {{-- Initial Loading Indicator --}}
        <div wire:loading.delay.longest wire:target="updatedSearch,updatedStatusFilter,updatedPaymentFilter,updatedYearFilter,updatedMonthFilter,updatedDayFilter,updatedSortBy,updatedSortDirection,clearFilters" class="flex justify-center py-12">
            <div class="text-center">
                <div class="inline-flex items-center gap-3 text-zinc-600 dark:text-zinc-400">
                    <svg class="animate-spin h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ __('Loading orders...') }}</span>
                </div>
            </div>
        </div>

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
                <div class="mt-3 item-left" data-month="{{ $monthLabel }}" data-year="{{ $year }}">
                    <h2 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-200">{{ $monthLabel }}</h2>
                </div>

                @foreach($days as $dayKey => $orders)
                    @php
                        $dayLabel = \Carbon\Carbon::parse($dayKey)->timezone($tz)->locale($loc)->isoFormat('MMMM D (dddd)');
                    @endphp

                    {{-- Day header --}}
                    <div class="mt-1.5">
                        <h5 class="text-base font-medium text-zinc-700 dark:text-zinc-300">{{ $dayLabel }}</h5>
                    </div>

                    {{-- Orders Container --}}
                    <div class="mt-2">

                        {{-- Grid View --}}
                        <div x-cloak x-show="viewMode === 'grid'" class="mb-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($orders as $o)
                                @php
                                    $status = $o->status;
                                    $statusClasses = match($status) {
                                        'pending'    => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                                        'in_transit' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
                                        'delivered'  => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
                                        'completed'  => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                        'cancelled'  => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                        default      => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
                                    };
                                    $loc = app()->getLocale() === 'cn' ? 'zh_CN' : app()->getLocale();
                                    $timeLabel = $o->created_at->timezone($tz)->locale($loc)->isoFormat('LT');
                                @endphp

                                <button
                                    wire:click="openOrder({{ $o->id }})"
                                    wire:key="grid-order-{{ $o->id }}"
                                    class="cursor-pointer text-left bg-white dark:bg-zinc-800 rounded-lg p-4 shadow-sm hover:shadow-lg transition-all duration-200 dark:ring-1 dark:ring-white/10 hover:dark:ring-white/20"
                                    title="View order">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Receipt #') }}</div>
                                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $o->receipt_number ?? '—' }}</div>
                                        </div>
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full {{ $statusClasses }}">
                                            {{ __([
                                                'pending'    => 'Pending',
                                                'in_transit' => 'In transit',
                                                'delivered'  => 'Delivered',
                                                'completed'  => 'Completed',
                                                'cancelled'  => 'Cancelled',
                                            ][$status] ?? ucfirst(str_replace('_', ' ', $status))) }}
                                        </span>
                                    </div>

                                    <div class="mt-3 flex items-center gap-3 text-sm">
                                        <div class="text-zinc-600 dark:text-zinc-300">
                                            <i class="fas fa-user mr-1 text-zinc-400"></i>{{ $o->customer->name ?? __('Walk-In') }}
                                        </div>
                                        <div class="text-zinc-600 dark:text-zinc-300">
                                            <i class="fas fa-user-tie mr-1 text-zinc-400"></i>{{ $o->employee->name ?? __('Unassigned') }}
                                        </div>
                                        <div class="ml-auto text-zinc-500 dark:text-zinc-400">
                                            <i class="fas fa-clock mr-1 text-zinc-400"></i>{{ $timeLabel }}
                                        </div>
                                    </div>

                                    <div class="mt-2 flex items-center justify-between">
                                        <div class="text-sm text-zinc-600 dark:text-zinc-300">
                                            <i class="fas fa-money-bill mr-1 text-zinc-400"></i>
                                            ₱{{ number_format($o->order_total, 2) }}
                                        </div>
                                        <div class="text-xs {{ $o->is_paid ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                                            @if($o->is_paid)
                                                <i class="fas fa-check-circle mr-1"></i>{{ __('Paid') }}
                                            @else
                                                <i class="fas fa-circle-exclamation mr-1"></i>{{ __('Unpaid') }}
                                            @endif
                                        </div>
                                    </div>
                                </button>
                            @endforeach
                        </div>

                        {{-- List View --}}
                        <ul x-show="viewMode === 'list'" role="list" class="mb-10 space-y-3">
                            @foreach($orders as $o)
                                @php
                                    $status = $o->status;
                                    $statusClasses = match($status) {
                                        'pending'    => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                                        'in_transit' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
                                        'delivered'  => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
                                        'completed'  => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                        'cancelled'  => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                        default      => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
                                    };
                                    $loc = app()->getLocale() === 'cn' ? 'zh_CN' : app()->getLocale();
                                    $timeLabel = $o->created_at->timezone($tz)->locale($loc)->isoFormat('LT');
                                @endphp

                                <li wire:key="list-order-{{ $o->id }}">
                                    <button wire:click="openOrder({{ $o->id }})"
                                        class="cursor-pointer w-full text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-lg transition-all duration-200"
                                        title="View Order {{ $o->receipt_number }}">
                                        
                                        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 shadow-sm hover:shadow-md transition-all duration-200">
                                            <div class="p-4">
                                                {{-- Header Row: Receipt # and Time --}}
                                                <div class="flex justify-between items-start mb-3">
                                                    <div>
                                                        <div class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">{{ __('Receipt #') }}</div>
                                                        <div class="font-mono font-semibold text-lg text-zinc-900 dark:text-zinc-100">
                                                            {{ $o->receipt_number ?? '—' }}
                                                        </div>
                                                    </div>
                                                    <div class="text-right">
                                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                                            <i class="fas fa-clock mr-1"></i>{{ $timeLabel }}
                                                        </div>
                                                        <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                                            {{ $o->created_at->timezone($tz)->locale($loc)->isoFormat('ll') }}
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Second Row: Customer and Status --}}
                                                <div class="flex justify-between items-center mb-3">
                                                    <div class="flex items-center text-sm text-zinc-600 dark:text-zinc-300">
                                                        <i class="fas fa-user mr-2 text-zinc-400"></i>
                                                        <span class="font-medium">{{ $o->customer->name ?? __('Walk-In') }}</span>
                                                    </div>
                                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full {{ $statusClasses }}">
                                                        @if($status === 'pending')
                                                            <i class="fas fa-clock mr-1"></i>
                                                        @elseif($status === 'in_transit')
                                                            <i class="fas fa-truck mr-1"></i>
                                                        @elseif($status === 'delivered')
                                                            <i class="fas fa-box-open mr-1"></i>
                                                        @elseif($status === 'completed')
                                                            <i class="fas fa-check-circle mr-1"></i>
                                                        @elseif($status === 'cancelled')
                                                            <i class="fas fa-times-circle mr-1"></i>
                                                        @endif
                                                        <span class="ml-1">
                                                            {{ __([
                                                                'preparing'  => 'Preparing',
                                                                'pending'    => 'Pending',
                                                                'in_transit' => 'In transit',
                                                                'delivered'  => 'Delivered',
                                                                'completed'  => 'Completed',
                                                                'cancelled'  => 'Cancelled',
                                                            ][$status] ?? ucfirst(str_replace('_',' ', $status))) }}
                                                        </span>
                                                    </span>
                                                </div>

                                                {{-- Third Row: Payment Status and Total --}}
                                                <div class="flex justify-between items-center">
                                                    <div class="flex items-center gap-4">
                                                        {{-- Payment Status --}}
                                                        <div class="flex items-center text-sm">
                                                            @if($o->is_paid)
                                                                <i class="fas fa-check-circle mr-2 text-green-500"></i>
                                                                <span class="text-green-600 dark:text-green-400 font-medium">{{ __('Paid') }}</span>
                                                            @else
                                                                <i class="fas fa-exclamation-circle mr-2 text-red-500"></i>
                                                                <span class="text-red-600 dark:text-red-400 font-medium">{{ __('Unpaid') }}</span>
                                                            @endif
                                                        </div>

                                                        {{-- Payment Type --}}
                                                        <div class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">
                                                            {{ $o->payment_type ?? __('N/A') }}
                                                        </div>

                                                        {{-- Employee/Delivery Person --}}
                                                        @if($o->employee)
                                                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                                                <i class="fas fa-user-tie mr-1"></i>{{ $o->employee->name }}
                                                            </div>
                                                        @endif
                                                    </div>

                                                    {{-- Total Amount --}}
                                                    <div class="text-right">
                                                        <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                                                            ₱{{ number_format($o->order_total, 2) }}
                                                        </div>
                                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                                            {{ __('Total Amount') }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </button>
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

        {{-- Loading Indicator --}}
        @if($hasMorePages && !$isLoading)
            <div wire:loading.delay wire:target="loadMore" class="flex justify-center items-center py-8">
                <div class="flex justify-center items-center py-8">
                    <i class="fas fa-spinner fa-spin animate-spin"></i>
                    <span>{{ __('Fetching Orders...') }}</span>
                </div>
            </div>
        @endif

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
    @if($selectedOrder && $showOrderModal)
        <div x-cloak x-show="showOrderModal" x-transition.opacity x-trap.noscroll.inert="showOrderModal"
            class="fixed inset-0 z-50">

            <div class="absolute inset-0 bg-zinc-500/80" @click="showOrderModal=false; $wire.closeOrder()"></div>

            <div x-show="showOrderModal" x-transition.scale
                class="absolute inset-0 flex items-center justify-center p-4">

                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between p-6 border-b border-zinc-200 dark:border-zinc-700">
                        <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                            <i class="fas fa-file-invoice mr-2"></i>{{ __('Order Details') }}
                        </h3>
                        <button @click="showOrderModal=false; $wire.closeOrder()"
                            class="cursor-pointer text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors duration-200">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">

                            {{-- Order Information --}}
                            <div class="space-y-4">
                                <h4 class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    <i class="fas fa-shopping-bag mr-2"></i>{{ __('Order Information') }}
                                </h4>
                                @php
                                    $status = $selectedOrder->status;
                                    $statusClasses = match($status) {
                                        'pending'    => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                                        'in_transit' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
                                        'delivered'  => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
                                        'completed'  => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                        'cancelled'  => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                        default      => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
                                    };
                                @endphp
                                <dl class="space-y-2">

                                    {{-- order id --}}
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                            <i class="fas fa-hashtag mr-1"></i>{{ __('Order ID') }}:
                                        </dt>
                                        <dd class="text-sm font-medium text-zinc-900 dark:text-zinc-100">#{{ $selectedOrder->id }}</dd>
                                    </div>

                                    {{-- receipt number --}}
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                            <i class="fas fa-receipt mr-1"></i>{{ __('Receipt Number') }}:
                                        </dt>
                                        <dd class="text-sm font-mono font-medium text-zinc-900 dark:text-zinc-100">{{ $selectedOrder->receipt_number }}</dd>
                                    </div>

                                    {{-- Employee --}}
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                            <i class="fas fa-user-tie mr-1"></i>{{ __('Delivered By') }}:
                                        </dt>
                                        <dd class="text-sm text-zinc-900 dark:text-zinc-100">{{ $selectedOrder->employee->name ?? 'N/A' }}</dd>
                                    </div>

                                    {{-- Order Status --}}
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                            <i class="fas fa-info-circle mr-1"></i>{{ __('Order Status') }}:
                                        </dt>
                                        <dd>
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full {{ $statusClasses }}">
                                                @if($status === 'pending')
                                                    <i class="fas fa-clock mr-1"></i>
                                                @elseif($status === 'in_transit')
                                                    <i class="fas fa-truck-fast mr-1"></i>
                                                @elseif($status === 'delivered')
                                                    <i class="fas fa-box-open mr-1"></i>
                                                @elseif($status === 'completed')
                                                    <i class="fas fa-check-circle mr-1"></i>
                                                @elseif($status === 'cancelled')
                                                    <i class="fas fa-times-circle mr-1"></i>
                                                @endif
                                                <span class="ml-1">
                                                    {{ __([
                                                        'preparing'  => 'Preparing',
                                                        'pending'    => 'Pending',
                                                        'in_transit' => 'In transit',
                                                        'delivered'  => 'Delivered',
                                                        'completed'  => 'Completed',
                                                        'cancelled'  => 'Cancelled',
                                                    ][$status] ?? ucfirst(str_replace('_',' ', $status))) }}
                                                </span>
                                            </span>
                                        </dd>
                                    </div>

                                    {{-- Payment status --}}
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                            <i class="fas fa-credit-card mr-1"></i>{{ __('Status') }}:
                                        </dt>
                                        <dd>
                                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full {{ $selectedOrder->is_paid ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' }}">
                                                @if($selectedOrder->is_paid)
                                                    <i class="fas fa-check-circle"></i> {{ __('Paid') }}
                                                @else
                                                    <i class="fas fa-exclamation-triangle"></i> {{ __('Unpaid') }}
                                                @endif
                                            </span>
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                            <i class="fas fa-money-bill mr-1"></i>{{ __('Payment Method') }}:
                                        </dt>
                                        @php
                                            $map = [
                                                'cash'   => 'Cash',
                                                'online' => 'Online',
                                            ];
                                            $code = strtolower($selectedOrder->payment_type ?? '');
                                            $label = $map[$code] ?? ($selectedOrder->payment_type ?? __('N/A'));
                                        @endphp
                                        <dd class="text-sm text-zinc-900 dark:text-zinc-100">{{ __($label) }}</dd>
                                    </div>
                                </dl>
                            </div>

                            {{-- Customer Information --}}
                            <div class="space-y-4">
                                <h4 class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    <i class="fas fa-user mr-2"></i>{{ __('Customer Information') }}
                                </h4>
                                @if($selectedOrder->customer)
                                    <dl class="space-y-2">
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                                <i class="fas fa-id-badge mr-1"></i>{{ __('Customer Name') }}:
                                            </dt>
                                            <dd class="text-sm text-zinc-900 dark:text-zinc-100">{{ $selectedOrder->customer->name }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                                <i class="fas fa-map-marker-alt mr-1"></i>{{ __('Unit & Address') }}:
                                            </dt>
                                            @if ($selectedOrder->customer->unit || $selectedOrder->customer->address)
                                                <dd class="text-sm text-zinc-900 dark:text-zinc-100">
                                                    {{ $selectedOrder->customer->unit ? $selectedOrder->customer->unit . ', ' : '' }}{{ $selectedOrder->customer->address }}
                                                </dd>
                                            @else
                                                <dd class="text-sm text-zinc-900 dark:text-zinc-100">{{ __('N/A') }}</dd>
                                            @endif
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                                <i class="fas fa-phone mr-1"></i>{{ __('Contact Number') }}:
                                            </dt>
                                            <dd class="text-sm text-zinc-900 dark:text-zinc-100">{{ $selectedOrder->customer->contact_number ?? __('N/A') }}</dd>
                                        </div>
                                    </dl>
                                @else
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                        <i class="fas fa-user-slash mr-1"></i>{{ __('No customer information available') }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        {{-- Products List --}}
                        <div>
                            <h4 class="font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                                <i class="fas fa-shopping-basket mr-2"></i>{{ __('Ordered Items') }}
                            </h4>
                            @if($selectedOrder->orderItems && count($selectedOrder->orderItems) > 0)
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                                        <thead class="bg-zinc-200 dark:bg-zinc-900">
                                            <tr>
                                                <th class="px-4 py-2 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                                    {{ __('ID #') }}
                                                </th>
                                                <th class="px-4 py-2 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                                    {{ __('Product') }}
                                                </th>
                                                <th class="px-4 py-2 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                                    {{ __('Quantity') }}
                                                </th>
                                                <th class="px-4 py-2 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                                    {{ __('Unit Price') }}
                                                </th>
                                                <th class="px-4 py-2 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                                    {{ __('Total') }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                            @foreach($selectedOrder->orderItems as $item)
                                                @php
                                                    $prodName = $item->product->product_name ?? $item->product->name ?? 'N/A';
                                                    $total = $item->total_price ?? ($item->unit_price * $item->quantity);
                                                @endphp
                                                <tr>
                                                    <td class="px-4 py-2 text-center text-sm text-zinc-900 dark:text-zinc-100">
                                                        <i class="fas fa-hashtag mr-1 text-zinc-400"></i>{{ $item->product->id ?? '#' }}
                                                    </td>
                                                    <td class="items-center px-4 py-2 text-center text-sm text-zinc-900 dark:text-zinc-100">
                                                        {{ $prodName }}
                                                    </td>
                                                    <td class="px-4 py-2 text-center text-sm text-zinc-900 dark:text-zinc-100">
                                                        {{ $item->quantity }}
                                                    </td>
                                                    <td class="px-4 py-2 text-center text-sm text-zinc-900 dark:text-zinc-100">₱{{ number_format($item->unit_price, 2) }}</td>
                                                    <td class="px-4 py-2 text-center text-sm font-medium text-zinc-900 dark:text-zinc-100">₱{{ number_format($total, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Order Total Amount --}}
                                <div class="mt-4 border-t border-zinc-200 dark:border-zinc-700 pt-4">
                                    <div class="flex justify-between items-center">
                                        <span class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                                            <i class="fas fa-receipt mr-2"></i>{{ __('Total Amount') }}:
                                        </span>
                                        <span class="text-xl font-bold text-zinc-900 dark:text-zinc-100">₱{{ number_format($selectedOrder->order_total, 2) }}</span>
                                    </div>
                                </div>
                            @else
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>{{ __('No items found for this order') }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 px-6 py-4 bg-zinc-50 dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-700">
                        <button
                            @click="showOrderModal=false; $wire.closeOrder()"
                            class="cursor-pointer px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700 transition-colors duration-200"
                        >
                            <i class="fas fa-times mr-1"></i>{{ __('Close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>