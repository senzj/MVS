{{--
    Universal Order Modal
    =====================
    Replaces:  confirm modal + order-details modal
    Modes:
        'confirm'  – review before saving (Add / Edit)
        'walkin'   – confirm + cash/gcash payment in one step (Create walk-in)
        'view'     – read-only order details (Orders index)

    Required props for CONFIRM / WALKIN modes ($confirmData array):
        receiptNumber, reviewDateTime, orderType, paymentLabel,
        paymentStatusLabel, statusLabel, deliveredBy?,
        customerName?, customerContact?, customerUnit?, customerAddress?,
        items[], totalAmount

    Required props for VIEW mode:
        $selectedOrder  – Order model (with orderItems.product, customer, employee loaded)

    Livewire methods expected:
        confirm / walkin  → closeSaveConfirmation(), saveSalesRecord()
        walkin cash       → updatedAmountReceived(), processPayment()
        view              → closeOrderDetailsModal()

    Print hook:
        window.__printOrderModal() is called by the print button;
        wire it up to your receipts module or window.print() directly.
--}}

@php
    $modalMode = $modalMode ?? 'confirm';   // 'confirm' | 'walkin' | 'view'

    // ── Confirm / Walkin data ──────────────────────────────────────
    $cd                  = $confirmData ?? [];
    $reviewReceiptNumber = $cd['receiptNumber']       ?? '';
    $reviewDateTime      = $cd['reviewDateTime']      ?? __('N/A');
    $reviewOrderType     = $cd['orderType']           ?? '';
    $reviewPaymentLabel  = $cd['paymentLabel']        ?? '';
    $reviewPaymentStatus = $cd['paymentStatusLabel']  ?? '';
    $reviewStatusLabel   = $cd['statusLabel']         ?? '';
    $reviewDeliveredBy   = $cd['deliveredBy']         ?? null;
    $reviewCustomerName  = $cd['customerName']        ?? null;
    $reviewCustomerContact = $cd['customerContact']   ?? null;
    $reviewCustomerUnit  = $cd['customerUnit']        ?? null;
    $reviewCustomerAddress = $cd['customerAddress']   ?? null;
    $reviewItems         = $cd['items']               ?? [];
    $reviewTotal         = (float) ($cd['totalAmount'] ?? 0);
    $isDelivery          = in_array($reviewOrderType, [__('Delivery'), 'Delivery', 'deliver']);

    // ── View-mode data ─────────────────────────────────────────────
    $order = $selectedOrder ?? null;  // Order model for 'view' mode

    // ── Walkin-mode extras ─────────────────────────────────────────
    $walkinPaymentType  = $paymentType  ?? 'cash';
    $walkinChange       = $changeAmount ?? 0;
    $walkinImage        = $currentImage ?? null;

    // ── Close / save wire actions ──────────────────────────────────
    $wireClose  = match ($modalMode) {
        'view'   => 'closeOrderDetailsModal',
        default  => 'closeSaveConfirmation',
    };
    $wireSave   = match ($modalMode) {
        'walkin' => 'processPayment',
        'view'   => null,
        default  => 'saveSalesRecord',
    };
    $saveLabel  = match ($modalMode) {
        'walkin' => __('Complete Order'),
        default  => __('Confirm & Save'),
    };
    $entangleProp = ($modalMode === 'view') ? 'showOrderDetailsModal' : 'showConfirmModal';
    $panelWidthClass = $modalMode === 'view'
        ? 'w-full sm:max-w-lg max-h-[92dvh] sm:max-h-[90vh]'
        : 'w-full sm:max-w-2xl max-h-[92dvh] sm:max-h-[88vh]';
@endphp

