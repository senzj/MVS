{{--
    Universal Order Modal  (v2)
    ===========================
    Modes: 'confirm' | 'walkin' | 'view'

    Props:
        $modalMode  – string  (controls which sections are shown, which actions are wired, etc.)
        $confirmData – array   (for 'confirm' mode: all data needed to render the order summary)
        $selectedOrder – Order model (for 'view' mode: the order to display)
    The 'view' mode is used for viewing order details from the orders list, and also for the "receipt" view after confirming a new order or walk-in payment.
--}}

@php
    $modalMode = $modalMode ?? 'confirm';

    // ── Confirm / Walkin ────────────────────────────────────────────
    $cd                    = $confirmData ?? [];
    $reviewReceiptNumber   = $cd['receiptNumber']      ?? '';
    $reviewDateTime        = $cd['reviewDateTime']     ?? __('N/A');
    $reviewOrderType       = $cd['orderType']          ?? '';
    $reviewPaymentLabel    = $cd['paymentLabel']       ?? '';
    $reviewPaymentStatus   = $cd['paymentStatusLabel'] ?? '';  // 'Unpaid' | 'Paid' | 'Refunded'
    $reviewStatusLabel     = $cd['statusLabel']        ?? '';
    $reviewDeliveredBy     = $cd['deliveredBy']        ?? null;
    $reviewCustomerName    = $cd['customerName']       ?? null;
    $reviewCustomerContact = $cd['customerContact']    ?? null;
    $reviewCustomerUnit    = $cd['customerUnit']       ?? null;
    $reviewCustomerAddress = $cd['customerAddress']    ?? null;
    $reviewItems           = $cd['items']              ?? [];
    $reviewTotal           = (float) ($cd['totalAmount'] ?? 0);
    $reviewStatusKey       = $cd['statusKey']          ?? strtolower(str_replace(' ', '_', $reviewStatusLabel));
    $isDelivery            = in_array($reviewOrderType, [__('Delivery'), 'Delivery', 'deliver']);
    $isGcash               = str_contains(strtolower((string)$reviewPaymentLabel), 'gcash');

    // ── View mode ───────────────────────────────────────────────────
    $order = $selectedOrder ?? null;

    // ── Walk-in payment extras ──────────────────────────────────────
    $walkinPaymentType = $paymentType  ?? 'cash';
    $walkinChange      = $changeAmount ?? 0;
    $walkinImage       = $currentImage ?? null;
    // Show QR only for walk-in + gcash + not yet paid
    $showQr = $walkinPaymentType === 'gcash'
              || $walkinPaymentType === 'maya'
              && ! $isDelivery
              && in_array($reviewPaymentStatus, [__('Unpaid'), 'Unpaid', 'unpaid']);

    // ── Wire actions ────────────────────────────────────────────────
    $wireClose    = $modalMode === 'view' ? 'closeOrderDetailsModal' : 'closeSaveConfirmation';
    $wireSave     = match ($modalMode) { 'walkin' => 'processPayment', 'view' => null, default => 'saveSalesRecord' };
    $saveLabel    = $modalMode === 'walkin' ? __('Complete Order') : __('Confirm & Save');
    $entangleProp = $modalMode === 'view' ? 'showOrderDetailsModal' : 'showConfirmModal';
    $panelWidth   = $modalMode === 'view'
        ? 'w-full sm:max-w-xl max-h-[98dvh] sm:max-h-[90vh]'
        : 'w-full sm:max-w-xl max-h-[98dvh] sm:max-h-[90vh]';
    $currentOrderType = $orderType ?? ($order_type ?? $reviewOrderType ?? '');
    $confirmProofUrl = $existingProofUrl
        ?? (!empty($existingProof) ? asset('storage/' . $existingProof) : null);
    $confirmProofAllowCamera = $modalMode === 'confirm'
        ? $currentOrderType === 'walk_in'
        : false;
@endphp

<div
    x-data="{ show: @entangle($entangleProp) }"
    x-cloak
    x-init="$watch('show', value => {
        document.body.style.overflow = value ? 'hidden' : ''
    })"
