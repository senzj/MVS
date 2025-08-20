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
                                <i class="fas fa-receipt mr-1"></i>Receipt #
                            </th>

                            {{-- customer name --}}
                            <th class="w-35 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-user mr-1"></i>Customer
                            </th>

                            {{-- status --}}
                            <th class="w-20 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-info-circle mr-1"></i>Status
                            </th>

                            {{-- payment type --}}
                            <th class="w-35 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-credit-card mr-1"></i>Payment
                            </th>

                            {{-- delivered by --}}
                            <th class="w-40 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-user-tie mr-1"></i>Delivered By
                            </th>

                            {{-- date and time of the order --}}
                            <th class="w-40 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-clock mr-1"></i>Date & Time
                            </th>

                            {{-- action button --}}
                            <th class="w-50 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-cogs mr-1"></i>Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($ongoing as $order)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">

                                {{-- order id --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-mono font-medium text-zinc-900 dark:text-zinc-100">
                                        <i class="fas fa-hashtag mr-1 text-zinc-400"></i>{{ $order->receipt_number }}
                                    </div>
                                </td>

                                {{-- customer name --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                        <i class="fas fa-user-circle mr-1 text-zinc-400"></i>{{ $order->customer->name ?? 'N/A' }}
                                    </div>
                                </td>

                                {{-- order status --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($editingOrderId === $order->id)
                                        <select wire:model="editStatus" class="border rounded px-2 py-1 text-xs bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-700">
                                            <option value="pending">Pending</option>
                                            <option value="delivered">Delivered</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-{{ $order->status_color }}-100 text-{{ $order->status_color }}-800 dark:bg-{{ $order->status_color }}-900/30 dark:text-{{ $order->status_color }}-300">
                                            @if($order->status === 'pending')
                                                <i class="fas fa-clock mr-1"></i>
                                            @elseif($order->status === 'delivered')
                                                <i class="fas fa-truck mr-1"></i>
                                            @elseif($order->status === 'completed')
                                                <i class="fas fa-check-circle mr-1"></i>
                                            @elseif($order->status === 'cancelled')
                                                <i class="fas fa-times-circle mr-1"></i>
                                            @endif
                                            {{ ucfirst($order->status) }}
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
                                    <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                        <i class="fas fa-user-tie mr-1 text-zinc-400"></i>
                                        {{ $order->employee->name ?? 'N/A' }}
                                    </div>
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
                                        <button wire:click="viewOrderDetails({{ $order->id }})" class="cursor-pointer inline-flex flex-col items-center gap-1 px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/20 rounded-lg transition-colors" title="View Details">
                                            <i class="fas fa-eye text-lg"></i>
                                            <span class="text-xs">View</span>
                                        </button>
                                        
                                        @if($editingOrderId === $order->id)
                                            <button wire:click="saveEdit({{ $order->id }})" class="cursor-pointer inline-flex flex-col items-center gap-0.5 px-3 py-2 text-sm font-medium text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/20 rounded-lg transition-colors" title="Save Changes">
                                                <i class="fas fa-save text-lg"></i>
                                                <span class="text-xs">Save</span>
                                            </button>
                                        @else
                                            <button wire:click="editOrder({{ $order->id }})" class="cursor-pointer inline-flex flex-col items-center gap-0.5 px-3 py-2 text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition-colors" title="Edit Order">
                                                <i class="fas fa-edit text-lg"></i>
                                                <span class="text-xs">Edit</span>
                                            </button>
                                        @endif
                                        
                                        <button
                                            wire:click="markFinished({{ $order->id }})"
                                            class="inline-flex flex-col items-center gap-0.5 px-3 py-2 text-sm font-medium text-green-600 hover:text-green-900 disabled:text-zinc-400 disabled:cursor-not-allowed dark:text-green-400 dark:hover:text-green-300 dark:disabled:text-zinc-600 hover:bg-green-100 dark:hover:bg-green-900/20 disabled:hover:bg-transparent rounded-lg transition-colors
                                            @if (!$order->is_paid)
                                                opacity-50 cursor-not-allowed
                                            @else
                                                opacity-100 cursor-pointer
                                            @endif"
                                            title="{{ $order->is_paid ? 'Mark as finished' : 'Cannot finish unpaid orders' }}"

                                            @if(!$order->is_paid) 
                                                disabled 
                                            @endif
                                        >
                                            <i class="fas fa-check-double text-lg"></i>
                                            <span class="text-xs">Complete</span>
                                        </button>
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
                                <i class="fas fa-receipt mr-1"></i>Receipt #
                            </th>

                            {{-- customer name --}}
                            <th class="w-35 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-user mr-1"></i>Customer
                            </th>

                            {{-- status --}}
                            <th class="w-20 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-info-circle mr-1"></i>Status
                            </th>

                            {{-- payment type --}}
                            <th class="w-35 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-credit-card mr-1"></i>Payment
                            </th>

                            {{-- delivered by --}}
                            <th class="w-40 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-user-tie mr-1"></i>Delivered By
                            </th>

                            {{-- date and time of the order --}}
                            <th class="w-40 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-clock mr-1"></i>Date & Time
                            </th>

                            {{-- action button --}}
                            <th class="w-60 px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-cogs mr-1"></i>Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($completed as $order)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">

                                {{-- order reciept number --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-mono font-medium text-zinc-900 dark:text-zinc-100">
                                        <i class="fas fa-hashtag mr-1 text-zinc-400"></i>{{ $order->receipt_number }}
                                    </div>
                                </td>

                                {{-- customer name --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                        <i class="fas fa-user-circle mr-1 text-zinc-400"></i>{{ $order->customer->name ?? 'N/A' }}
                                    </div>
                                </td>

                                {{-- order status --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($editingOrderId === $order->id)
                                        <select wire:model="editStatus" class="border rounded px-2 py-1 text-xs bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-700">
                                            <option value="pending">Pending</option>
                                            <option value="delivered">Delivered</option>
                                            <option value="completed">Completed</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-{{ $order->status_color }}-100 text-{{ $order->status_color }}-800 dark:bg-{{ $order->status_color }}-900/30 dark:text-{{ $order->status_color }}-300">
                                            @if($order->status === 'pending')
                                                <i class="fas fa-clock mr-1"></i>
                                            @elseif($order->status === 'delivered')
                                                <i class="fas fa-truck mr-1"></i>
                                            @elseif($order->status === 'completed')
                                                <i class="fas fa-check-circle mr-1"></i>
                                            @elseif($order->status === 'cancelled')
                                                <i class="fas fa-times-circle mr-1"></i>
                                            @endif
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    @endif
                                </td>

                                {{-- payment status --}}
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
                                    <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                        <i class="fas fa-user-tie mr-1 text-zinc-400"></i>
                                        {{ $order->employee->name ?? 'N/A' }}
                                    </div>
                                </td>

                                {{-- date and time of delivery --}}
                                <td class="px-6 py-4 whitespace-nowrap">
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-cent">
                                    <div class="flex items-center justify-center gap-3">
                                        <button wire:click="viewOrderDetails({{ $order->id }})" class="cursor-pointer inline-flex flex-col items-center gap-1 px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/20 rounded-lg transition-colors" title="View Details">
                                            <i class="fas fa-eye text-lg"></i>
                                            <span class="text-xs">View</span>
                                        </button>
                                        
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
                    <button wire:click="closeOrderDetailsModal" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div class="p-6 space-y-6">
                    {{-- Order Information --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <h4 class="font-semibold text-zinc-900 dark:text-zinc-100">
                                <i class="fas fa-shopping-bag mr-2"></i>Order Information
                            </h4>
                            <dl class="space-y-2">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                        <i class="fas fa-hashtag mr-1"></i>Order ID:
                                    </dt>
                                    <dd class="text-sm font-medium text-zinc-900 dark:text-zinc-100">#{{ $selectedOrder->id }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                        <i class="fas fa-receipt mr-1"></i>Receipt Number:
                                    </dt>
                                    <dd class="text-sm font-mono font-medium text-zinc-900 dark:text-zinc-100">{{ $selectedOrder->receipt_number }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                        <i class="fas fa-user-tie mr-1"></i>Employee:
                                    </dt>
                                    <dd class="text-sm text-zinc-900 dark:text-zinc-100">{{ $selectedOrder->employee->name ?? 'N/A' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                        <i class="fas fa-info-circle mr-1"></i>Order Status:
                                    </dt>
                                    <dd>
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-{{ $selectedOrder->status_color }}-100 text-{{ $selectedOrder->status_color }}-800 dark:bg-{{ $selectedOrder->status_color }}-900/30 dark:text-{{ $selectedOrder->status_color }}-300">
                                            @if($selectedOrder->status === 'pending')
                                                <i class="fas fa-clock mr-1"></i>
                                            @elseif($selectedOrder->status === 'delivered')
                                                <i class="fas fa-truck mr-1"></i>
                                            @elseif($selectedOrder->status === 'completed')
                                                <i class="fas fa-check-circle mr-1"></i>
                                            @elseif($selectedOrder->status === 'cancelled')
                                                <i class="fas fa-times-circle mr-1"></i>
                                            @endif
                                            {{ ucfirst($selectedOrder->status) }}
                                        </span>
                                    </dd>
                                </div>
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
                                <div class="flex justify-between">
                                    <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                        <i class="fas fa-money-bill mr-1"></i>Payment Type:
                                    </dt>
                                    <dd class="text-sm text-zinc-900 dark:text-zinc-100 uppercase">{{ $selectedOrder->payment_type }}</dd>
                                </div>
                            </dl>
                        </div>
                        
                        {{-- Customer Information --}}
                        <div class="space-y-4">
                            <h4 class="font-semibold text-zinc-900 dark:text-zinc-100">
                                <i class="fas fa-user mr-2"></i>Customer Information
                            </h4>
                            @if($selectedOrder->customer)
                                <dl class="space-y-2">
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                            <i class="fas fa-id-badge mr-1"></i>Name:
                                        </dt>
                                        <dd class="text-sm text-zinc-900 dark:text-zinc-100">{{ $selectedOrder->customer->name }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                            <i class="fas fa-map-marker-alt mr-1"></i>Unit & Address:
                                        </dt>
                                        @if ($selectedOrder->customer->unit || $selectedOrder->customer->address)
                                            <dd class="text-sm text-zinc-900 dark:text-zinc-100">{{ $selectedOrder->customer->unit }}, {{ $selectedOrder->customer->address }}</dd>

                                        @elseif (!$selectedOrder->customer->unit && $selectedOrder->customer->address)
                                            <dd class="text-sm text-zinc-900 dark:text-zinc-100">{{ $selectedOrder->customer->address }}</dd>

                                        @else
                                            <dd class="text-sm text-zinc-900 dark:text-zinc-100">Not Provided</dd>

                                        @endif

                                    </div>
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
                            <i class="fas fa-shopping-basket mr-2"></i>Order Items
                        </h4>
                        @if($selectedOrder->orderItems && count($selectedOrder->orderItems) > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                                    <thead class="bg-zinc-200 dark:bg-zinc-900">
                                        <tr>
                                            {{-- product id --}}
                                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                                <i class="fas fa-tag mr-1"></i>ID
                                            </th>

                                            {{-- product name --}}
                                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                                <i class="fas fa-box mr-1"></i>Product
                                            </th>

                                            {{-- purchased quantity --}}
                                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                                <i class="fas fa-sort-numeric-up mr-1"></i>Quantity
                                            </th>

                                            {{-- product price --}}
                                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                                <i class="fas fa-peso-sign mr-1"></i>Price
                                            </th>

                                            {{-- product total amount price --}}
                                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                                <i class="fas fa-calculator mr-1"></i>Total
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                        @foreach($selectedOrder->orderItems as $item)
                                            <tr>
                                                {{-- product id --}}
                                                <td class="px-4 py-2 text-sm text-zinc-900 dark:text-zinc-100">
                                                    <i class="fas fa-hashtag mr-1 text-zinc-400"></i>{{ $item->product->id ?? 'N/A' }}
                                                </td>

                                                {{-- product name --}}
                                                <td class="px-4 py-2 text-sm text-zinc-900 dark:text-zinc-100">
                                                    <i class="fas fa-tag mr-1 text-zinc-400"></i>{{ $item->product->name ?? 'N/A' }}
                                                </td>

                                                {{-- purchased quantity --}}
                                                <td class="px-4 py-2 text-sm text-zinc-900 dark:text-zinc-100">
                                                    <i class="fas fa-cubes mr-1 text-zinc-400"></i>{{ $item->quantity }}
                                                </td>

                                                {{-- product price --}}
                                                <td class="px-4 py-2 text-sm text-zinc-900 dark:text-zinc-100">₱{{ number_format($item->unit_price, 2) }}</td>

                                                {{-- product total amount price --}}
                                                <td class="px-4 py-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">₱{{ number_format($item->total_price, 2) }}</td>
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
                </div>
                
                <div class="flex justify-end gap-3 px-6 py-4 bg-zinc-50 dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-700">
                    <button wire:click="closeOrderDetailsModal" class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                        <i class="fas fa-times mr-1"></i>Close
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