<div x-data="{ show: @entangle($entangleProp) }"
     x-show="show"
     x-effect="show ? document.body.style.overflow = 'hidden' : document.body.style.overflow = ''"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display:none;">

    {{-- Backdrop --}}
    <div class="flex items-end sm:items-center justify-center min-h-screen p-0 sm:p-4 bg-black/50 backdrop-blur-sm">

        {{-- Panel --}}
        <div class="relative bg-white dark:bg-zinc-800 rounded-t-2xl sm:rounded-2xl shadow-2xl
                {{ $panelWidthClass }} overflow-hidden flex flex-col"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95">

            {{-- ── Header ──────────────────────────────────────────── --}}
            <div class="sticky top-0 z-10 flex items-center justify-between gap-3
                        px-5 sm:px-6 py-4
                        border-b border-zinc-200 dark:border-zinc-700
                        bg-white dark:bg-zinc-800">
                <div class="flex items-center gap-2 min-w-0">
                    <i class="fas fa-file-invoice text-blue-500 shrink-0"></i>
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 truncate">
                        @if($modalMode === 'view')
                            {{ __('Order Details') }}
                            <span class="ml-1 font-mono text-xs text-zinc-400">{{ $order?->receipt_number }}</span>
                        @elseif($modalMode === 'walkin')
                            {{ __('Review & Payment') }}
                        @else
                            {{ __('Review Order') }}
                        @endif
                    </h3>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    {{-- Print button (placeholder hook) --}}
                    <button type="button"
                        onclick="window.__printOrderModal && window.__printOrderModal()"
                        title="{{ __('Print receipt') }}"
                        class="cursor-pointer w-8 h-8 flex items-center justify-center rounded-full
                               text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300
                               hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                        <i class="fas fa-print text-sm"></i>
                    </button>
                    <button type="button" wire:click="{{ $wireClose }}"
                        class="cursor-pointer w-8 h-8 flex items-center justify-center rounded-full
                               text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300
                               hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            {{-- Scrollable body --}}
            <div class="flex-1 overflow-y-auto px-5 sm:px-6 py-5 space-y-5">

                @if($modalMode === 'view' && $order)
                    {{-- VIEW MODE                                       --}}
                    @include('livewire.partials.orders.modal.vieworders', ['order' => $order])

                @else
                    {{-- CONFIRM / WALKIN MODE                          --}}

                    {{-- Receipt header strip --}}
                    <div class="rounded-xl bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-700 p-4">
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-2 text-sm">
                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-0.5">{{ __('Order Number') }}</p>
                                <p class="font-mono font-semibold text-zinc-900 dark:text-zinc-100">{{ $reviewReceiptNumber }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-0.5">{{ __('Date & Time') }}</p>
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $reviewDateTime }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-0.5">{{ __('Order Type') }}</p>
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $reviewOrderType }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-0.5">{{ __('Payment') }}</p>
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $reviewPaymentLabel }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-0.5">{{ __('Status') }}</p>
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $reviewPaymentStatus }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-0.5">{{ __('Order Status') }}</p>
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $reviewStatusLabel }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Delivery / customer info --}}
                    @if($isDelivery)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="rounded-xl bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-700 p-4">
                                <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-2">
                                    <i class="fas fa-user-tie mr-1"></i>{{ __('Delivery') }}
                                </p>
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $reviewDeliveredBy ?: __('Not selected') }}
                                </p>
                            </div>
                            <div class="rounded-xl bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-700 p-4">
                                <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-2">
                                    <i class="fas fa-user mr-1"></i>{{ __('Customer') }}
                                </p>
                                <dl class="space-y-1 text-sm">
                                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $reviewCustomerName    ?: __('N/A') }}</dd>
                                    <dd class="text-zinc-600 dark:text-zinc-400">{{ $reviewCustomerContact ?: __('No contact') }}</dd>
                                    <dd class="text-zinc-600 dark:text-zinc-400">
                                        {{ implode(', ', array_filter([$reviewCustomerUnit, $reviewCustomerAddress])) ?: __('No address') }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    @endif

                    {{-- Items table --}}
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-100 dark:bg-zinc-900/60">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wide">{{ __('Item') }}</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-zinc-500 uppercase tracking-wide">{{ __('Qty') }}</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-zinc-500 uppercase tracking-wide">{{ __('Price') }}</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-zinc-500 uppercase tracking-wide">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-100 dark:divide-zinc-700">
                                @forelse($reviewItems as $item)
                                    @if(!empty($item['product_id']))
                                        <tr>
                                            <td class="px-4 py-2.5 font-medium text-zinc-900 dark:text-zinc-100 truncate max-w-[180px]">
                                                {{ $item['product_name'] ?: __('Product') }}
                                                @if($item['is_free'] ?? false)
                                                    <span class="ml-1 text-xs text-green-600 dark:text-green-400 font-normal">({{ __('Free') }})</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2.5 text-right text-zinc-700 dark:text-zinc-300">{{ (int)($item['quantity'] ?? 0) }}</td>
                                            <td class="px-4 py-2.5 text-right text-zinc-700 dark:text-zinc-300">₱{{ number_format((float)($item['price'] ?? 0), 2) }}</td>
                                            <td class="px-4 py-2.5 text-right font-semibold text-zinc-900 dark:text-zinc-100">
                                                ₱{{ number_format((float)($item['total'] ?? 0), 2) }}
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">
                                            {{ __('No items.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="px-4 py-3 bg-zinc-50 dark:bg-zinc-900/50 border-t border-zinc-200 dark:border-zinc-700
                                    flex items-center justify-between font-semibold text-zinc-900 dark:text-zinc-100">
                            <span>{{ __('Total') }}</span>
                            <span class="text-lg font-black font-mono">₱{{ number_format($reviewTotal, 2) }}</span>
                        </div>
                    </div>

                    {{-- ── Walk-in payment section ────────────────── --}}
                    @if($modalMode === 'walkin')
                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                            <div class="px-4 py-3 bg-zinc-100 dark:bg-zinc-900/60 border-b border-zinc-200 dark:border-zinc-700">
                                <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wide">
                                    <i class="fas fa-money-bill-wave mr-1"></i>{{ __('Payment') }}
                                    <span class="ml-1 font-mono font-normal normal-case text-zinc-700 dark:text-zinc-300">
                                        {{ $walkinPaymentType === 'cash' ? __('Cash') : __('GCash / Online') }}
                                    </span>
                                </p>
                            </div>
                            <div class="p-4 bg-white dark:bg-zinc-800">
                                @if($walkinPaymentType === 'cash')
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                                {{ __('Amount Received') }}
                                            </label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500 font-semibold">₱</span>
                                                <input type="number"
                                                    wire:model.live.debounce.250ms="amountReceived"
                                                    data-field="amountReceived"
                                                    step="0.01" min="0"
                                                    class="w-full pl-8 pr-3 py-2.5 text-sm rounded-xl border border-zinc-300 dark:border-zinc-600
                                                           bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                                                           focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition"
                                                    placeholder="0.00">
                                            </div>
                                            @error('amountReceived')
                                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="flex items-center justify-between px-3 py-2.5 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                                            <span class="text-sm font-medium text-green-800 dark:text-green-300">{{ __('Change') }}</span>
                                            <span class="text-lg font-black font-mono text-green-900 dark:text-green-200">₱{{ number_format((float)$walkinChange, 2) }}</span>
                                        </div>
                                    </div>
                                @else
                                    {{-- GCash QR --}}
                                    <div class="text-center space-y-3">
                                        <div class="inline-flex items-center justify-center rounded-xl overflow-hidden
                                                    {{ $walkinImage ? '' : 'w-32 h-32 bg-zinc-100 dark:bg-zinc-700 border-2 border-dashed border-zinc-300 dark:border-zinc-600' }}">
                                            @if($walkinImage)
                                                <img src="{{ $walkinImage }}" alt="{{ __('GCash QR') }}" class="max-w-[200px] max-h-[200px] object-contain rounded-xl">
                                            @else
                                                <span class="text-xs text-zinc-400">{{ __('No QR') }}</span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ __('Scan to pay') }}: <span class="font-semibold text-zinc-900 dark:text-zinc-100">₱{{ number_format($reviewTotal, 2) }}</span>
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                @endif {{-- end confirm/walkin/view branch --}}
            </div>

            {{-- Footer --}}
            <div class="sticky bottom-0 z-10 flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 justify-end
                        px-5 sm:px-6 py-4
                        bg-zinc-50 dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-700">

                <button type="button" wire:click="{{ $wireClose }}"
                    class="cursor-pointer px-4 py-2.5 text-sm font-medium rounded-xl
                           border border-zinc-300 dark:border-zinc-600
                           text-zinc-700 dark:text-zinc-300
                           bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                    @if($modalMode === 'view')
                        <i class="fas fa-times mr-1"></i>{{ __('Close') }}
                    @else
                        {{ __('Cancel') }}
                    @endif
                </button>

                @if($wireSave)
                    <button type="button"
                        wire:click="{{ $wireSave }}"
                        wire:loading.attr="disabled"
                        wire:target="{{ $wireSave }}"
                        @if($modalMode === 'walkin' && $walkinPaymentType === 'cash' && (float)$walkinChange < 0) disabled @endif
                        class="cursor-pointer px-5 py-2.5 text-sm font-semibold rounded-xl
                               bg-blue-600 text-white hover:bg-blue-700 active:scale-95
                               disabled:opacity-50 disabled:cursor-not-allowed
                               transition-all shadow-md shadow-blue-500/20">
                        <span wire:loading.remove wire:target="{{ $wireSave }}">
                            <i class="fas fa-{{ $modalMode === 'walkin' ? 'check' : 'save' }} mr-1"></i>
                            {{ $saveLabel }}
                        </span>
                        <span wire:loading wire:target="{{ $wireSave }}" class="inline-flex items-center gap-2">
                            <i class="fas fa-spinner fa-spin mr-1"></i>{{ __('Processing...') }}
                        </span>
                    </button>
                @endif
            </div>

        </div>
    </div>
</div>
