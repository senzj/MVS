{{-- Confirm Modal --}}
@php
    $confirmData = $confirmData ?? [];
    $reviewReceiptNumber = $confirmData['receiptNumber'] ?? '';
    $reviewDateTime = $confirmData['reviewDateTime'] ?? '';
    $reviewOrderType = $confirmData['orderType'] ?? '';
    $reviewPaymentLabel = $confirmData['paymentLabel'] ?? '';
    $reviewPaymentStatusLabel = $confirmData['paymentStatusLabel'] ?? '';
    $reviewStatusLabel = $confirmData['statusLabel'] ?? '';
    $reviewDeliveredBy = $confirmData['deliveredBy'] ?? null;
    $reviewCustomerName = $confirmData['customerName'] ?? null;
    $reviewCustomerContact = $confirmData['customerContact'] ?? null;
    $reviewCustomerUnit = $confirmData['customerUnit'] ?? null;
    $reviewCustomerAddress = $confirmData['customerAddress'] ?? null;
    $reviewItems = $confirmData['items'] ?? [];
    $reviewTotalAmount = $confirmData['totalAmount'] ?? 0;
@endphp
<div x-data="{ show: @entangle('showConfirmModal') }"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;">
    <div class="flex items-end sm:items-center justify-center min-h-screen p-0 sm:p-4 bg-black/50 transition-opacity">
        <div class="relative bg-white dark:bg-zinc-800 rounded-t-2xl sm:rounded-2xl shadow-xl w-full sm:max-w-2xl max-h-[90dvh] sm:max-h-[85vh] overflow-hidden flex flex-col"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95 translate-y-full sm:translate-y-0"
            x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95 translate-y-full sm:translate-y-0">

            <div class=\"sticky top-0 px-5 sm:px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 z-10\">
                <div class="flex items-center justify-between">
                    <h3 class="text-base sm:text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Review Sales Record') }}</h3>
                    <button type="button" wire:click="closeSaveConfirmation"
                        class="cursor-pointer w-8 h-8 flex items-center justify-center rounded-full text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors shrink-0\">
                        <i class="fas fa-times\"></i>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-5 sm:px-6 py-4 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs sm:text-sm">
                    @php
                        $reviewDateTime = $reviewDateTime ?: __('N/A');
                    @endphp
                    <div><span class="font-semibold">{{ __('Order Number') }}:</span> {{ $reviewReceiptNumber }}</div>
                    <div><span class="font-semibold">{{ __('Date & Time') }}:</span> {{ $reviewDateTime }}</div>
                    <div><span class="font-semibold">{{ __('Order Type') }}:</span> {{ $reviewOrderType }}</div>
                    <div><span class="font-semibold">{{ __('Payment Method') }}:</span> {{ $reviewPaymentLabel }}</div>
                    <div><span class="font-semibold">{{ __('Payment Status') }}:</span> {{ $reviewPaymentStatusLabel }}</div>
                    <div><span class="font-semibold">{{ __('Order Status') }}:</span> {{ $reviewStatusLabel }}</div>
                </div>

                @if($reviewOrderType === __('Delivery') || $reviewOrderType === 'Delivery')
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 text-sm">
                        <p class="font-semibold mb-1">{{ __('Delivery') }}</p>
                        <p>{{ __('Delivered By') }}: {{ $reviewDeliveredBy ?: __('Not selected') }}</p>
                    </div>
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 text-sm">
                        <p class="font-semibold mb-1">{{ __('Customer') }}</p>
                        <p>{{ __('Name') }}: {{ $reviewCustomerName ?: __('N/A') }}</p>
                        <p>{{ __('Contact Number') }}: {{ $reviewCustomerContact ?: __('N/A') }}</p>
                        <p>{{ __('Unit') }}: {{ $reviewCustomerUnit ?: __('N/A') }}</p>
                        <p>{{ __('Address') }}: {{ $reviewCustomerAddress ?: __('N/A') }}</p>
                    </div>
                @endif

                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 text-sm overflow-hidden">
                    <p class="font-semibold mb-3">{{ __('Items') }}</p>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[560px] border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700 text-left text-zinc-500 dark:text-zinc-400">
                                    <th class="py-2 pr-3 font-semibold">{{ __('Item') }}</th>
                                    <th class="py-2 pr-3 font-semibold text-right">{{ __('Quantity') }}</th>
                                    <th class="py-2 pr-3 font-semibold text-right">{{ __('Price') }}</th>
                                    <th class="py-2 font-semibold text-right">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reviewItems as $item)
                                    @if(!empty($item['product_id']))
                                        <tr class="border-b border-zinc-100 dark:border-zinc-800 last:border-b-0">
                                            <td class="py-2 pr-3">
                                                <div class="truncate font-medium text-zinc-900 dark:text-zinc-100">
                                                    {{ $item['product_name'] ?: __('Item Product') }}
                                                </div>
                                            </td>
                                            <td class="py-2 pr-3 text-right text-zinc-700 dark:text-zinc-300">
                                                {{ (int) ($item['quantity'] ?? 0) }}
                                            </td>
                                            <td class="py-2 pr-3 text-right text-zinc-700 dark:text-zinc-300">
                                                ₱{{ number_format((float) ($item['price'] ?? 0), 2) }}
                                            </td>
                                            <td class="py-2 text-right font-semibold text-zinc-900 dark:text-zinc-100">
                                                ₱{{ number_format((float) ($item['total'] ?? 0), 2) }}
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-4 text-center text-zinc-500 dark:text-zinc-400">
                                            {{ __('No items selected.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-zinc-200 dark:border-zinc-700 mt-2 pt-2 flex items-center justify-between font-semibold">
                        <span>{{ __('Total Amount') }}</span>
                        <span>₱{{ number_format((float) $reviewTotalAmount, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="sticky bottom-0 px-5 sm:px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 justify-end">
                <button type="button" wire:click="closeSaveConfirmation"
                    class="cursor-pointer px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                    {{ __('Cancel') }}
                </button>

                <button type="button" wire:click="saveSalesRecord"
                    wire:loading.attr="disabled"
                    wire:target="saveSalesRecord"
                    class="cursor-pointer px-4 py-2 text-sm font-semibold text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <span wire:loading.remove wire:target="saveSalesRecord">{{ __('Confirm & Save') }}</span>
                    <span wire:loading wire:target="saveSalesRecord" class="inline-flex items-center gap-2">
                        <i class="fas fa-spinner fa-spin"></i>{{ __('Saving...') }}
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
