@section('title', __('Orders Dashboard'))

<div class="w-full max-w-full overflow-x-hidden px-2 sm:px-4 pb-8"
    x-data="{ activeTab: 'ongoing' }"
    wire:poll.5s>

    {{-- HEADER --}}
    <div class="flex flex-col gap-3 mb-5 pt-2 sm:pt-0">

        {{-- Title + Clock --}}
        <div class="flex items-start justify-between gap-2">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <i class="fas fa-shopping-cart text-blue-500"></i>
                    {{ __('Orders') }}
                </h2>
                <div class="text-zinc-400 text-xs mt-0.5"
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
                             return new Intl.DateTimeFormat(this.intlLocale, { hour:'numeric', minute:'2-digit', second:'2-digit', hour12:true }).format(this.nowMs);
                         }
                     }"
                     x-init="start()">
                    <span class="hidden sm:inline" x-text="formattedDate"></span>
                    <span class="hidden sm:inline"> · </span>
                    <span x-text="formattedTime"></span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                {{-- Create Existing Record Order --}}
                <a href="#" wire:navigate>
                    <button type="button"
                        class="cursor-pointer inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-green-600 text-white text-sm font-semibold
                            hover:bg-green-700 active:scale-95 transition-all shadow-md shadow-green-500/20">
                        <i class="fas fa-file-invoice"></i>
                        <span class="inline">{{ __('Record Sales') }}</span>
                    </button>
                </a>

                {{-- Create New Order button --}}
                <a href="{{ route('orders.create') }}" wire:navigate>
                    <button type="button"
                        class="cursor-pointer inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold
                            hover:bg-blue-700 active:scale-95 transition-all shadow-md shadow-blue-500/20">
                        <i class="fas fa-plus"></i>
                        <span class="inline">{{ __('Create Order') }}</span>
                    </button>
                </a>
            </div>
        </div>

        {{-- Tab Bar + Counters --}}
        <div class="grid w-full grid-cols-2 gap-0 rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-1 shadow-sm overflow-hidden">
            {{-- Ongoing Tab --}}
            <button
                type="button"
                x-on:click="activeTab = 'ongoing'"
                class="relative flex w-full items-center justify-center gap-2 px-4 py-3 text-sm font-semibold transition-all
                       focus:outline-none rounded-xl"
                :class="activeTab === 'ongoing'
                    ? 'bg-blue-600 text-white shadow-sm dark:bg-blue-500'
                    : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700/40'">
                <i class="fas fa-hourglass-half text-xs"></i>
                {{ __('Ongoing') }}
                <span class="inline-flex items-center justify-center min-w-[1.4rem] h-5 px-1.5 text-xs rounded-full font-bold
                                                         bg-white/20 text-inherit">
                    {{ $ongoingCount }}
                </span>
            </button>

            {{-- Completed Tab --}}
            <button
                type="button"
                x-on:click="activeTab = 'completed'"
                class="relative flex w-full items-center justify-center gap-2 px-4 py-3 text-sm font-semibold transition-all
                       focus:outline-none rounded-xl"
                :class="activeTab === 'completed'
                    ? 'bg-blue-600 text-white shadow-sm dark:bg-blue-500'
                    : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700/40'">
                <i class="fas fa-clipboard-check text-xs"></i>
                {{ __('Completed') }}
                <span class="inline-flex items-center justify-center min-w-[1.4rem] h-5 px-1.5 text-xs rounded-full font-bold
                                                         bg-white/20 text-inherit">
                    {{ $completedCount }}
                </span>
            </button>
        </div>
    </div>


    {{-- ═══════════════════════════════════════════════
         ONGOING ORDERS PANEL
    ════════════════════════════════════════════════ --}}
    <div x-show="activeTab === 'ongoing'" x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">

        @if($ongoing->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-zinc-400 dark:text-zinc-500">
                <i class="fas fa-inbox text-5xl mb-4 opacity-40"></i>
                <p class="text-sm">{{ __('No ongoing orders today.') }}</p>
            </div>
        @else
            {{-- ── Mobile Cards (< lg) ── --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 lg:hidden">
                @foreach($ongoing as $order)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 overflow-hidden">

                        {{-- Card top strip (status color) --}}
                        @php
                            $stripColors = [
                                'pending'    => 'bg-yellow-400',
                                'preparing'  => 'bg-orange-400',
                                'in_transit' => 'bg-indigo-500',
                                'delivered'  => 'bg-purple-500',
                                'completed'  => 'bg-green-500',
                                'cancelled'  => 'bg-red-500',
                            ];
                            $strip = $stripColors[$order->status] ?? 'bg-zinc-400';
                        @endphp
                        <div class="h-1 w-full {{ $strip }}"></div>

                        <div class="p-4 space-y-3">
                            {{-- Receipt + Status row --}}
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-mono text-xs text-zinc-400 dark:text-zinc-500">
                                        <i class="fas fa-receipt mr-1"></i>{{ $order->receipt_number }}
                                    </p>
                                    @php
                                        $customerBadgeClass = $order->order_type === 'walk_in'
                                            ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
                                            : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300';
                                    @endphp
                                    <span class="mt-1 inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold {{ $customerBadgeClass }}">
                                        @if($order->order_type === 'walk_in')
                                            <i class="fas fa-walking text-[10px]"></i>{{ __('Walk-In') }}
                                        @else
                                            <i class="fas fa-user-circle text-[10px]"></i>{{ $order->customer->name ?? __('N/A') }}
                                        @endif
                                    </span>
                                </div>

                                {{-- Status badge --}}
                                <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full shrink-0
                                             bg-{{ $order->status_color }}-100 text-{{ $order->status_color }}-800
                                             dark:bg-{{ $order->status_color }}-900/30 dark:text-{{ $order->status_color }}-300">
                                    @if($order->status === 'preparing')   <i class="fas fa-hourglass-start mr-1"></i>
                                    @elseif($order->status === 'pending')  <i class="fas fa-clock mr-1"></i>
                                    @elseif($order->status === 'in_transit') <i class="fas fa-truck-fast mr-1"></i>
                                    @elseif($order->status === 'delivered')  <i class="fas fa-box-open mr-1"></i>
                                    @elseif($order->status === 'completed')  <i class="fas fa-check-circle mr-1"></i>
                                    @elseif($order->status === 'cancelled')  <i class="fas fa-times-circle mr-1"></i>
                                    @endif
                                    {{ __([
                                        'preparing'  => 'Preparing',
                                        'pending'    => 'Pending',
                                        'in_transit' => 'In transit',
                                        'delivered'  => 'Delivered',
                                        'completed'  => 'Completed',
                                        'cancelled'  => 'Cancelled',
                                    ][$order->status] ?? ucfirst(str_replace('_',' ', $order->status))) }}
                                </span>
                            </div>

                            {{-- Old order badge --}}
                            @if(!$order->created_at->isToday())
                                @php $days = max(1, (int)$order->created_at->diffInDays(now())); @endphp
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300">
                                    <i class="fas fa-calendar-alt"></i>
                                    @if($days <= 7)
                                        {{ trans_choice('days_ago', $days, ['count' => $days]) }}
                                    @else
                                        {{ __('Old Order') }}
                                    @endif
                                </span>
                            @endif

                            {{-- Info grid --}}
                            <div class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-xs">
                                <div class="text-zinc-500 dark:text-zinc-400">
                                    <i class="fas fa-{{ $order->is_paid ? 'check-circle text-green-500' : 'exclamation-triangle text-red-500' }} mr-1"></i>
                                    {{ $order->is_paid ? __('Paid') : __('Unpaid') }}
                                </div>
                                <div class="text-zinc-500 dark:text-zinc-400 truncate">
                                    @if($order->order_type === 'walk_in')
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[11px] font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                            <i class="fas fa-walking text-[10px]"></i>{{ __('Walk-In') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[11px] font-semibold bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">
                                            <i class="fas fa-user-tie text-[10px]"></i>{{ $order->employee->name ?? __('N/A') }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-zinc-400 dark:text-zinc-500 col-span-2">
                                    @php $loc = app()->getLocale() === 'cn' ? 'zh_CN' : app()->getLocale(); @endphp
                                    <i class="fas fa-clock mr-1"></i>
                                    {{ $order->updated_at->locale($loc)->isoFormat('MMM D · hh:mm A') }}
                                </div>
                            </div>

                            {{-- Edit mode fields --}}
                            @if($editingOrderId === $order->id)
                                <div class="space-y-2 pt-1 border-t border-zinc-100 dark:border-zinc-700">
                                    <select wire:model="editStatus"
                                        class="w-full border rounded-lg px-2 py-1.5 text-xs bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-700">
                                        <option value="pending">{{ __('Pending') }}</option>
                                        <option value="in_transit">{{ __('In transit') }}</option>
                                        <option value="delivered">{{ __('Delivered') }}</option>
                                        <option value="completed">{{ __('Completed') }}</option>
                                        <option value="cancelled">{{ __('Cancelled') }}</option>
                                    </select>
                                    <select wire:model="editDeliveredBy"
                                        class="w-full border rounded-lg px-2 py-1.5 text-xs bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-700">
                                        <option value="">{{ __('Unassign') }}</option>
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                        @endforeach
                                    </select>
                                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs">
                                        <input type="checkbox" {{ $order->is_paid ? 'checked' : '' }}
                                               class="rounded border-zinc-300 dark:border-zinc-600 text-blue-600"
                                               wire:click="togglePaid({{ $order->id }})" />
                                        {{ $order->is_paid ? __('Mark Unpaid') : __('Mark Paid') }}
                                    </label>
                                </div>
                            @endif

                            {{-- Action bar --}}
                            <div class="flex items-center gap-1.5 pt-1 border-t border-zinc-100 dark:border-zinc-700 flex-wrap">

                                {{-- View --}}
                                <button wire:click="viewOrderDetails({{ $order->id }})"
                                    class="card-action-btn text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20">
                                    <i class="fas fa-eye"></i> {{ __('View') }}
                                </button>

                                {{-- Edit / Save --}}
                                @if($editingOrderId === $order->id)
                                    <button wire:click="saveEdit({{ $order->id }})"
                                        class="card-action-btn text-green-600 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-900/20">
                                        <i class="fas fa-save"></i> {{ __('Save') }}
                                    </button>
                                @else
                                    <button wire:click="editOrder({{ $order->id }})"
                                        class="card-action-btn text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-700">
                                        <i class="fas fa-edit"></i> {{ __('Edit') }}
                                    </button>
                                @endif

                                {{-- State-driven action --}}
                                @if($order->status === 'pending')
                                    @php $deliveryStatus = $this->getDeliveryPersonStatus($order->id); @endphp

                                    @if($deliveryStatus === 'no_person')
                                        <span class="card-action-btn text-zinc-400 opacity-50 cursor-not-allowed">
                                            <i class="fas fa-user-slash"></i> {{ __('No Staff') }}
                                        </span>
                                    @elseif($deliveryStatus === 'available')
                                        <button wire:click="startDelivery({{ $order->id }})" @disabled($editingOrderId)
                                            class="card-action-btn text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-900/20">
                                            <i class="fas fa-truck"></i> {{ __('Deliver') }}
                                        </button>
                                    @elseif($deliveryStatus === 'batch_preparing' || $deliveryStatus === 'busy')
                                        <button wire:click="startDelivery({{ $order->id }})" @disabled($editingOrderId)
                                            class="card-action-btn text-yellow-600 hover:bg-yellow-50 dark:text-yellow-400 dark:hover:bg-yellow-900/20">
                                            <i class="fas fa-plus-circle"></i> {{ __('Add Delivery') }}
                                        </button>
                                    @elseif($deliveryStatus === 'preparing')
                                        <span class="card-action-btn text-yellow-600 dark:text-yellow-400">
                                            <i class="fas fa-hourglass-half"></i> {{ __('In Batch') }}
                                        </span>
                                    @elseif($deliveryStatus === 'waiting')
                                        <span class="card-action-btn text-purple-600 dark:text-purple-400 opacity-75">
                                            <i class="fas fa-clock-rotate-left"></i> {{ __('In Queue') }}
                                        </span>
                                    @else
                                        <span class="card-action-btn text-orange-600 dark:text-orange-400 opacity-75">
                                            <i class="fas fa-clock"></i> {{ __('Busy') }}
                                        </span>
                                    @endif

                                @elseif($order->status === 'preparing')
                                    @php
                                        $employeeId = $order->delivered_by;
                                        $batchInfo  = $this->getBatchInfo($employeeId);
                                        $remainingTime = $batchInfo['remaining_time'] ?? 0;
                                    @endphp
                                    @if($employeeId)
                                        <div x-data="{
                                                r: {{ $remainingTime }},
                                                started: false,
                                                tick() {
                                                    if (this.started) return;
                                                    this.started = true;
                                                    let iv = setInterval(() => {
                                                        if (this.r > 0) { this.r--; }
                                                        else { clearInterval(iv); $wire.processBatchDelivery({{ $employeeId }}); }
                                                    }, 1000);
                                                }
                                             }"
                                             x-init="tick()"
                                             class="card-action-btn text-yellow-600 dark:text-yellow-400">
                                            <i class="fas fa-hourglass-half"></i>
                                            {{ __('Preparing') }}
                                            <span class="font-mono text-[10px] bg-yellow-100 dark:bg-yellow-900/30 px-1 rounded ml-1"
                                                  x-text="Math.floor(r/60)+':'+String(r%60).padStart(2,'0')"></span>
                                        </div>
                                    @else
                                        <span class="card-action-btn text-orange-600 dark:text-orange-400">
                                            <i class="fas fa-user-slash"></i> {{ __('No Staff') }}
                                        </span>
                                    @endif

                                @elseif($order->status === 'in_transit')
                                    <button wire:click="markDelivered({{ $order->id }})" @disabled($editingOrderId)
                                        class="card-action-btn text-purple-600 hover:bg-purple-50 dark:text-purple-400 dark:hover:bg-purple-900/20">
                                        <i class="fas fa-box-open"></i> {{ __('Delivered') }}
                                    </button>

                                @elseif($order->status === 'delivered' && !$order->is_paid)
                                    <button wire:click="togglePaid({{ $order->id }})" @disabled($editingOrderId)
                                        class="card-action-btn text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20">
                                        <i class="fas fa-money-bill-transfer"></i> {{ __('Paid') }}
                                    </button>

                                @elseif($order->status === 'delivered' && $order->is_paid)
                                    <button wire:click="markFinished({{ $order->id }})" @disabled($editingOrderId)
                                        class="card-action-btn text-green-600 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-900/20">
                                        <i class="fas fa-check-double"></i> {{ __('Complete') }}
                                    </button>
                                @endif

                                {{-- Cancel / Delete --}}
                                @if($order->status === 'preparing')
                                    <button wire:click="cancelPrepare({{ $order->id }})"
                                        class="card-action-btn text-orange-600 hover:bg-orange-50 dark:text-orange-400 dark:hover:bg-orange-900/20 ml-auto">
                                        <i class="fas fa-ban"></i> {{ __('Cancel') }}
                                    </button>
                                @else
                                    <button wire:click="confirmDelete({{ $order->id }})"
                                        class="card-action-btn text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 ml-auto">
                                        <i class="fas fa-trash"></i> {{ __('Delete') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ── Desktop Table (≥ lg) ── --}}
            <div class="hidden lg:block bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/60">
                            <tr>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Order Number') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Customer Name') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Payment') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Delivered By') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Date & Time') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach($ongoing as $order)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/40 transition-colors">

                                    {{-- Receipt --}}
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="font-mono text-sm text-zinc-800 dark:text-zinc-200">
                                            <i class="fas fa-receipt mr-1 text-zinc-400"></i>{{ $order->receipt_number }}
                                        </span>
                                    </td>

                                    {{-- Customer --}}
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-800 dark:text-zinc-200">
                                        @if ($order->order_type === 'walk_in')
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                <i class="fas fa-walking text-[10px]"></i>{{ __('Walk-In') }}
                                            </span>
                                        @else
                                            <i class="fas fa-user-circle mr-1 text-zinc-400"></i>{{ $order->customer->name ?? __('N/A') }}
                                        @endif
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @if($editingOrderId === $order->id)
                                            <select wire:model="editStatus"
                                                class="border rounded-lg px-2 py-1 text-xs bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-700">
                                                <option value="pending">{{ __('Pending') }}</option>
                                                <option value="in_transit">{{ __('In transit') }}</option>
                                                <option value="delivered">{{ __('Delivered') }}</option>
                                                <option value="completed">{{ __('Completed') }}</option>
                                                <option value="cancelled">{{ __('Cancelled') }}</option>
                                            </select>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full
                                                         bg-{{ $order->status_color }}-100 text-{{ $order->status_color }}-800
                                                         dark:bg-{{ $order->status_color }}-900/30 dark:text-{{ $order->status_color }}-300">
                                                @if($order->status === 'preparing')   <i class="fas fa-hourglass-start"></i>
                                                @elseif($order->status === 'pending')  <i class="fas fa-clock"></i>
                                                @elseif($order->status === 'in_transit') <i class="fas fa-truck-fast"></i>
                                                @elseif($order->status === 'delivered')  <i class="fas fa-box-open"></i>
                                                @elseif($order->status === 'completed')  <i class="fas fa-check-circle"></i>
                                                @elseif($order->status === 'cancelled')  <i class="fas fa-times-circle"></i>
                                                @endif
                                                {{ __([
                                                    'preparing'  => 'Preparing',
                                                    'pending'    => 'Pending',
                                                    'in_transit' => 'In transit',
                                                    'delivered'  => 'Delivered',
                                                    'completed'  => 'Completed',
                                                    'cancelled'  => 'Cancelled',
                                                ][$order->status] ?? ucfirst(str_replace('_',' ', $order->status))) }}
                                            </span>
                                            @if(!$order->created_at->isToday())
                                                @php $days = max(1, (int)$order->created_at->diffInDays(now())); @endphp
                                                <span class="block mt-1 text-xs px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300">
                                                    <i class="fas fa-calendar-alt mr-1"></i>
                                                    @if($days <= 7) {{ trans_choice('days_ago', $days, ['count' => $days]) }}
                                                    @else {{ __('Old Order') }}
                                                    @endif
                                                </span>
                                            @endif
                                        @endif
                                    </td>

                                    {{-- Payment --}}
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @if($editingOrderId === $order->id)
                                            <div class="flex flex-col items-center gap-1.5">
                                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full
                                                    {{ $order->is_paid ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' }}">
                                                    <i class="fas fa-{{ $order->is_paid ? 'check-circle' : 'exclamation-triangle' }}"></i>
                                                    {{ $order->is_paid ? __('Paid') : __('Unpaid') }}
                                                </span>
                                                <label class="inline-flex items-center gap-1 cursor-pointer text-xs text-zinc-600 dark:text-zinc-400">
                                                    <input type="checkbox" {{ $order->is_paid ? 'checked' : '' }}
                                                           class="rounded border-zinc-300 dark:border-zinc-600 text-blue-600"
                                                           wire:click="togglePaid({{ $order->id }})" />
                                                    {{ $order->is_paid ? __('Mark Unpaid') : __('Mark Paid') }}
                                                </label>
                                            </div>
                                        @else
                                            @if($order->is_paid)
                                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                    <i class="fas fa-check-circle"></i> {{ __('Paid') }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                                    <i class="fas fa-exclamation-triangle"></i> {{ __('Unpaid') }}
                                                </span>
                                            @endif
                                        @endif
                                    </td>

                                    {{-- Delivered by --}}
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-800 dark:text-zinc-200">
                                        @if($editingOrderId === $order->id)
                                            <select wire:model="editDeliveredBy"
                                                class="w-full border rounded-lg px-2 py-1 text-xs bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-700">
                                                <option value="">{{ __('Unassign') }}</option>
                                                @foreach($employees as $emp)
                                                    <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            @if($order->order_type === 'walk_in')
                                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                    <i class="fas fa-walking"></i> Walk-In
                                                </span>
                                            @else
                                                <i class="fas fa-user-tie mr-1 text-zinc-400"></i>{{ $order->employee->name ?? __('N/A') }}
                                            @endif
                                        @endif
                                    </td>

                                    {{-- Date --}}
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @php $loc = app()->getLocale() === 'cn' ? 'zh_CN' : app()->getLocale(); @endphp
                                        <time class="text-xs text-zinc-500 dark:text-zinc-400" datetime="{{ $order->updated_at->toIso8601String() }}">
                                            <span class="block">{{ $order->updated_at->locale($loc)->isoFormat('LL') }}</span>
                                            <span class="block">{{ $order->updated_at->locale($loc)->isoFormat('hh:mm A') }}</span>
                                        </time>
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1">

                                            {{-- View --}}
                                            <button wire:click="viewOrderDetails({{ $order->id }})"
                                                class="tbl-action-btn text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20"
                                                title="{{ __('View') }}">
                                                <i class="fas fa-eye text-base"></i>
                                                <span class="text-xs">{{ __('View') }}</span>
                                            </button>

                                            {{-- Edit / Save --}}
                                            @if($editingOrderId === $order->id)
                                                <button wire:click="saveEdit({{ $order->id }})"
                                                    class="tbl-action-btn text-green-600 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-900/20"
                                                    title="{{ __('Save') }}">
                                                    <i class="fas fa-save text-base"></i>
                                                    <span class="text-xs">{{ __('Save') }}</span>
                                                </button>
                                            @else
                                                <button wire:click="editOrder({{ $order->id }})"
                                                    class="tbl-action-btn text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-700"
                                                    title="{{ __('Edit') }}">
                                                    <i class="fas fa-edit text-base"></i>
                                                    <span class="text-xs">{{ __('Edit') }}</span>
                                                </button>
                                            @endif

                                            {{-- State-driven action --}}
                                            @if($order->status === 'pending')
                                                @php $deliveryStatus = $this->getDeliveryPersonStatus($order->id); @endphp

                                                @if($deliveryStatus === 'no_person')
                                                    <span class="tbl-action-btn text-zinc-400 opacity-50 cursor-not-allowed">
                                                        <i class="fas fa-user-slash text-base"></i>
                                                        <span class="text-xs">{{ __('No Staff') }}</span>
                                                    </span>
                                                @elseif($deliveryStatus === 'available')
                                                    <button wire:click="startDelivery({{ $order->id }})" @disabled($editingOrderId)
                                                        class="tbl-action-btn text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-900/20">
                                                        <i class="fas fa-truck text-base"></i>
                                                        <span class="text-xs">{{ __('Deliver') }}</span>
                                                    </button>
                                                @elseif($deliveryStatus === 'batch_preparing' || $deliveryStatus === 'busy')
                                                    <button wire:click="startDelivery({{ $order->id }})" @disabled($editingOrderId)
                                                        class="tbl-action-btn text-yellow-600 hover:bg-yellow-50 dark:text-yellow-400 dark:hover:bg-yellow-900/20">
                                                        <i class="fas fa-plus-circle text-base"></i>
                                                        <span class="text-xs">{{ __('Add Delivery') }}</span>
                                                    </button>
                                                @elseif($deliveryStatus === 'preparing')
                                                    <span class="tbl-action-btn text-yellow-600 dark:text-yellow-400">
                                                        <i class="fas fa-hourglass-half text-base"></i>
                                                        <span class="text-xs">{{ __('In Batch') }}</span>
                                                    </span>
                                                @elseif($deliveryStatus === 'waiting')
                                                    <span class="tbl-action-btn text-purple-600 dark:text-purple-400 opacity-75">
                                                        <i class="fas fa-clock-rotate-left text-base"></i>
                                                        <span class="text-xs">{{ __('In Queue') }}</span>
                                                    </span>
                                                @else
                                                    <span class="tbl-action-btn text-orange-600 dark:text-orange-400 opacity-75">
                                                        <i class="fas fa-clock text-base"></i>
                                                        <span class="text-xs">{{ __('Busy') }}</span>
                                                    </span>
                                                @endif

                                            @elseif($order->status === 'preparing')
                                                @php
                                                    $employeeId    = $order->delivered_by;
                                                    $batchInfo     = $this->getBatchInfo($employeeId);
                                                    $remainingTime = $batchInfo['remaining_time'] ?? 0;
                                                @endphp
                                                @if($employeeId)
                                                    <div x-data="{
                                                            r: {{ $remainingTime }},
                                                            started: false,
                                                            tick() {
                                                                if (this.started) return;
                                                                this.started = true;
                                                                let iv = setInterval(() => {
                                                                    if (this.r > 0) { this.r--; }
                                                                    else { clearInterval(iv); $wire.processBatchDelivery({{ $employeeId }}); }
                                                                }, 1000);
                                                            }
                                                        }"
                                                        x-init="tick()"
                                                        class="tbl-action-btn text-yellow-600 dark:text-yellow-400">
                                                        <i class="fas fa-hourglass-half text-base"></i>
                                                        <span class="text-xs">{{ __('Preparing') }}</span>
                                                        <span class="font-mono text-[10px] bg-yellow-100 dark:bg-yellow-900/30 px-1 rounded"
                                                              x-text="Math.floor(r/60)+':'+String(r%60).padStart(2,'0')"></span>
                                                    </div>
                                                @else
                                                    <span class="tbl-action-btn text-orange-600 dark:text-orange-400">
                                                        <i class="fas fa-user-slash text-base"></i>
                                                        <span class="text-xs">{{ __('No Staff') }}</span>
                                                    </span>
                                                @endif

                                            @elseif($order->status === 'in_transit')
                                                <button wire:click="markDelivered({{ $order->id }})" @disabled($editingOrderId)
                                                    class="tbl-action-btn text-purple-600 hover:bg-purple-50 dark:text-purple-400 dark:hover:bg-purple-900/20">
                                                    <i class="fas fa-box-open text-base"></i>
                                                    <span class="text-xs">{{ __('Delivered') }}</span>
                                                </button>

                                            @elseif($order->status === 'delivered' && !$order->is_paid)
                                                <button wire:click="togglePaid({{ $order->id }})" @disabled($editingOrderId)
                                                    class="tbl-action-btn text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20">
                                                    <i class="fas fa-money-bill-transfer text-base"></i>
                                                    <span class="text-xs">{{ __('Paid') }}</span>
                                                </button>

                                            @elseif($order->status === 'delivered' && $order->is_paid)
                                                <button wire:click="markFinished({{ $order->id }})" @disabled($editingOrderId)
                                                    class="tbl-action-btn text-green-600 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-900/20">
                                                    <i class="fas fa-check-double text-base"></i>
                                                    <span class="text-xs">{{ __('Complete') }}</span>
                                                </button>

                                            @else
                                                <div class="w-16 h-10"></div>
                                            @endif

                                            {{-- Cancel / Delete --}}
                                            @if($order->status === 'preparing')
                                                <button wire:click="cancelPrepare({{ $order->id }})"
                                                    class="tbl-action-btn text-orange-600 hover:bg-orange-50 dark:text-orange-400 dark:hover:bg-orange-900/20">
                                                    <i class="fas fa-ban text-base"></i>
                                                    <span class="text-xs">{{ __('Cancel') }}</span>
                                                </button>
                                            @else
                                                <button wire:click="confirmDelete({{ $order->id }})"
                                                    class="tbl-action-btn text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20">
                                                    <i class="fas fa-trash text-base"></i>
                                                    <span class="text-xs">{{ __('Delete') }}</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>


    {{-- ═══════════════════════════════════════════════
         COMPLETED / CANCELLED ORDERS PANEL
    ════════════════════════════════════════════════ --}}
    <div x-show="activeTab === 'completed'" x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">

        @if($completed->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-zinc-400 dark:text-zinc-500">
                <i class="fas fa-archive text-5xl mb-4 opacity-40"></i>
                <p class="text-sm">{{ __('No completed or cancelled orders today.') }}</p>
            </div>
        @else
            {{-- ── Mobile Cards (< lg) ── --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 lg:hidden">
                @foreach($completed as $order)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 overflow-hidden opacity-90">
                        @php
                            $stripColors = [
                                'completed' => 'bg-green-500',
                                'cancelled' => 'bg-red-400',
                            ];
                            $strip = $stripColors[$order->status] ?? 'bg-zinc-400';
                        @endphp
                        <div class="h-1 w-full {{ $strip }}"></div>

                        <div class="p-4 space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-mono text-xs text-zinc-400 dark:text-zinc-500">
                                        <i class="fas fa-receipt mr-1"></i>{{ $order->receipt_number }}
                                    </p>
                                    @php
                                        $customerBadgeClass = $order->order_type === 'walk_in'
                                            ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
                                            : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300';
                                    @endphp
                                    <span class="mt-1 inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold {{ $customerBadgeClass }}">
                                        @if($order->order_type === 'walk_in')
                                            <i class="fas fa-walking text-[10px]"></i>{{ __('Walk-In') }}
                                        @else
                                            <i class="fas fa-user-circle text-[10px]"></i>{{ $order->customer->name ?? __('N/A') }}
                                        @endif
                                    </span>
                                </div>
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full shrink-0
                                             bg-{{ $order->status_color }}-100 text-{{ $order->status_color }}-800
                                             dark:bg-{{ $order->status_color }}-900/30 dark:text-{{ $order->status_color }}-300">
                                    @if($order->status === 'completed') <i class="fas fa-check-circle"></i>
                                    @elseif($order->status === 'cancelled') <i class="fas fa-times-circle"></i>
                                    @endif
                                    {{ __([
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                    ][$order->status] ?? ucfirst(str_replace('_',' ', $order->status))) }}
                                </span>
                            </div>

                            @if(!$order->created_at->isToday())
                                @php $days = max(1, (int)$order->created_at->diffInDays(now())); @endphp
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300">
                                    <i class="fas fa-calendar-alt"></i>
                                    @if($days <= 7) {{ trans_choice('days_ago', $days, ['count' => $days]) }}
                                    @else {{ __('Old Order') }}
                                    @endif
                                </span>
                            @endif

                            <div class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-xs">
                                <div class="text-zinc-500 dark:text-zinc-400">
                                    <i class="fas fa-{{ $order->is_paid ? 'check-circle text-green-500' : 'exclamation-triangle text-red-500' }} mr-1"></i>
                                    {{ $order->is_paid ? __('Paid') : __('Unpaid') }}
                                </div>
                                <div class="text-zinc-500 dark:text-zinc-400 truncate">
                                    @if($order->order_type === 'walk_in')
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[11px] font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                            <i class="fas fa-walking text-[10px]"></i>{{ __('Walk-In') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[11px] font-semibold bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">
                                            <i class="fas fa-user-tie text-[10px]"></i>{{ $order->employee->name ?? __('N/A') }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-zinc-400 dark:text-zinc-500 col-span-2">
                                    @php $loc = app()->getLocale() === 'cn' ? 'zh_CN' : app()->getLocale(); @endphp
                                    <i class="fas fa-clock mr-1"></i>
                                    {{ $order->updated_at->locale($loc)->isoFormat('MMM D · hh:mm A') }}
                                </div>
                            </div>

                            @if($editingOrderId === $order->id)
                                <div class="space-y-2 pt-1 border-t border-zinc-100 dark:border-zinc-700">
                                    <select wire:model="editStatus"
                                        class="w-full border rounded-lg px-2 py-1.5 text-xs bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-700">
                                        <option value="pending">{{ __('Pending') }}</option>
                                        <option value="in_transit">{{ __('In transit') }}</option>
                                        <option value="delivered">{{ __('Delivered') }}</option>
                                        <option value="completed">{{ __('Completed') }}</option>
                                        <option value="cancelled">{{ __('Cancelled') }}</option>
                                    </select>
                                    <select wire:model="editDeliveredBy"
                                        class="w-full border rounded-lg px-2 py-1.5 text-xs bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-700">
                                        <option value="">{{ __('Unassign') }}</option>
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                        @endforeach
                                    </select>
                                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs">
                                        <input type="checkbox" {{ $order->is_paid ? 'checked' : '' }}
                                               class="rounded border-zinc-300 dark:border-zinc-600 text-blue-600"
                                               wire:click="togglePaid({{ $order->id }})" />
                                        {{ $order->is_paid ? __('Mark Unpaid') : __('Mark Paid') }}
                                    </label>
                                </div>
                            @endif

                            <div class="flex items-center gap-1.5 pt-1 border-t border-zinc-100 dark:border-zinc-700">
                                <button wire:click="viewOrderDetails({{ $order->id }})"
                                    class="card-action-btn text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20">
                                    <i class="fas fa-eye"></i> {{ __('View') }}
                                </button>
                                @if($editingOrderId === $order->id)
                                    <button wire:click="saveEdit({{ $order->id }})"
                                        class="card-action-btn text-green-600 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-900/20">
                                        <i class="fas fa-save"></i> {{ __('Save') }}
                                    </button>
                                @else
                                    <button wire:click="editOrder({{ $order->id }})"
                                        class="card-action-btn text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-700">
                                        <i class="fas fa-edit"></i> {{ __('Edit') }}
                                    </button>
                                @endif
                                <button wire:click="confirmDelete({{ $order->id }})"
                                    class="card-action-btn text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 ml-auto">
                                    <i class="fas fa-trash"></i> {{ __('Delete') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ── Desktop Table (≥ lg) ── --}}
            <div class="hidden lg:block bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/60">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Order Number') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Customer Name') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Payment') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Delivered By') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Date & Time') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach($completed as $order)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/40 transition-colors opacity-90">

                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="font-mono text-sm text-zinc-700 dark:text-zinc-300">
                                            <i class="fas fa-receipt mr-1 text-zinc-400"></i>{{ $order->receipt_number }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-700 dark:text-zinc-300">
                                        @if($order->order_type === 'walk_in')
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                <i class="fas fa-walking text-[10px]"></i>{{ __('Walk-In') }}
                                            </span>
                                        @else
                                            <i class="fas fa-user-circle mr-1 text-zinc-400"></i>{{ $order->customer->name ?? __('N/A') }}
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @if($editingOrderId === $order->id)
                                            <select wire:model="editStatus"
                                                class="border rounded-lg px-2 py-1 text-xs bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-700">
                                                <option value="pending">{{ __('Pending') }}</option>
                                                <option value="in_transit">{{ __('In transit') }}</option>
                                                <option value="delivered">{{ __('Delivered') }}</option>
                                                <option value="completed">{{ __('Completed') }}</option>
                                                <option value="cancelled">{{ __('Cancelled') }}</option>
                                            </select>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full
                                                         bg-{{ $order->status_color }}-100 text-{{ $order->status_color }}-800
                                                         dark:bg-{{ $order->status_color }}-900/30 dark:text-{{ $order->status_color }}-300">
                                                @if($order->status === 'completed') <i class="fas fa-check-circle"></i>
                                                @elseif($order->status === 'cancelled') <i class="fas fa-times-circle"></i>
                                                @endif
                                                {{ __([
                                                    'preparing'  => 'Preparing',
                                                    'pending'    => 'Pending',
                                                    'in_transit' => 'In transit',
                                                    'delivered'  => 'Delivered',
                                                    'completed'  => 'Completed',
                                                    'cancelled'  => 'Cancelled',
                                                ][$order->status] ?? ucfirst(str_replace('_',' ', $order->status))) }}
                                            </span>
                                            @if(!$order->created_at->isToday())
                                                @php $days = max(1, (int)$order->created_at->diffInDays(now())); @endphp
                                                <span class="block mt-1 text-xs px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300">
                                                    <i class="fas fa-calendar-alt mr-1"></i>
                                                    @if($days <= 7) {{ trans_choice('days_ago', $days, ['count' => $days]) }}
                                                    @else {{ __('Old Order') }}
                                                    @endif
                                                </span>
                                            @endif
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @if($editingOrderId === $order->id)
                                            <div class="flex flex-col items-center gap-1.5">
                                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full
                                                    {{ $order->is_paid ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' }}">
                                                    <i class="fas fa-{{ $order->is_paid ? 'check-circle' : 'exclamation-triangle' }}"></i>
                                                    {{ $order->is_paid ? __('Paid') : __('Unpaid') }}
                                                </span>
                                                <label class="inline-flex items-center gap-1 cursor-pointer text-xs text-zinc-600 dark:text-zinc-400">
                                                    <input type="checkbox" {{ $order->is_paid ? 'checked' : '' }}
                                                           class="rounded border-zinc-300 dark:border-zinc-600 text-blue-600"
                                                           wire:click="togglePaid({{ $order->id }})" />
                                                    {{ $order->is_paid ? __('Mark Unpaid') : __('Mark Paid') }}
                                                </label>
                                            </div>
                                        @else
                                            @if($order->is_paid)
                                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                    <i class="fas fa-check-circle"></i> {{ __('Paid') }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                                    <i class="fas fa-exclamation-triangle"></i> {{ __('Unpaid') }}
                                                </span>
                                            @endif
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-700 dark:text-zinc-300">
                                        @if($editingOrderId === $order->id)
                                            <select wire:model="editDeliveredBy"
                                                class="w-full border rounded-lg px-2 py-1 text-xs bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-700">
                                                <option value="">{{ __('Unassign') }}</option>
                                                @foreach($employees as $emp)
                                                    <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <i class="fas fa-user-tie mr-1 text-zinc-400"></i>{{ $order->employee->name ?? __('N/A') }}
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @php $loc = app()->getLocale() === 'cn' ? 'zh_CN' : app()->getLocale(); @endphp
                                        <time class="text-xs text-zinc-500 dark:text-zinc-400" datetime="{{ $order->updated_at->toIso8601String() }}">
                                            <span class="block">{{ $order->updated_at->locale($loc)->isoFormat('LL') }}</span>
                                            <span class="block">{{ $order->updated_at->locale($loc)->isoFormat('hh:mm A') }}</span>
                                        </time>
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1">
                                            <button wire:click="viewOrderDetails({{ $order->id }})"
                                                class="tbl-action-btn text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20">
                                                <i class="fas fa-eye text-base"></i>
                                                <span class="text-xs">{{ __('View') }}</span>
                                            </button>

                                            @if($editingOrderId === $order->id)
                                                <button wire:click="saveEdit({{ $order->id }})"
                                                    class="tbl-action-btn text-green-600 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-900/20">
                                                    <i class="fas fa-save text-base"></i>
                                                    <span class="text-xs">{{ __('Save') }}</span>
                                                </button>
                                            @else
                                                <button wire:click="editOrder({{ $order->id }})"
                                                    class="tbl-action-btn text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-700">
                                                    <i class="fas fa-edit text-base"></i>
                                                    <span class="text-xs">{{ __('Edit') }}</span>
                                                </button>
                                            @endif

                                            <button wire:click="confirmDelete({{ $order->id }})"
                                                class="tbl-action-btn text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20">
                                                <i class="fas fa-trash text-base"></i>
                                                <span class="text-xs">{{ __('Delete') }}</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>


    {{-- ═══════════════════════════════════════════════
         ORDER DETAILS MODAL
    ════════════════════════════════════════════════ --}}
    @if($showOrderDetailsModal && $selectedOrder)
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4 z-50">
            <div class="bg-white dark:bg-zinc-800 w-full sm:rounded-2xl sm:max-w-3xl max-h-[92dvh] overflow-y-auto shadow-2xl">

                {{-- Modal header --}}
                <div class="sticky top-0 flex items-center justify-between px-5 py-4 border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 z-10">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <i class="fas fa-file-invoice text-blue-500"></i>{{ __('Order Details') }}
                        <span class="font-mono text-xs text-zinc-400">{{ $selectedOrder->receipt_number }}</span>
                    </h3>
                    <button wire:click="closeOrderDetailsModal"
                        class="cursor-pointer w-8 h-8 flex items-center justify-center rounded-full text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-5 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                        {{-- Order Info --}}
                        <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-xl p-4 space-y-3">
                            <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-shopping-bag mr-1"></i>{{ __('Order Information') }}
                            </h4>
                            <dl class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <dt class="text-zinc-500 dark:text-zinc-400"><i class="fas fa-hashtag mr-1"></i>{{ __('Order ID') }}</dt>
                                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">#{{ $selectedOrder->id }}</dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-zinc-500 dark:text-zinc-400"><i class="fas fa-receipt mr-1"></i>{{ __('Receipt Number') }}</dt>
                                    <dd class="font-mono font-medium text-zinc-900 dark:text-zinc-100">{{ $selectedOrder->receipt_number }}</dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-zinc-500 dark:text-zinc-400"><i class="fas fa-user-tie mr-1"></i>{{ __('Delivered By') }}</dt>
                                    <dd class="text-zinc-900 dark:text-zinc-100">{{ $selectedOrder->employee->name ?? 'N/A' }}</dd>
                                </div>
                                <div class="flex justify-between text-sm items-center">
                                    <dt class="text-zinc-500 dark:text-zinc-400"><i class="fas fa-info-circle mr-1"></i>{{ __('Status') }}</dt>
                                    <dd>
                                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full
                                                     bg-{{ $selectedOrder->status_color }}-100 text-{{ $selectedOrder->status_color }}-800
                                                     dark:bg-{{ $selectedOrder->status_color }}-900/30 dark:text-{{ $selectedOrder->status_color }}-300">
                                            {{ __([
                                                'preparing'  => 'Preparing',
                                                'pending'    => 'Pending',
                                                'in_transit' => 'In transit',
                                                'delivered'  => 'Delivered',
                                                'completed'  => 'Completed',
                                                'cancelled'  => 'Cancelled',
                                            ][$selectedOrder->status] ?? ucfirst(str_replace('_',' ', $selectedOrder->status))) }}
                                        </span>
                                    </dd>
                                </div>
                                <div class="flex justify-between text-sm items-center">
                                    <dt class="text-zinc-500 dark:text-zinc-400"><i class="fas fa-credit-card mr-1"></i>{{ __('Payment Status') }}</dt>
                                    <dd>
                                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full
                                            {{ $selectedOrder->is_paid ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' }}">
                                            <i class="fas fa-{{ $selectedOrder->is_paid ? 'check-circle' : 'exclamation-triangle' }}"></i>
                                            {{ $selectedOrder->is_paid ? __('Paid') : __('Unpaid') }}
                                        </span>
                                    </dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-zinc-500 dark:text-zinc-400"><i class="fas fa-money-bill mr-1"></i>{{ __('Payment Method') }}</dt>
                                    <dd class="text-zinc-900 dark:text-zinc-100">
                                        @php
                                            $map = ['cash' => 'Cash', 'online' => 'Online'];
                                            $code = strtolower($selectedOrder->payment_type ?? '');
                                            $label = $map[$code] ?? ($selectedOrder->payment_type ?? __('N/A'));
                                        @endphp
                                        {{ __($label) }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        {{-- Customer Info --}}
                        <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-xl p-4 space-y-3">
                            <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-user mr-1"></i>{{ __('Customer Information') }}
                            </h4>
                            @if($selectedOrder->customer)
                                <dl class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <dt class="text-zinc-500 dark:text-zinc-400"><i class="fas fa-id-badge mr-1"></i>{{ __('Name') }}</dt>
                                        <dd class="text-zinc-900 dark:text-zinc-100">{{ $selectedOrder->customer->name }}</dd>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <dt class="text-zinc-500 dark:text-zinc-400"><i class="fas fa-map-marker-alt mr-1"></i>{{ __('Unit & Address') }}</dt>
                                        <dd class="text-zinc-900 dark:text-zinc-100 text-right">
                                            @if($selectedOrder->customer->unit || $selectedOrder->customer->address)
                                                {{ implode(', ', array_filter([$selectedOrder->customer->unit, $selectedOrder->customer->address])) }}
                                            @else
                                                {{ __('Not Provided') }}
                                            @endif
                                        </dd>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <dt class="text-zinc-500 dark:text-zinc-400"><i class="fas fa-phone mr-1"></i>{{ __('Contact Number') }}</dt>
                                        <dd class="text-zinc-900 dark:text-zinc-100">{{ $selectedOrder->customer->contact_number ?? __('N/A') }}</dd>
                                    </div>
                                </dl>
                            @else
                                <p class="text-sm text-zinc-500 dark:text-zinc-400 flex items-center gap-1">
                                    <i class="fas fa-user-slash"></i>{{ __('No customer information available.') }}
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- Items --}}
                    <div>
                        <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">
                            <i class="fas fa-shopping-basket mr-1"></i>{{ __('Ordered Items') }}
                        </h4>
                        @if($selectedOrder->orderItems && count($selectedOrder->orderItems) > 0)
                            <div class="rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-700">
                                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700 text-sm">
                                    <thead class="bg-zinc-100 dark:bg-zinc-900/60">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-zinc-500 uppercase">ID</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-zinc-500 uppercase">{{ __('Product') }}</th>
                                            <th class="px-4 py-2.5 text-center text-xs font-semibold text-zinc-500 uppercase">{{ __('Quantity') }}</th>
                                            <th class="px-4 py-2.5 text-center text-xs font-semibold text-zinc-500 uppercase">{{ __('Unit Price') }}</th>
                                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-zinc-500 uppercase">{{ __('Total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-100 dark:divide-zinc-700">
                                        @foreach($selectedOrder->orderItems as $item)
                                            <tr>
                                                <td class="px-4 py-2.5 text-zinc-500 dark:text-zinc-400 text-xs">#{{ $item->product->id ?? __('N/A') }}</td>
                                                <td class="px-4 py-2.5 text-zinc-900 dark:text-zinc-100">{{ $item->product->name ?? '#' }}</td>
                                                <td class="px-4 py-2.5 text-center text-zinc-700 dark:text-zinc-300">{{ $item->quantity }}</td>
                                                <td class="px-4 py-2.5 text-center text-zinc-700 dark:text-zinc-300">₱{{ number_format($item->unit_price, 2) }}</td>
                                                <td class="px-4 py-2.5 text-right font-semibold text-zinc-900 dark:text-zinc-100">₱{{ number_format($item->total_price, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3 flex justify-between items-center px-2">
                                <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Total Amount') }}</span>
                                <span class="text-xl font-bold text-zinc-900 dark:text-zinc-100">₱{{ number_format($selectedOrder->order_total, 2) }}</span>
                            </div>
                        @else
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 flex items-center gap-1">
                                <i class="fas fa-exclamation-triangle"></i>{{ __('No items found for this order.') }}
                            </p>
                        @endif
                    </div>
                </div>

                <div class="sticky bottom-0 flex justify-end px-5 py-4 bg-zinc-50 dark:bg-zinc-900/80 border-t border-zinc-200 dark:border-zinc-700">
                    <button wire:click="closeOrderDetailsModal"
                        class="cursor-pointer px-5 py-2 text-sm font-semibold rounded-xl border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                        <i class="fas fa-times mr-1"></i>{{ __('Close') }}
                    </button>
                </div>
            </div>
        </div>
    @endif


    {{-- ═══════════════════════════════════════════════
         DELETE CONFIRMATION MODAL
    ════════════════════════════════════════════════ --}}
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-sm bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <i class="fas fa-triangle-exclamation text-red-500"></i>{{ __('Confirm Deletion') }}
                    </h3>
                </div>
                <div class="px-5 py-4 space-y-1.5">
                    <p class="text-sm text-zinc-700 dark:text-zinc-300">
                        {{ __('Are you sure you want to delete this order? This action can\'t be undone.') }}
                    </p>
                    @if($deleteReceipt)
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 font-mono">
                            {{ __('Receipt #') }}: {{ $deleteReceipt }}
                        </p>
                    @endif
                </div>
                <div class="px-5 py-4 bg-zinc-50 dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-700 flex justify-end gap-2">
                    <button wire:click="closeDeleteModal"
                        class="cursor-pointer px-4 py-2 text-sm rounded-xl border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                        {{ __('Cancel') }}
                    </button>
                    <button wire:click="deleteOrderConfirmed"
                        class="cursor-pointer px-4 py-2 text-sm font-semibold rounded-xl bg-red-600 text-white hover:bg-red-700 active:scale-95 transition-all">
                        <i class="fas fa-trash mr-1"></i>{{ __('Delete') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Utility classes (injected inline so Livewire keeps a single root element) --}}
    <style>
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
</div>
