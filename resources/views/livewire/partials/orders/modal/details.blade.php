<div class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4 z-50">
    <div class="bg-white dark:bg-zinc-800 w-full sm:rounded-2xl rounded-t-2xl sm:max-w-3xl max-h-[92dvh] overflow-y-auto shadow-2xl">

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
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5">

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
                            <div class="rounded-xl overflow-x-auto border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full divide-y divide-zinc-200 dark:divide-zinc-700 text-xs sm:text-sm">
                            <thead class="bg-zinc-100 dark:bg-zinc-900/60">
                                <tr>
                                    <th class="px-2 sm:px-4 py-2 text-left text-xs font-semibold text-zinc-500 uppercase">ID</th>
                                    <th class="px-2 sm:px-4 py-2 text-left text-xs font-semibold text-zinc-500 uppercase">{{ __('Product') }}</th>
                                    <th class="px-2 sm:px-4 py-2 text-center text-xs font-semibold text-zinc-500 uppercase">Qty</th>
                                    <th class="px-2 sm:px-4 py-2 text-center text-xs font-semibold text-zinc-500 uppercase hidden sm:table-cell">Price</th>
                                    <th class="px-2 sm:px-4 py-2 text-right text-xs font-semibold text-zinc-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-100 dark:divide-zinc-700">
                                @foreach($selectedOrder->orderItems as $item)
                                    <tr>
                                        <td class="px-2 sm:px-4 py-2 text-zinc-500 dark:text-zinc-400 text-xs">#{{ $item->product->id ?? __('N/A') }}</td>
                                        <td class="px-2 sm:px-4 py-2 text-zinc-900 dark:text-zinc-100 text-xs sm:text-sm max-w-xs truncate">{{ $item->product->name ?? '#' }}</td>
                                        <td class="px-2 sm:px-4 py-2 text-center text-zinc-700 dark:text-zinc-300 text-xs sm:text-sm">{{ $item->quantity }}</td>
                                        <td class="px-2 sm:px-4 py-2 text-center text-zinc-700 dark:text-zinc-300 text-xs sm:text-sm hidden sm:table-cell">₱{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="px-2 sm:px-4 py-2 text-right font-semibold text-zinc-900 dark:text-zinc-100 text-xs sm:text-sm">₱{{ number_format($item->total_price, 2) }}</td>
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
