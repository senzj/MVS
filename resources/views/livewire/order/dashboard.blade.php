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
            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                <i class="fas fa-clock"></i>
                Ongoing: {{ $ongoingCount }}
            </span>
            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                <i class="fas fa-check-circle"></i>
                Completed: {{ $completedCount }}
            </span>

            {{-- create order --}}
            <button type="button" class="ml-2 inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 active:bg-blue-800 transition dark:bg-blue-500 dark:hover:bg-blue-600">
                <a href="{{ route('orders.create') }}" class="flex items-center gap-1" wire:navigate>
                    <i class="fas fa-plus"></i>
                    <span>Create Order</span>
                </a>
            </button>
        </div>
    </div>

    {{-- Ongoing Orders Table --}}
    <div class="mb-8">
        <h3 class="text-xl font-semibold mb-3 text-zinc-900 dark:text-zinc-100">
            <i class="fas fa-hourglass-half mr-2"></i>Ongoing Orders
        </h3>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-receipt mr-1"></i>Receipt #
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-user mr-1"></i>Customer
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-info-circle mr-1"></i>Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-credit-card mr-1"></i>Payment
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-cogs mr-1"></i>Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($ongoing as $order)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-mono font-medium text-zinc-900 dark:text-zinc-100">
                                        <i class="fas fa-hashtag mr-1 text-zinc-400"></i>{{ $order->receipt_number }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                        <i class="fas fa-user-circle mr-1 text-zinc-400"></i>{{ $order->customer->name ?? 'N/A' }}
                                    </div>
                                </td>
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
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($order->is_paid)
                                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                            <i class="fas fa-check-circle"></i>
                                            Paid
                                        </span>
                                    @else
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                Unpaid
                                            </span>
                                            <label class="inline-flex items-center">
                                                <input type="checkbox" class="rounded border-zinc-300 dark:border-zinc-600 text-blue-600" wire:click="togglePaid({{ $order->id }})" />
                                                <span class="ml-1 text-xs text-zinc-600 dark:text-zinc-400">Mark paid</span>
                                            </label>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <button wire:click="viewOrderDetails({{ $order->id }})" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        @if($editingOrderId === $order->id)
                                            <button wire:click="saveEdit({{ $order->id }})" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300" title="Save Changes">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        @else
                                            <button wire:click="editOrder({{ $order->id }})" class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-300" title="Edit Order">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endif
                                        
                                        <button
                                            wire:click="markFinished({{ $order->id }})"
                                            class="text-green-600 hover:text-green-900 disabled:text-zinc-400 disabled:cursor-not-allowed dark:text-green-400 dark:hover:text-green-300 dark:disabled:text-zinc-600"
                                            @if(!$order->is_paid) disabled @endif
                                            title="{{ $order->is_paid ? 'Mark as finished' : 'Cannot finish unpaid orders' }}"
                                        >
                                            <i class="fas fa-check-double"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
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
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-receipt mr-1"></i>Receipt #
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-user mr-1"></i>Customer
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-info-circle mr-1"></i>Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-credit-card mr-1"></i>Payment
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <i class="fas fa-cogs mr-1"></i>Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($completed as $order)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-mono font-medium text-zinc-900 dark:text-zinc-100">
                                        <i class="fas fa-hashtag mr-1 text-zinc-400"></i>{{ $order->receipt_number }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                        <i class="fas fa-user-circle mr-1 text-zinc-400"></i>{{ $order->customer->name ?? 'N/A' }}
                                    </div>
                                </td>
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
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                        <i class="fas fa-check-circle"></i>
                                        Paid
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <button wire:click="viewOrderDetails({{ $order->id }})" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        @if($editingOrderId === $order->id)
                                            <button wire:click="saveEdit({{ $order->id }})" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300" title="Save Changes">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        @else
                                            <button wire:click="editOrder({{ $order->id }})" class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-300" title="Edit Order">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
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
        <div class="fixed inset-0 bg-zinc-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
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
                                            <i class="fas fa-map-marker-alt mr-1"></i>Unit Address:
                                        </dt>
                                        <dd class="text-sm text-zinc-900 dark:text-zinc-100">{{ $selectedOrder->customer->unit_address ?? 'N/A' }}</dd>
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
                                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                                <i class="fas fa-box mr-1"></i>Product
                                            </th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                                <i class="fas fa-sort-numeric-up mr-1"></i>Quantity
                                            </th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                                <i class="fas fa-peso-sign mr-1"></i>Price
                                            </th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
                                                <i class="fas fa-calculator mr-1"></i>Total
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                        @foreach($selectedOrder->orderItems as $item)
                                            <tr>
                                                <td class="px-4 py-2 text-sm text-zinc-900 dark:text-zinc-100">
                                                    <i class="fas fa-tag mr-1 text-zinc-400"></i>{{ $item->product->name ?? 'N/A' }}
                                                </td>
                                                <td class="px-4 py-2 text-sm text-zinc-900 dark:text-zinc-100">
                                                    <i class="fas fa-cubes mr-1 text-zinc-400"></i>{{ $item->quantity }}
                                                </td>
                                                <td class="px-4 py-2 text-sm text-zinc-900 dark:text-zinc-100">₱{{ number_format($item->price, 2) }}</td>
                                                <td class="px-4 py-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">₱{{ number_format($item->quantity * $item->price, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            {{-- Order Total --}}
                            <div class="mt-4 border-t border-zinc-200 dark:border-zinc-700 pt-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                                        <i class="fas fa-receipt mr-2"></i>Total Amount:
                                    </span>
                                    <span class="text-xl font-bold text-zinc-900 dark:text-zinc-100">₱{{ number_format($selectedOrder->orderItems->sum(function($item) { return $item->quantity * $item->price; }), 2) }}</span>
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
