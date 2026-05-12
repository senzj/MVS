@section('title', __('Orders Dashboard'))

<div class="w-full max-w-full overflow-hidden"
    x-data="{ activeTab: 'ongoing' }"
    wire:poll.60s="pollBatchTimers">

    {{-- HEADER --}}
    <div class="flex flex-col gap-3 mb-5 pt-2 sm:pt-0">

        {{-- Title + Live Clock --}}
        <div class="flex items-start justify-between gap-2">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <i class="fas fa-shopping-cart text-blue-500"></i>
                    {{ __('Orders') }}
                </h2>

                <div class="inline-flex items-center gap-2 px-2 py-1 text-gray-800 dark:text-gray-300 text-sm"
                    x-data="{
                        locale: '{{ app()->getLocale() }}',
                        nowMs: Date.now(),
                        get intlLocale() { return this.locale === 'cn' ? 'zh-CN' : this.locale; },
                        tick() { this.nowMs = Date.now(); },
                        start() { this.tick(); setInterval(() => this.tick(), 1000); },
                        get formattedDate() {
                            return new Intl.DateTimeFormat(this.intlLocale, { weekday:'long', year:'numeric', month:'long', day:'numeric' }).format(this.nowMs);
                        },
                        get formattedTime() {
                            return new Intl.DateTimeFormat(this.intlLocale, { hour:'numeric', minute:'2-digit', second:'2-digit', hour12: true }).format(this.nowMs);
                        }
                    }"
                    x-init="start()">
                    <span class="hidden sm:inline" x-text="formattedDate"></span>
                    <span class="hidden sm:inline">•</span>
                    <span x-text="formattedTime"></span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('orders.add') }}" wire:navigate>
                    <button type="button"
                        class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-green-600 text-white text-sm font-semibold
                               hover:bg-green-700 active:scale-95 transition-all shadow-md shadow-green-500/20">
                        <i class="fas fa-file-invoice"></i>
                        <span class="inline">{{ __('Record Sales') }}</span>
                    </button>
                </a>

                <a href="{{ route('orders.create') }}" wire:navigate>
                    <button type="button"
                        class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold
                               hover:bg-blue-700 active:scale-95 transition-all shadow-md shadow-blue-500/20">
                        <i class="fas fa-plus"></i>
                        <span class="inline">{{ __('Create Order') }}</span>
                    </button>
                </a>
            </div>
        </div>

        {{-- Search + Filters --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-4">
            <div wire:loading.class="opacity-50 pointer-events-none"
                 wire:target="search,paymentFilter,statusFilter,clearFilters"
                 class="grid grid-cols-1 lg:grid-cols-4 gap-3 items-end">

                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                        <i class="fas fa-search mr-1"></i>{{ __('Search Orders') }}
                    </label>
                    <div class="relative">
                        <input type="text"
                               wire:model.defer.300ms="search"
                               placeholder="{{ __('Order number, customer, delivered by') }}"
                               class="w-full pl-3 pr-9 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                                      bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                      focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                        @if($search)
                            <button type="button" wire:click="$set('search', '')"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-red-500 transition-colors">
                                <i class="fas fa-times-circle text-sm"></i>
                            </button>
                        @else
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-300 dark:text-zinc-600 pointer-events-none">
                                <i class="fas fa-search text-sm"></i>
                            </span>
                        @endif
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                        <i class="fas fa-credit-card mr-1"></i>{{ __('Payment') }}
                    </label>
                    <select wire:model.live="paymentFilter"
                        class="w-full py-2.5 px-3 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                        <option value="all">{{ __('All Payments') }}</option>
                        <option value="paid">{{ __('Paid') }}</option>
                        <option value="unpaid">{{ __('Unpaid') }}</option>
                        <option value="refunded">{{ __('Refunded') }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                        <i class="fas fa-filter mr-1"></i>{{ __('Status') }}
                    </label>
                    <select wire:model.live="statusFilter"
                        class="w-full py-2.5 px-3 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                        <option value="all">{{ __('All Status') }}</option>
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="preparing">{{ __('Preparing') }}</option>
                        <option value="in_transit">{{ __('In transit') }}</option>
                        <option value="delivered">{{ __('Delivered') }}</option>
                        <option value="completed">{{ __('Completed') }}</option>
                        <option value="cancelled">{{ __('Cancelled') }}</option>
                    </select>
                </div>
            </div>

            @if($search || $paymentFilter !== 'all' || $statusFilter !== 'all')
                <div class="flex items-center w-full mt-4 justify-end"
                     wire:loading.remove wire:target="search,paymentFilter,statusFilter,clearFilters">
                    <button type="button"
                        wire:click="clearFilters"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-zinc-100 dark:bg-zinc-700 text-sm font-semibold text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition">
                        <i class="fas fa-times-circle text-sm"></i>
                        {{ __('Clear filters') }}
                    </button>
                </div>
            @endif

        {{-- Order Status KPIs --}}
        <div class="grid grid-cols-1 sm:grid-cols-5 gap-3 mt-3">
            @php $s = $orderStatusCounts ?? ['pending'=>0,'preparing'=>0,'in_transit'=>0,'delivered'=>0,'completed_cancelled'=>0]; @endphp

            <div class="p-3 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Pending') }}</p>
                <div class="mt-2 flex items-center justify-between">
                    <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($s['pending'] ?? 0) }}</p>
                    <i class="fas fa-clock text-amber-500"></i>
                </div>
            </div>

            <div class="p-3 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Preparing') }}</p>
                <div class="mt-2 flex items-center justify-between">
                    <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($s['preparing'] ?? 0) }}</p>
                    <i class="fas fa-utensils text-yellow-500"></i>
                </div>
            </div>

            <div class="p-3 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('In Transit') }}</p>
                <div class="mt-2 flex items-center justify-between">
                    <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($s['in_transit'] ?? 0) }}</p>
                    <i class="fas fa-truck-fast text-indigo-500"></i>
                </div>
            </div>

            <div class="p-3 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Delivered') }}</p>
                <div class="mt-2 flex items-center justify-between">
                    <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($s['delivered'] ?? 0) }}</p>
                    <i class="fas fa-box-open text-purple-500"></i>
                </div>
            </div>

            <div class="p-3 bg-white border rounded-lg shadow-sm dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700">
                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Completed / Cancelled') }}</p>
                <div class="mt-2 flex items-center justify-between">
                    <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($s['completed_cancelled'] ?? 0) }}</p>
                    <i class="fas fa-check-circle text-zinc-500"></i>
                </div>
            </div>
        </div>
        </div>

        {{-- Tab Bar --}}
        <div class="grid w-full grid-cols-2 gap-0 rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-1 shadow-sm overflow-hidden">
            <button type="button"
                x-on:click="activeTab = 'ongoing'"
                class="relative flex w-full items-center justify-center gap-2 px-4 py-3 text-sm font-semibold transition-all focus:outline-none rounded-xl"
                :class="activeTab === 'ongoing'
                    ? 'bg-blue-600 text-white shadow-sm dark:bg-blue-500'
                    : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700/40'">
                <i class="fas fa-hourglass-half text-xs"></i>
                {{ __('Ongoing') }}
                <span class="inline-flex items-center justify-center min-w-[1.4rem] h-5 px-1.5 text-xs rounded-full font-bold bg-white/20 text-inherit">
                    {{ $ongoingCount }}
                </span>
            </button>

            <button type="button"
                x-on:click="activeTab = 'completed'"
                class="relative flex w-full items-center justify-center gap-2 px-4 py-3 text-sm font-semibold transition-all focus:outline-none rounded-xl"
                :class="activeTab === 'completed'
                    ? 'bg-blue-600 text-white shadow-sm dark:bg-blue-500'
                    : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700/40'">
                <i class="fas fa-clipboard-check text-xs"></i>
                {{ __('Completed') }}
                <span class="inline-flex items-center justify-center min-w-[1.4rem] h-5 px-1.5 text-xs rounded-full font-bold bg-white/20 text-inherit">
                    {{ $completedCount }}
                </span>
            </button>
        </div>
    </div>

    {{-- ONGOING TAB --}}
    <div x-show="activeTab === 'ongoing'"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0">
        <div wire:loading.remove wire:target="search,paymentFilter,statusFilter,clearFilters">
            @include('livewire.partials.orders.table', [
                'orders' => $ongoing,
                'tab'    => 'ongoing',
            ])
        </div>
    </div>

    {{-- COMPLETED TAB --}}
    <div x-show="activeTab === 'completed'" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0">
        <div wire:loading.remove wire:target="search,paymentFilter,statusFilter,clearFilters">
            @include('livewire.partials.orders.table', [
                'orders' => $completed,
                'tab'    => 'completed',
            ])
        </div>
    </div>

    {{-- MODALS --}}
    @include('livewire.partials.orders.modal.order', [
        'modalMode' => 'view',
        'selectedOrder' => $selectedOrder,
    ])

    @include('livewire.partials.orders.modal.delete')

    @include('livewire.partials.orders.modal.cancel')

    {{-- Refund component — MUST be a Livewire component tag, not @include --}}
    <livewire:partials.orders.modal.refund />

    {{-- Payment modal component (opens via event) --}}
    <livewire:partials.orders.modal.payment />

    {{-- SHARED UTILITY CSS --}}
    <style>
        /* Card action button – used inside mobile cards */
        .card-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.375rem 0.625rem;
            border-radius: 0.625rem;
            font-size: 0.75rem;
            font-weight: 500;
            transition: background-color 0.15s;
            cursor: pointer;
            white-space: nowrap;
        }
        /* Table action button – used inside desktop table rows */
        .tbl-action-btn {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 0.125rem;
            padding: 0.375rem 0.625rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            transition: background-color 0.15s;
            cursor: pointer;
            white-space: nowrap;
            min-width: 3.25rem;
        }
    </style>

    {{-- Full-screen loading overlay --}}
    @include('livewire.partials.loading-overlay', [
        'wireTarget' => implode(',', [
            'search','paymentFilter','statusFilter','clearFilters',
            'viewOrderDetails','confirmDelete','deleteOrderConfirmed',
            'startDelivery','cancelPrepare','togglePaid',
            'markDelivered','markFinished','processBatchDelivery',
            'closeOrderDetailsModal','closeDeleteModal','openCancel','confirmCancel','closeCancelModal',
            ''
        ])
    ])

</div>