>

    <template x-if="show">

        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end sm:items-center justify-center min-h-screen p-0 sm:p-4 bg-black/60">

                <div class="relative bg-white dark:bg-zinc-800 rounded-lg sm:rounded-2xl shadow-2xl
                            {{ $panelWidth }} overflow-hidden flex flex-col"
                    x-transition:enter="transition ease-out duration-250"
                    x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0 translate-y-6 sm:scale-95">

                    {{-- ── Header ──────────────────────────────────────────── --}}
                    <div class="sticky top-0 z-10 flex items-center justify-between gap-3
                                px-5 sm:px-6 py-4 border-b border-zinc-200 dark:border-zinc-700
                                bg-white dark:bg-zinc-900">

                        <div class="flex items-center gap-2 min-w-0">
                            <i class="fas fa-file-invoice text-blue-500 shrink-0"></i>
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 truncate">
                                @if($modalMode === 'view')
                                    {{ __('Order Information') }}
                                @elseif($modalMode === 'walkin')
                                    {{ __('Review & Payment') }}
                                @else {{-- create/edit --}}
                                    {{ __('Review Orders') }}
                                @endif
                            </h3>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
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

                    {{-- ── Body ────────────────────────────────────────────── --}}
                    <div class="flex-1 overflow-y-auto px-5 sm:px-6 py-5 space-y-5">

                        @if($modalMode === 'view' && $order)
                            {{-- ════════════════════════════════════════════ --}}
                            {{-- VIEW MODE — receipt layout                   --}}
                            {{-- ════════════════════════════════════════════ --}}
                            @include('livewire.partials.orders.modal.vieworders', [
                                'order' => $order
                            ])

                        @else
                            {{-- ════════════════════════════════════════════ --}}
                            {{-- CONFIRM / WALKIN                             --}}
                            {{-- ════════════════════════════════════════════ --}}
                            @include('livewire.partials.orders.modal.vieworders', [
                                'previewMode' => true,
                                'reviewReceiptNumber' => $reviewReceiptNumber,
                                'reviewDateTime' => $reviewDateTime,
                                'reviewOrderType' => $reviewOrderType,
                                'reviewPaymentLabel' => $reviewPaymentLabel,
                                'reviewPaymentStatus' => $reviewPaymentStatus,
                                'reviewStatusLabel' => $reviewStatusLabel,
                                'reviewStatusKey' => $reviewStatusKey,
                                'reviewDeliveredBy' => $reviewDeliveredBy,
                                'reviewCustomerName' => $reviewCustomerName,
                                'reviewCustomerContact' => $reviewCustomerContact,
                                'reviewCustomerUnit' => $reviewCustomerUnit,
                                'reviewCustomerAddress' => $reviewCustomerAddress,
                                'reviewItems' => $reviewItems,
                                'reviewTotal' => $reviewTotal,
                                'showProofSection' => false,
                                'showFooter' => false,
                            ])

                            {{-- ── Walk-in payment ────────────────────────── --}}
                            @if($modalMode === 'walkin')
                                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                                    <div class="px-4 py-2.5 bg-zinc-100 dark:bg-zinc-900/60 border-b border-zinc-200 dark:border-zinc-700">
                                        <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wide">
                                            <i class="fas fa-money-bill-wave mr-1"></i>{{ __('Payment') }}
                                            <span class="ml-1 font-mono font-normal normal-case text-zinc-700 dark:text-zinc-300">
                                                {{ $walkinPaymentType }}
                                            </span>
                                        </p>
                                    </div>
                                    <div class="p-4 bg-white dark:bg-zinc-800 space-y-4">

                                        {{-- Cash: amount received + change --}}
                                        @if($walkinPaymentType === 'cash')
                                            @if($reviewTotal > 0)
                                                <div class="space-y-3">
                                                    <div x-data="{
                                                        received: '',
                                                        total: {{ $reviewTotal }},
                                                        get change() {
                                                            const r = parseFloat(this.received) || 0;
                                                            const c = r - this.total;
                                                            return c;
                                                        },
                                                        get changeFormatted() {
                                                            return '₱' + Math.abs(this.change).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                                                        },
                                                        commit() {
                                                            $wire.set('amountReceived', this.received);
                                                        }
                                                    }" class="space-y-3">
                                                        <div>
                                                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                                                {{ __('Amount Received') }}
                                                            </label>
                                                            <div class="relative">
                                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500 font-semibold text-sm">₱</span>
                                                                <input type="number"
                                                                    x-model="received"
                                                                    @blur="commit()"
                                                                    @keydown.enter.prevent="commit()"
                                                                    step="0.01" min="0"
                                                                    class="w-full pl-8 pr-3 py-2.5 text-sm rounded-xl
                                                                        border border-zinc-300 dark:border-zinc-600
                                                                        bg-zinc-50 dark:bg-zinc-700/60
                                                                        text-zinc-900 dark:text-zinc-100
                                                                        focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition"
                                                                    placeholder="0.00">
                                                            </div>
                                                        </div>

                                                        <div class="flex items-center justify-between px-4 py-3 rounded-xl"
                                                            :class="change >= 0 ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'">
                                                            <span class="text-sm font-medium"
                                                                :class="change >= 0 ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300'">
                                                                {{ __('Change') }}
                                                            </span>
                                                            <span class="text-xl font-black font-mono"
                                                                :class="change >= 0 ? 'text-green-900 dark:text-green-200' : 'text-red-900 dark:text-red-200'"
                                                                x-text="changeFormatted">
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <p class="text-sm text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-900 rounded-xl px-4 py-3">
                                                    {{ __('Total is zero — no cash input required.') }}
                                                </p>
                                            @endif

                                        {{-- GCash: QR (unpaid walk-in only) + proof upload --}}
                                        @elseif($walkinPaymentType != 'cash')
                                            @if($showQr && $walkinImage)
                                                <div class="text-center space-y-2">
                                                    <div class="inline-block rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-700">
                                                        <img src="{{ $walkinImage }}" alt="{{ __('GCash QR') }}" class="max-w-90 max-h-96 object-contain">
                                                    </div>
                                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                                        {{ __('Scan to pay') }}:
                                                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">₱{{ number_format($reviewTotal, 2) }}</span>
                                                    </p>
                                                </div>
                                            @endif

                                            {{-- Proof of payment upload --}}
                                            @include('livewire.partials.orders.proof-of-payment', [
                                                'compact'          => true,
                                                'existingProofUrl' => null,
                                                'paymentType'      => $walkinPaymentType,
                                            ])
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- GCash proof upload — confirm mode (Add / Edit pages) --}}
                            @if($modalMode === 'confirm' && $isGcash && (! empty($confirmProofUrl) || ! empty($proofOfPayment)))
                                @include('livewire.partials.orders.proof-of-payment', [
                                    'compact'          => true,
                                    'existingProofUrl' => $confirmProofUrl,
                                    'paymentType'      => $paymentType ?? $reviewPaymentLabel,
                                    'readOnly'         => false,
                                    'allowCamera'      => $confirmProofAllowCamera,
                                ])
                            @endif

                        @endif
                    </div>

                    {{-- ── Footer ───────────────────────────────────────────── --}}
                    <div class="sticky bottom-0 z-10 flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 justify-end
                                px-5 sm:px-6 py-4 border-t border-zinc-200 dark:border-zinc-700
                                bg-white dark:bg-zinc-900">

                            {{-- Delete action (history page only) --}}
                            @if(!empty($showDelete) && $showDelete && isset($order))
                            <div class="flex w-full items-center justify-between gap-2">
                                <button
                                    wire:click="confirmDelete({{ $order->id }})"
                                    class="cursor-pointer inline-flex items-center px-4 py-2.5 text-sm font-medium rounded-lg
                                        text-red-600 dark:text-red-400
                                        bg-red-50 dark:bg-red-900/20
                                        hover:bg-red-100 dark:hover:bg-red-900/40
                                        border border-red-200 dark:border-red-800 transition-colors">
                                    <i class="fas fa-trash mr-1"></i>
                                    {{ __('Delete Order') }}
                                </button>
                            @else
                                <div class="flex w-full items-center justify-end gap-2">
                            @endif

                            {{-- Confirm + cancel --}}
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="{{ $wireClose }}"
                                    class="cursor-pointer px-4 py-2.5 text-sm font-medium rounded-lg
                                        border border-zinc-300 dark:border-zinc-600
                                        text-zinc-700 dark:text-zinc-300
                                        bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                                    @if($modalMode === 'view') <i class="fas fa-times mr-1"></i> @endif
                                    {{ $modalMode === 'view' ? __('Close') : __('Cancel') }}
                                </button>

                                @if($wireSave)
                                    <button type="button"
                                        wire:click="{{ $wireSave }}"
                                        wire:loading.attr="disabled"
                                        wire:target="{{ $wireSave }}"
                                        class="cursor-pointer px-5 py-2.5 text-sm font-semibold rounded-lg
                                            bg-blue-600 text-white hover:bg-blue-700 active:scale-95
                                            disabled:opacity-50 disabled:cursor-not-allowed
                                            transition-all shadow-md shadow-blue-500/20">
                                        <span wire:loading.remove wire:target="{{ $wireSave }}">
                                            <i class="fas fa-{{ $modalMode === 'walkin' ? 'check' : 'save' }} mr-1"></i>
                                            {{ $saveLabel }}
                                        </span>
                                        <span wire:loading wire:target="{{ $wireSave }}" class="inline-flex items-center gap-2">
                                            <i class="fas fa-spinner fa-spin mr-1"></i>{{ __('Processing') }}
                                        </span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </template>

</div>
