{{-- Payment Modal --}}
<div x-data="{ show: @entangle('showPaymentModal') }"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;">
    <div class="flex items-center justify-center min-h-screen p-4 bg-black/50 transition-opacity">
        <div class="relative bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full mx-auto"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95">

            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Process Payment') }}</h3>
                    <button wire:click="closePaymentModal" class="cursor-pointer text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="px-6 py-4">
                <div class="mb-4 flex justify-between">
                    <label class="block text-sm font-medium text-zinc-900 dark:text-zinc-100 mb-2">
                        {{ __('Payment Method') }}:
                        <span class="font-semibold text-green-400">
                            {{ $paymentType === 'cash' ? __('Cash') : __('Online') }}
                        </span>
                    </label>
                    @if ($paymentType == 'gcash')
                        <div class="relative group">
                            <button type="button" class="cursor-pointer flex items-center justify-center w-6 h-6 text-gray-400 hover:text-gray-600 dark:text-zinc-400 dark:hover:text-zinc-300 transition-colors duration-200">
                                <i class="fas fa-info-circle"></i>
                            </button>
                            <div class="absolute top-full left-0 mb-2 w-70 p-3 bg-gray-900 dark:bg-zinc-700 text-white text-xs rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-50">
                                <p class="font-medium mb-2">{{ __('Online Payment Steps') }}:</p>
                                <ol class="list-decimal list-inside space-y-1">
                                    <li>{{ __('Open your GCash app or any online payment app') }}</li>
                                    <li>{{ __('Scan the QR code below') }}</li>
                                    <li>{{ __('Confirm the amount') }}: ₱{{ number_format($this->totalAmount, 2) }}</li>
                                    <li>{{ __('Complete the payment') }}</li>
                                    <li>{{ __('Click "Complete Order" button') }}</li>
                                </ol>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <h4 class="font-medium text-zinc-900 dark:text-zinc-100 mb-2">{{ __('Order Summary') }}</h4>
                    <div class="space-y-1 text-sm">
                        @foreach($orderItems as $item)
                            @if($item['product_id'])
                                <div class="flex justify-between">
                                    <span>{{ $item['product_name'] }} ({{ $item['quantity'] }}x)</span>
                                    <span>₱{{ number_format($item['total'], 2) }}</span>
                                </div>
                            @endif
                        @endforeach
                        <div class="border-t pt-2 mt-2 font-semibold">
                            <div class="flex justify-between">
                                <span>{{ __('Total Amount') }}:</span>
                                <span>₱{{ number_format($this->totalAmount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if($paymentType === 'cash')
                    <div class="space-y-4">
                        <div>
                            <label for="amountReceived" class="block text-sm font-medium text-zinc-900 dark:text-zinc-100 mb-1">
                                {{ __('Amount Received') }}
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-600">₱</span>
                                <input type="number"
                                    id="amountReceived"
                                    wire:model.live.debounce.300ms="amountReceived"
                                    data-field="amountReceived"
                                    step="0.01"
                                    min="0"
                                    class="w-full pl-8 pr-3 py-2 rounded-lg focus:ring border border-gray-500 transition"
                                    placeholder="0.00">
                            </div>
                        </div>
                        <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-green-800">{{ __('Change') }}:</span>
                                <span class="text-lg font-bold text-green-900">₱{{ number_format($changeAmount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                @elseif ($paymentType === 'gcash')
                    <div class="text-center space-y-2">
                        <div class="mx-auto bg-gray-100 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center overflow-hidden {{ $currentImage ? '' : 'w-32 h-32' }}">
                            @if ($currentImage)
                                <img src="{{ $currentImage }}" alt="GCash QR Code" class="max-w-full max-h-70 object-contain rounded-lg" />
                            @else
                                <span class="text-gray-400 text-sm">{{ __('No Image') }}</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500 mt-1">{{ __('Scan to pay') }}: ₱{{ number_format($this->totalAmount, 2) }}.</p>
                    </div>
                @endif
            </div>

            <div class="px-6 py-4 border-t border-gray-200">
                <div class="flex space-x-3">
                    <button wire:click="closePaymentModal"
                            class="cursor-pointer flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-blue-500">
                        {{ __('Cancel') }}
                    </button>
                    <button wire:click="processPayment"
                            wire:loading.attr="disabled"
                            wire:target="processPayment"
                            @if($paymentType === 'cash' && $changeAmount < 0) disabled @endif
                            class="cursor-pointer flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="processPayment">{{ __('Complete Order') }}</span>
                        <span wire:loading wire:target="processPayment" class="flex items-center justify-center">
                            <i class="fas fa-spinner fa-spin mr-2"></i>{{ __('Processing...') }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
