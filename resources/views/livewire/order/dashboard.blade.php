@section('title', 'Order Dashboard')
<div class="container mx-auto p-1">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                <i class="fas fa-shopping-cart mr-2"></i>Orders for {{ $today }}
            </h2>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">Manage today's orders, payments, and status updates.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1 px-2 py-1 text-s rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                <i class="fas fa-clock"></i>
                Ongoing: {{ $ongoingCount }}
            </span>
            <span class="inline-flex items-center gap-1 px-2 py-1 text-s rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                <i class="fas fa-check-circle"></i>
                Completed: {{ $completedCount }}
            </span>

            {{-- create order --}}
            <a href="{{ route('orders.create') }}" class="flex items-center gap-1" wire:navigate>
                <button type="button" class="cursor-pointer ml-6 mr-10 inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 active:bg-blue-800 transition dark:bg-blue-500 dark:hover:bg-blue-600">
                    <i class="fas fa-plus"></i>
                    <span>Create Order</span>
                </button>
            </a>
        </div>
    </div>

    {{-- Ongoing Orders Table --}}
    <div class="mb-8">
        <h3 class="text-xl font-semibold mb-3 text-zinc-900 dark:text-zinc-100">
            <i class="fas fa-hourglass-half mr-2"></i>Ongoing Orders
        </h3>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700 table-fixed">
                    <thead class="bg-zinc-200 dark:bg-zinc-900">
                        <tr>
                            {{-- order receipt id --}}
                            <th class="w-30 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                </i>Receipt #
                            </th>

                            {{-- customer name --}}
                            <th class="w-35 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Customer Name
                            </th>

                            {{-- order status --}}
                            <th class="w-20 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Order Status
                            </th>

                            {{-- order payment status --}}
                            <th class="w-35 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Payment
                            </th>

                            {{-- delivered by --}}
                            <th class="w-40 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Delivered By
                            </th>

                            {{-- date and time of the order --}}
                            <th class="w-40 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Date & Time
                            </th>

                            {{-- action button --}}
                            <th class="w-50 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($ongoing as $order)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">

                                {{-- order id --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-mono font-medium text-zinc-900 dark:text-zinc-100">
                                        <i class="fas fa-receipt mr-1"></i>{{ $order->receipt_number }}
                                    </div>
                                </td>

                                {{-- customer name --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                        @if ($order->order_type === "walk_in" && !$order->customer)
                                            <i class="fas fa-walking mr-1 text-zinc-400"></i>Walk-In
                                        @elseif ($order->order_type === "walk_in" && $order->customer)
                                            <i class="fas fa-user-circle mr-1 text-zinc-400"></i>{{ $order->customer->name }}
                                        @else
                                            <i class="fas fa-user-circle mr-1 text-zinc-400"></i>{{ $order->customer->name ?? 'N/A' }}
                                        @endif
                                    </div>
                                </td>

                                {{-- order status --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($editingOrderId === $order->id)
                                        <select wire:model="editStatus" class="border rounded px-2 py-1 text-xs bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-700">
                                            <option value="pending">Pending</option>
                                            <option value="in_transit">Out for Delivery</option>
                                            <option value="delivered">Delivered</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-{{ $order->status_color }}-100 text-{{ $order->status_color }}-800 dark:bg-{{ $order->status_color }}-900/30 dark:text-{{ $order->status_color }}-300">
                                            @if ($order->status === 'preparing')
                                                <i class="fas fa-hourglass-start mr-1"></i>

                                            @elseif($order->status === 'pending')
                                                <i class="fas fa-clock mr-1"></i>

                                            @elseif ($order->status === 'in_transit')
                                                <i class="fas fa-truck-fast mr-1"></i>

                                            @elseif($order->status === 'delivered')
                                                <i class="fas fa-box-open mr-1"></i>

                                            @elseif($order->status === 'completed')
                                                <i class="fas fa-check-circle mr-1"></i>

                                            @elseif($order->status === 'cancelled')
                                                <i class="fas fa-times-circle mr-1"></i>
                                            @endif
                                                <span class="ml-1">{{ ucwords(str_replace('_', ' ', $order->status)) }}</span>
                                        </span>
                                    @endif

                                    {{-- if order is not from today --}}
                                    @if(!$order->created_at->isToday())
                                        <span class="block mt-1 text-xs px-2 py-1 font-semibold rounded-full bg-orange-200 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300">
                                            <i class="fas fa-calendar-alt mr-1"></i>
                                            @php
                                                $days = $order->created_at->diffInDays(now());
                                            @endphp

                                            @if($days <= 7)
                                                {{ max(1, (int) $days) }} {{ Str::plural('Day', max(1, (int) $days)) }} Ago
                                            @else
                                                Old Order
                                            @endif
                                        </span>
                                    @endif
                                </td>

                                {{-- order payment status --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($editingOrderId === $order->id)
                                        {{-- Edit mode: Show checkbox to toggle payment --}}
                                        <div class="flex flex-col items-center gap-2">
                                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full {{ $order->is_paid ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' }}">
                                                <i class="fas fa-{{ $order->is_paid ? 'check-circle' : 'exclamation-triangle' }}"></i>
                                                {{ $order->is_paid ? 'Paid' : 'Unpaid' }}
                                            </span>
                                            
                                            {{-- Always show editable checkbox in edit mode --}}
                                            <label class="inline-flex items-center cursor-pointer">
                                                <input type="checkbox" 
                                                       {{ $order->is_paid ? 'checked' : '' }}
                                                       class="rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500 cursor-pointer" 
                                                       wire:click="togglePaid({{ $order->id }})" />
                                                <span class="ml-1 text-xs text-zinc-600 dark:text-zinc-400">
                                                    {{ $order->is_paid ? 'Mark unpaid' : 'Mark paid' }}
                                                </span>
                                            </label>
                                        </div>
                                    @else
                                        {{-- View mode: Only show payment status --}}
                                        @if($order->is_paid)
                                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                <i class="fas fa-check-circle"></i>
                                                Paid
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                Unpaid
                                            </span>
                                        @endif
                                    @endif
                                </td>

                                {{-- delivered by --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($editingOrderId === $order->id)
                                        <select wire:model="editDeliveredBy" class="w-full border rounded px-2 py-1 text-xs bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-700">
                                            <option value="">Unassigned</option>
                                            @foreach($employees as $emp)
                                                <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                            @if ($order->order_type === "walk_in")
                                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                    <i class="fas fa-walking"></i>
                                                    Walk-In
                                                </span>
                                            @else
                                                <i class="fas fa-user-tie mr-1 text-zinc-400"></i>
                                                {{ $order->employee->name ?? 'N/A' }}
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                {{-- order date and time --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                        <small class="text-xs text-zinc-500 dark:text-zinc-400">
                                            <i class="fas fa-clock mr-1"></i>
                                            {{ $order->created_at->format('M d, Y') . ' | ' . $order->created_at->format('h:i A') }}
                                        </small>
                                    </div>
                                </td>

                                {{-- action buttons --}}
                                <td class="px-6 py-4 whitespace-nowrap text-base font-medium">
                                    <div class="flex items-center justify-center gap-1">
                                        
                                        {{-- View Order --}}
                                        <div class="w-15 flex justify-center">
                                            <button wire:click="viewOrderDetails({{ $order->id }})"
                                                class="cursor-pointer inline-flex flex-col items-center gap-1 px-3 py-2 text-sm font-medium 
                                                    text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 
                                                    hover:bg-blue-100 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                                title="View Details">
                                                <i class="fas fa-eye text-lg"></i>
                                                <span class="text-xs">View</span>
                                            </button>
                                        </div>

                                        {{-- Edit / Save --}}
                                        <div class="w-15 flex justify-center">
                                            @if($editingOrderId === $order->id)
                                                <button wire:click="saveEdit({{ $order->id }})"
                                                    class="cursor-pointer inline-flex flex-col items-center gap-0.5 px-3 py-2 text-sm font-medium 
                                                        text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 
                                                        hover:bg-green-100 dark:hover:bg-green-900/20 rounded-lg transition-colors"
                                                    title="Save Changes">
                                                    <i class="fas fa-save text-lg"></i>
                                                    <span class="text-xs">Save</span>
                                                </button>
                                            @else
                                                <button wire:click="editOrder({{ $order->id }})"
                                                    class="cursor-pointer inline-flex flex-col items-center gap-0.5 px-3 py-2 text-sm font-medium 
                                                        text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-300 
                                                        hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition-colors"
                                                    title="Edit Order">
                                                    <i class="fas fa-edit text-lg"></i>
                                                    <span class="text-xs">Edit</span>
                                                </button>
                                            @endif
                                        </div>

                                        {{-- State-driven action --}}
                                        <div class="w-14 flex justify-center">
                                            @if($order->status === 'pending')
                                                @php
                                                    $deliveryStatus = $this->getDeliveryPersonStatus($order->id);
                                                @endphp
                                                
                                                @if($deliveryStatus === 'no_person')
                                                    {{-- No delivery person assigned --}}
                                                    <div class="inline-flex flex-col items-center gap-0.5 px-3 py-2 text-sm font-medium 
                                                        text-gray-400 dark:text-gray-500 cursor-not-allowed opacity-50"
                                                        title="No delivery person assigned">
                                                        <i class="fas fa-user-slash text-lg"></i>
                                                        <span class="text-xs">No Person</span>
                                                    </div>

                                                @elseif($deliveryStatus === 'available')
                                                    {{-- Delivery person available --}}
                                                    <button wire:click="startDelivery({{ $order->id }})"
                                                        @disabled($editingOrderId)
                                                        class="cursor-pointer inline-flex flex-col items-center gap-0.5 px-3 py-2 text-sm font-medium 
                                                            text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 
                                                            hover:bg-indigo-100 dark:hover:bg-indigo-900/20 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                        title="Start Delivery">
                                                        <i class="fas fa-truck text-lg"></i>
                                                        <span class="text-xs">Deliver</span>
                                                    </button>

                                                @elseif($deliveryStatus === 'batch_preparing' || $deliveryStatus === 'busy')
                                                    {{-- Can add to batch or delivery person has active deliveries --}}
                                                    <button wire:click="startDelivery({{ $order->id }})"
                                                        @disabled($editingOrderId)
                                                        class="cursor-pointer inline-flex flex-col items-center gap-0.5 px-3 py-2 text-sm font-medium 
                                                            text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300 
                                                            hover:bg-yellow-100 dark:hover:bg-yellow-900/20 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                        title="Add to delivery batch">
                                                        <i class="fas fa-plus-circle text-lg"></i>
                                                        <span class="text-xs">Add Deliver</span>
                                                    </button>

                                                @elseif($deliveryStatus === 'preparing')
                                                    {{-- Order is already in the batch --}}
                                                    <div class="inline-flex flex-col items-center gap-0.5 px-3 py-2 text-sm font-medium 
                                                        text-yellow-600 dark:text-yellow-400"
                                                        title="Order is already in batch preparation">
                                                        <i class="fas fa-hourglass-half text-lg"></i>
                                                        <span class="text-xs">In Batch</span>
                                                    </div>

                                                @elseif($deliveryStatus === 'waiting')
                                                    {{-- Order missed the batch window and is waiting --}}
                                                    <div class="inline-flex flex-col items-center gap-0.5 px-3 py-2 text-sm font-medium 
                                                        text-purple-600 dark:text-purple-400 cursor-not-allowed opacity-75"
                                                        title="Missed batch window, waiting for next opportunity">
                                                        <i class="fas fa-clock-rotate-left text-lg"></i>
                                                        <span class="text-xs">In Queue</span>
                                                    </div>

                                                @else
                                                    {{-- Fallback for any other status --}}
                                                    <div class="inline-flex flex-col items-center gap-0.5 px-3 py-2 text-sm font-medium 
                                                        text-orange-600 dark:text-orange-400 cursor-not-allowed opacity-75"
                                                        title="Delivery person is currently busy">
                                                        <i class="fas fa-clock text-lg"></i>
                                                        <span class="text-xs">Busy</span>
                                                    </div>
                                                @endif
                                            
                                            @elseif($order->status === 'preparing')
                                                @php
                                                    $employeeId = $order->delivered_by;
                                                    $batchInfo = $this->getBatchInfo($employeeId);
                                                    $remainingTime = $batchInfo['remaining_time'] ?? 0; // seconds
                                                @endphp
                                                <div 
                                                    x-data="{
                                                        r: {{ $remainingTime }},
                                                        started: false,
                                                        tick() {
                                                            if (this.started) return;
                                                            this.started = true;
                                                            let iv = setInterval(() => {
                                                                if (this.r > 0) {
                                                                    this.r--;
                                                                } else {
                                                                    clearInterval(iv);
                                                                    // Call server once to promote (guard if already processed)
                                                                    $wire.processBatchDelivery({{ $employeeId }});
                                                                }
                                                            }, 1000);
                                                        }
                                                    }"
                                                    x-init="tick()"
                                                    class="inline-flex flex-col items-center gap-0.5 px-3 py-2 text-sm font-medium text-yellow-600 dark:text-yellow-400"
                                                    title="Order is in batch preparation">
                                                    <i class="fas fa-hourglass-half text-lg"></i>
                                                    <span class="text-xs">Preparing</span>
                                                    <template x-if="r > 0">
                                                        <span class="text-[10px] font-mono bg-yellow-100 dark:bg-yellow-900/30 px-1 rounded"
                                                              x-text="Math.floor(r/60)+':' + String(r%60).padStart(2,'0')"></span>
                                                    </template>
                                                    <template x-if="r === 0">
                                                        <span class="text-[10px] font-mono bg-green-100 dark:bg-green-900/30 px-1 rounded">0:00</span>
                                                    </template>
                                                </div>

                                            @elseif($order->status === 'in_transit')
                                                <button wire:click="markDelivered({{ $order->id }})"
                                                    @disabled($editingOrderId) {{-- 👈 disables if editing --}}
                                                    class="cursor-pointer inline-flex flex-col items-center gap-0.5 px-3 py-2 text-sm font-medium 
                                                        text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300 
                                                        hover:bg-purple-100 dark:hover:bg-purple-900/20 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                    title="Mark as Delivered">
                                                    <i class="fas fa-box-open text-lg"></i>
                                                    <span class="text-xs">Delivered</span>
                                                </button>
                                                
                                            @elseif($order->status === 'delivered' && !$order->is_paid)
                                                <button wire:click="togglePaid({{ $order->id }})"
                                                    @disabled($editingOrderId)
                                                    class="cursor-pointer inline-flex flex-col items-center gap-0.5 px-3 py-2 text-sm font-medium 
                                                        text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 
                                                        hover:bg-blue-100 dark:hover:bg-blue-900/20 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                    title="Mark as Paid">
                                                    <i class="fas fa-money-bill-transfer text-lg"></i>
                                                    <span class="text-xs">Paid</span>
                                                </button>

                                            @elseif($order->status === 'delivered' && $order->is_paid)
                                                <button wire:click="markFinished({{ $order->id }})"
                                                    @disabled($editingOrderId)
                                                    class="cursor-pointer inline-flex flex-col items-center gap-0.5 px-3 py-2 text-sm font-medium 
                                                        text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 
                                                        hover:bg-green-100 dark:hover:bg-green-900/20 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                    title="Mark as Finished">
                                                    <i class="fas fa-check-double text-lg"></i>
                                                    <span class="text-xs">Complete</span>
                                                </button>

                                            @else
                                                {{-- Invisible placeholder keeps layout fixed --}}
                                                <div class="w-20 h-[50px]"></div>
                                            @endif

                                        </div>

                                        {{-- Delete / Cancel --}}
                                        <div class="w-15 flex justify-center">
                                            @if($order->status === 'preparing')
                                                <button wire:click="cancelPrepare({{ $order->id }})" class="cursor-pointer inline-flex flex-col items-center gap-1 px-3 py-2 text-sm font-medium text-orange-600 hover:text-orange-900 dark:text-orange-400 dark:hover:text-orange-300 hover:bg-orange-200 dark:hover:bg-orange-700 rounded-lg transition-colors" title="Cancel Preparation">
                                                    <i class="fas fa-ban text-lg"></i>
                                                    <span class="text-xs">Cancel</span>
                                                </button>
                                            @else
                                                <button wire:click="confirmDelete({{ $order->id }})" class="cursor-pointer inline-flex flex-col items-center gap-1 px-3 py-2 text-sm font-medium text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-200 dark:hover:bg-red-700 rounded-lg transition-colors" title="Delete Order">
                                                    <i class="fas fa-trash text-lg"></i>
                                                    <span class="text-xs">Delete</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-inbox text-4xl mb-3"></i>
                                        <p class="text-sm">No ongoing orders today.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Completed Orders Table --}}
    <div>
        <h3 class="text-xl font-semibold mb-3 text-zinc-900 dark:text-zinc-100">
            <i class="fas fa-clipboard-check mr-2"></i>Completed/Cancelled Orders
        </h3>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700 table-fixed">
                    <thead class="bg-zinc-200 dark:bg-zinc-900">
                        <tr>
                            {{-- order receipt id --}}
                            <th class="w-30 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Receipt #
                            </th>

                            {{-- customer name --}}
                            <th class="w-35 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Customer Name
                            </th>

                            {{-- status --}}
                            <th class="w-20 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Order Status
                            </th>

                            {{-- order payment status --}}
                            <th class="w-35 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Payment
                            </th>

                            {{-- delivered by --}}
                            <th class="w-40 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Delivered By
                            </th>

                            {{-- date and time of the order --}}
                            <th class="w-40 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Date & Time
                            </th>

                            {{-- action button --}}
                            <th class="w-60 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($completed as $order)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">

                                {{-- order reciept number --}}
                                <td class="px-6 py-4 whitespace-nowrap text-left">
                                    <div class="text-sm font-mono font-medium text-zinc-900 dark:text-zinc-100">
                                        <i class="fas fa-receipt mr-1"></i>{{ $order->receipt_number }}
                                    </div>
                                </td>

                                {{-- customer name --}}
                                <td class="px-6 py-4 whitespace-nowrap text-left">
                                    <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                        @if ($order->order_type === "walk_in")
                                            <i class="fas fa-walking mr-1 text-zinc-400"></i>Walk-In
                                        @else
                                            <i class="fas fa-user-circle mr-1 text-zinc-400"></i>{{ $order->customer->name ?? 'N/A' }}
                                        @endif
                                    </div>
                                </td>

                                {{-- order status --}}
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($editingOrderId === $order->id)
                                        <select wire:model="editStatus" class="border rounded px-2 py-1 text-xs bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-700">
                                            <option value="pending">Pending</option>
                                            <option value="in_transit">Out for Delivery</option>
                                            <option value="delivered">Delivered</option>
                                            <option value="completed">Completed</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-{{ $order->status_color }}-100 text-{{ $order->status_color }}-800 dark:bg-{{ $order->status_color }}-900/30 dark:text-{{ $order->status_color }}-300">
                                            @if($order->status === 'pending')
                                                <i class="fas fa-clock mr-1"></i>
                                            @elseif($order->status === 'in_transit')
                                                <i class="fas fa-truck mr-1"></i>
                                            @elseif($order->status === 'delivered')
                                                <i class="fas fa-box-check mr-1"></i>
                                            @elseif($order->status === 'completed')
                                                <i class="fas fa-check-circle mr-1"></i>
                                            @elseif($order->status === 'cancelled')
                                                <i class="fas fa-times-circle mr-1"></i>
                                            @endif
                                            {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                        </span>
                                    @endif

                                    {{-- if order is not from today --}}
                                    @if(!$order->created_at->isToday())
                                        <span class="block mt-1 text-xs px-2 py-1 font-semibold rounded-full bg-orange-200 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300">
                                            <i class="fas fa-calendar-alt mr-1"></i>
                                            @php
                                                $days = $order->created_at->diffInDays(now());
                                            @endphp

                                            @if($days <= 7)
                                                {{ max(1, (int) $days) }} {{ Str::plural('Day', max(1, (int) $days)) }} Ago
                                            @else
                                                Old Order
                                            @endif
                                        </span>
                                    @endif
                                </td>

                                {{-- payment status --}}
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($editingOrderId === $order->id)
                                        {{-- Edit mode: Show checkbox to toggle payment --}}
                                        <div class="flex flex-col items-center gap-2">
                                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full {{ $order->is_paid ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' }}">
                                                <i class="fas fa-{{ $order->is_paid ? 'check-circle' : 'exclamation-triangle' }}"></i>
                                                {{ $order->is_paid ? 'Paid' : 'Unpaid' }}
                                            </span>
                                            
                                            {{-- Always show editable checkbox in edit mode --}}
                                            <label class="inline-flex items-center cursor-pointer">
                                                <input type="checkbox" 
                                                       {{ $order->is_paid ? 'checked' : '' }}
                                                       class="rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500 cursor-pointer" 
                                                       wire:click="togglePaid({{ $order->id }})" />
                                                <span class="ml-1 text-xs text-zinc-600 dark:text-zinc-400">
                                                    {{ $order->is_paid ? 'Mark unpaid' : 'Mark paid' }}
                                                </span>
                                            </label>
                                        </div>
                                    @else
                                        {{-- View mode: Only show payment status --}}
                                        @if($order->is_paid)
                                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                <i class="fas fa-check-circle"></i>
                                                Paid
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                Unpaid
                                            </span>
                                        @endif
                                    @endif
                                </td>

                                {{-- delivered by --}}
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($editingOrderId === $order->id)
                                        <select wire:model="editDeliveredBy" class="w-full border rounded px-2 py-1 text-xs bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-700">
                                            <option value="">Unassigned</option>
                                            @foreach($employees as $emp)
                                                <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                            <i class="fas fa-user-tie mr-1 text-zinc-400"></i>
                                            {{ $order->employee->name ?? 'N/A' }}
                                        </div>
                                    @endif
                                </td>

                                {{-- date and time of delivery --}}
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                        <div class="flex flex-col gap-1">
                                            <small class="text-xs text-zinc-500 dark:text-zinc-400">
                                                <i class="fas fa-clock mr-1 text-zinc-400"></i>
                                                {{ $order->created_at->format('M d, Y') . ' | ' . $order->created_at->format('h:i A') }}
                                            </small>
                                            <small class="text-xs text-zinc-500 dark:text-zinc-400">
                                                <i class="fas fa-calendar-check mr-1 text-zinc-400"></i>
                                                {{ $order->updated_at->format('M d, Y') . ' | ' . $order->updated_at->format('h:i A') }}
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                {{-- action buttons --}}
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        
                                        {{-- view button --}}
                                        <button wire:click="viewOrderDetails({{ $order->id }})" class="cursor-pointer inline-flex flex-col items-center gap-1 px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/20 rounded-lg transition-colors" title="View Details">
                                            <i class="fas fa-eye text-lg"></i>
                                            <span class="text-xs">View</span>
                                        </button>
                                        
                                        {{-- edit / save button --}}
                                        @if($editingOrderId === $order->id)
                                            <button wire:click="saveEdit({{ $order->id }})" class="cursor-pointer inline-flex flex-col items-center gap-1 px-3 py-2 text-sm font-medium text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/20 rounded-lg transition-colors" title="Save Changes">
                                                <i class="fas fa-save text-lg"></i>
                                                <span class="text-xs">Save</span>
                                            </button>
                                        @else
                                            <button wire:click="editOrder({{ $order->id }})" class="cursor-pointer inline-flex flex-col items-center gap-1 px-3 py-2 text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition-colors" title="Edit Order">
                                                <i class="fas fa-edit text-lg"></i>
                                                <span class="text-xs">Edit</span>
                                            </button>
                                        @endif

                                        {{-- delete --}}
                                        <div class="w-15 flex justify-center">
                                            <button wire:click="confirmDelete({{ $order->id }})" class="cursor-pointer inline-flex flex-col items-center gap-1 px-3 py-2 text-sm font-medium text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-200 dark:hover:bg-red-700 rounded-lg transition-colors" title="Delete Order">
                                                <i class="fas fa-trash text-lg"></i>
                                                <span class="text-xs">Delete</span>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-archive text-4xl mb-3"></i>
                                        <p class="text-sm">No completed/cancelled orders today.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Order Details Modal --}}
    @if($showOrderDetailsModal && $selectedOrder)
        <div class="fixed inset-0 bg-zinc-500/80 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                        <i class="fas fa-file-invoice mr-2"></i>Order Details
                    </h3>
                    <button wire:click="closeOrderDetailsModal" class="cursor-pointer text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                {{-- body div --}}
                <div class="p-6 space-y-6">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">

                        {{-- Order Information --}}
                        <div class="space-y-5">
                            <h4 class="font-semibold text-zinc-900 dark:text-zinc-100">
                                <i class="fas fa-shopping-bag mr-2"></i>Order Information
                            </h4>
                            <dl class="space-y-2">
                                {{-- order id --}}
                                <div class="flex justify-between">
                                    <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                        <i class="fas fa-hashtag mr-1"></i>Order ID:
                                    </dt>
                                    <dd class="text-sm font-medium text-zinc-900 dark:text-zinc-100">#{{ $selectedOrder->id }}</dd>
                                </div>

                                {{-- receipt number --}}
                                <div class="flex justify-between">
                                    <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                        <i class="fas fa-receipt mr-1"></i>Receipt Number:
                                    </dt>
                                    <dd class="text-sm font-mono font-medium text-zinc-900 dark:text-zinc-100">{{ $selectedOrder->receipt_number }}</dd>
                                </div>

                                {{-- employee delivery personnel --}}
                                <div class="flex justify-between">
                                    <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                        <i class="fas fa-user-tie mr-1"></i>Delivered By:
                                    </dt>
                                    <dd class="text-sm text-zinc-900 dark:text-zinc-100">{{ $selectedOrder->employee->name ?? 'N/A' }}</dd>
                                </div>

                                {{-- order status --}}
                                <div class="flex justify-between">
                                    <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                        <i class="fas fa-info-circle mr-1"></i>Order Status:
                                    </dt>
                                    <dd>
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-{{ $selectedOrder->status_color }}-100 text-{{ $selectedOrder->status_color }}-800 dark:bg-{{ $selectedOrder->status_color }}-900/30 dark:text-{{ $selectedOrder->status_color }}-300">
                                            @if($selectedOrder->status === 'pending')
                                                <i class="fas fa-clock mr-1"></i>
                                            @elseif($selectedOrder->status === 'in_transit')
                                                <i class="fas fa-truck-fast mr-1"></i>
                                            @elseif($selectedOrder->status === 'delivered')
                                                <i class="fas fa-truck mr-1"></i>
                                            @elseif($selectedOrder->status === 'completed')
                                                <i class="fas fa-check-circle mr-1"></i>
                                            @elseif($selectedOrder->status === 'cancelled')
                                                <i class="fas fa-times-circle mr-1"></i>
                                            @endif
                                            {{ ucwords(str_replace('_', ' ', $selectedOrder->status)) }}
                                        </span>
                                    </dd>
                                </div>

                                {{-- payment status --}}
                                <div class="flex justify-between">
                                    <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                        <i class="fas fa-credit-card mr-1"></i>Payment Status:
                                    </dt>
                                    <dd>
                                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full {{ $selectedOrder->is_paid ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' }}">
                                            @if($selectedOrder->is_paid)
                                                <i class="fas fa-check-circle"></i>
                                                Paid
                                            @else
                                                <i class="fas fa-exclamation-triangle"></i>
                                                Unpaid
                                            @endif
                                        </span>
                                    </dd>
                                </div>

                                {{-- payment type --}}
                                <div class="flex justify-between">
                                    <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                        <i class="fas fa-money-bill mr-1"></i>Payment Type:
                                    </dt>
                                    <dd class="text-sm text-zinc-900 dark:text-zinc-100 uppercase">{{ $selectedOrder->payment_type }}</dd>
                                </div>
                            </dl>
                        </div>

                        {{-- Customer Information --}}
                        <div class="space-y-5">
                            <h4 class="font-semibold text-zinc-900 dark:text-zinc-100">
                                <i class="fas fa-user mr-2"></i>Customer Information
                            </h4>
                            @if($selectedOrder->customer)
                                <dl class="space-y-2">
                                    {{-- name --}}
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                            <i class="fas fa-id-badge mr-1"></i>Name:
                                        </dt>
                                        <dd class="text-sm text-zinc-900 dark:text-zinc-100">{{ $selectedOrder->customer->name }}</dd>
                                    </div>

                                    {{-- unit and address --}}
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                            <i class="fas fa-map-marker-alt mr-1"></i>Unit & Address:
                                        </dt>
                                        @if ($selectedOrder->customer->unit || $selectedOrder->customer->address)
                                            <dd class="ml-5 text-sm text-zinc-900 dark:text-zinc-100">{{ $selectedOrder->customer->unit }}, {{ $selectedOrder->customer->address }}</dd>

                                        @elseif (!$selectedOrder->customer->unit && $selectedOrder->customer->address)
                                            <dd class="ml-5 text-sm text-zinc-900 dark:text-zinc-100">{{ $selectedOrder->customer->address }}</dd>

                                        @else
                                            <dd class="ml-5 text-sm text-zinc-900 dark:text-zinc-100">Not Provided</dd>

                                        @endif

                                    </div>

                                    {{-- contact number --}}
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                            <i class="fas fa-phone mr-1"></i>Contact:
                                        </dt>
                                        <dd class="text-sm text-zinc-900 dark:text-zinc-100">{{ $selectedOrder->customer->contact_number ?? 'N/A' }}</dd>
                                    </div>
                                </dl>
                            @else
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    <i class="fas fa-user-slash mr-1"></i>No customer information available
                                </p>
                            @endif
                        </div>

                    </div>
                    
                    {{-- Products List --}}
                    <div>
                        <h4 class="font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                            <i class="fas fa-shopping-basket mr-2"></i>Ordered Items
                        </h4>
                        @if($selectedOrder->orderItems && count($selectedOrder->orderItems) > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                                    <thead class="bg-zinc-200 dark:bg-zinc-900">
                                        <tr>
                                            {{-- product id --}}
                                            <th class="px-4 py-2 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                                ID #
                                            </th>

                                            {{-- product name --}}
                                            <th class="px-4 py-2 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                                Product
                                            </th>

                                            {{-- purchased quantity --}}
                                            <th class="px-4 py-2 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                                Quantity
                                            </th>

                                            {{-- product price --}}
                                            <th class="px-4 py-2 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                                Unit Price
                                            </th>

                                            {{-- product total amount price --}}
                                            <th class="px-4 py-2 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                                Total
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                        @foreach($selectedOrder->orderItems as $item)
                                            <tr>
                                                {{-- product id --}}
                                                <td class="px-4 py-2 text-center text-sm text-zinc-900 dark:text-zinc-100">
                                                    <i class="fas fa-hashtag mr-1 text-zinc-400"></i>{{ $item->product->id ?? 'N/A' }}
                                                </td>

                                                {{-- product name --}}
                                                <td class="px-4 py-2 text-center text-sm text-zinc-900 dark:text-zinc-100">
                                                    {{ $item->product->name ?? '#' }}
                                                </td>

                                                {{-- purchased quantity --}}
                                                <td class="px-4 py-2 text-center text-sm text-zinc-900 dark:text-zinc-100">
                                                    {{ $item->quantity }}
                                                </td>

                                                {{-- product price --}}
                                                <td class="px-4 py-2 text-center text-sm text-zinc-900 dark:text-zinc-100">
                                                    ₱{{ number_format($item->unit_price, 2) }}
                                                </td>

                                                {{-- product total amount price --}}
                                                <td class="px-4 py-2 text-center text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                    ₱{{ number_format($item->total_price, 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            {{-- Order Total Amount --}}
                            <div class="mt-4 border-t border-zinc-200 dark:border-zinc-700 pt-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                                        <i class="fas fa-receipt mr-2"></i>Total Amount:
                                    </span>
                                    <span class="text-xl font-bold text-zinc-900 dark:text-zinc-100">₱{{ number_format($selectedOrder->order_total, 2) }}</span>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                <i class="fas fa-exclamation-triangle mr-1"></i>No items found for this order
                            </p>
                        @endif
                    </div>

                </div> {{-- body div end --}}
                
                <div class="flex justify-end gap-3 px-6 py-4 bg-zinc-50 dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-700">
                    <button wire:click="closeOrderDetailsModal" 
                        class="cursor-pointer px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                        <i class="fas fa-times mr-1"></i>Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50">
            <div class="absolute inset-0 bg-black/50"></div>
            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div class="w-full max-w-md bg-white dark:bg-zinc-800 rounded-lg shadow-xl">
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                            <i class="fas fa-triangle-exclamation text-red-500 mr-2"></i>Confirm Deletion
                        </h3>
                    </div>
                    <div class="px-6 py-4 space-y-2">
                        <p class="text-sm text-zinc-700 dark:text-zinc-300">
                            Are you sure you want to delete this order? This action can't be undone.
                        </p>
                        @if($deleteReceipt)
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Receipt #: <span class="font-mono">{{ $deleteReceipt }}</span>
                            </p>
                        @endif
                    </div>
                    <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-700 flex justify-end gap-3">
                        <button wire:click="closeDeleteModal"
                            class="px-3 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                            Cancel
                        </button>
                        <button wire:click="deleteOrderConfirmed"
                            class="px-3 py-2 text-sm rounded-lg bg-red-600 text-white hover:bg-red-700">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
