{{--
    Universal Order Modal
    ===========================
    Modes: 'confirm' | 'walkin' | 'view'

    Props:
        $modalMode  – string  (controls which sections are shown, which actions are wired, etc.)
        $confirmData – array   (for 'confirm' mode: all data needed to render the order summary)
        $selectedOrder – Order model (for 'view' mode: the order to display)
        $isEditMode – bool (optional, passed by the Edit page only) — when true,
                      the proof section in confirm mode renders read-only.
    The 'view' mode is used for viewing order details from the orders list, and also for the "receipt" view after confirming a new order or walk-in payment.
--}}

@php
    $modalMode = $modalMode ?? 'confirm';

    // ── Confirm / Walkin ────────────────────────────────────────────
    $cd                    = $confirmData ?? [];
    $reviewReceiptNumber   = $cd['receiptNumber']      ?? '';
    $reviewDateTime        = $cd['reviewDateTime']     ?? __('N/A');
    $reviewOrderType       = $cd['orderType']          ?? '';
    $reviewPaymentLabel    = $cd['paymentLabel']       ?? '';  // 'Cash', 'GCash', etc.
    $reviewPaymentStatus   = $cd['paymentStatusLabel'] ?? '';  // 'Unpaid' | 'Paid' | 'Refunded'
    $reviewStatusLabel     = $cd['statusLabel']        ?? '';
    $reviewDeliveredBy     = $cd['deliveredBy']        ?? null;
    $reviewCustomerName    = $cd['customerName']       ?? null;
    $reviewCustomerContact = $cd['customerContact']    ?? null;
    $reviewCustomerUnit    = $cd['customerUnit']       ?? null;
    $reviewCustomerAddress = $cd['customerAddress']    ?? null;
    $reviewItems           = $cd['items']              ?? [];
    $reviewSubtotal        = (float) ($cd['subtotalAmount'] ?? 0);
    $reviewDiscountType    = $cd['discountType']       ?? 'none';
    $reviewDiscountValue   = (float) ($cd['discountValue'] ?? 0);
    $reviewDiscountPresetName = $cd['discountPresetName'] ?? null;
    $reviewDiscountAmount  = (float) ($cd['discountAmount'] ?? 0);
    $reviewTotal           = (float) ($cd['totalAmount'] ?? 0);
    $reviewStatusKey       = $cd['statusKey']          ?? strtolower(str_replace(' ', '_', $reviewStatusLabel));
    $isDelivery            = in_array($reviewOrderType, [__('Delivery'), 'Delivery', 'deliver']);

    // ── View mode ───────────────────────────────────────────────────
    $order = $selectedOrder ?? null;

    // ── Walk-in payment extras ──────────────────────────────────────
    $walkinPaymentType = $paymentType  ?? 'cash';
    $walkinChange      = $changeAmount ?? 0;
    $walkinImage       = $currentImage ?? null;

    // Show QR only for walk-in + (gcash or maya) + not yet paid.
    // (Previous version: `$a === 'gcash' || $a === 'maya' && !$b && $c` —
    // && binds tighter than ||, so this always evaluated true for gcash
    // regardless of delivery/payment status. Wrapped in in_array now.)
    $showQr = in_array($walkinPaymentType, ['gcash', 'maya'], true)
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

    // ── Proof visibility (confirm mode: Add / Create / Edit) ──
    // Raw payment type regardless of host naming convention: Create/Add
    // expose $paymentType (camelCase), Edit exposes $payment_type (snake).
    // Falls back to the review label only if neither raw property is in scope.
    $rawPaymentType = strtolower((string) ($paymentType ?? $payment_type ?? $reviewPaymentLabel ?? ''));
    $isCashPayment  = $rawPaymentType === 'cash' || $rawPaymentType === strtolower(__('Cash')) || $rawPaymentType === '';

    // Edit's page passes this explicitly — its review modal shows existing
    // proof for reference only and never allows uploading/replacing it here.
    $isEditReviewMode = $isEditMode ?? false;
@endphp

<div
    x-data="{ show: @entangle($entangleProp) }"
    x-cloak
    x-init="$watch('show', value => {
        document.body.style.overflow = value ? 'hidden' : ''
    })"
>

    <div x-show="show" style="display:none" class="fixed inset-0 z-50 overflow-y-auto">

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

                    {{-- Header --}}
                    <div class="sticky top-0 z-10 flex items-center justify-between gap-3 px-5 sm:px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">

                        {{-- Title --}}
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

                        {{-- Actions --}}
                        <div class="flex items-center gap-1 shrink-0">
                            {{-- Print Button --}}
                            <button type="button"
                                onclick="window.__printOrderModal && window.__printOrderModal()"
                                title="{{ __('Print receipt') }}"
                                class="cursor-pointer w-8 h-8 flex items-center justify-center rounded-full
                                    text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300
                                    hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                                <i class="fas fa-print text-sm"></i>
                            </button>

                            {{-- Close Button --}}
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
                            {{-- VIEW MODE — receipt layout --}}
                            @include('livewire.partials.orders.modal.vieworders', [
                                'order' => $order
                            ])

                        @else
                            {{-- CONFIRM / WALKIN --}}
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
                                'reviewSubtotal' => $reviewSubtotal,
                                'reviewDiscountType' => $reviewDiscountType,
                                'reviewDiscountValue' => $reviewDiscountValue,
                                'reviewDiscountPresetName' => $reviewDiscountPresetName,
                                'reviewDiscountAmount' => $reviewDiscountAmount,
                                'reviewTotal' => $reviewTotal,
                                'showProofSection' => false,
                                'showFooter' => false,
                            ])

                            {{-- ── Walk-in payment ────────────────────────── --}}
                            @if($modalMode === 'walkin')
                                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">

                                    {{-- Section label --}}
                                    <div class="px-4 py-2.5 bg-zinc-100 dark:bg-zinc-900/60 border-b border-zinc-200 dark:border-zinc-700">
                                        <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide flex items-center gap-1.5">
                                            <i class="fas fa-money-bill-wave"></i>
                                            {{ __('Payment') }}
                                            <span class="font-mono font-normal normal-case text-zinc-700 dark:text-zinc-300 ml-0.5">
                                                — {{ ucwords(str_replace('_', ' ', $walkinPaymentType)) }}
                                            </span>
                                        </p>
                                    </div>

                                    {{-- Payment Information --}}
                                    <div class="p-4 bg-white dark:bg-zinc-800 space-y-4">

                                        {{-- Cash: amount received + change --}}
                                        @if($walkinPaymentType === 'cash')
                                            @if($reviewTotal > 0)
                                                @include('livewire.partials.orders.payment.cash', [
                                                    'total'          => $reviewTotal,
                                                    'amountReceived' => $amountReceived ?? $reviewTotal,
                                                ])
                                            @else
                                                <p class="text-sm text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-900 rounded-xl px-4 py-3">
                                                    {{ __('Total is zero — no cash input required.') }}
                                                </p>
                                            @endif

                                        {{-- Non Cash (unpaid walk-in) + proof upload --}}
                                        @else
                                            @php
                                                $qrOptions    = $paymentQrOptions ?? [];
                                                $hasQrOptions = ! empty($qrOptions);
                                                $selectedQrId = $paymentQrId ?? null;
                                            @endphp

                                            @if($hasQrOptions)
                                                <div class="space-y-4">

                                                    {{-- QR account picker (only when >1 option) --}}
                                                    @if(count($qrOptions) > 1)
                                                        <div class="space-y-2">
                                                            <p class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                                                <i class="fas fa-hand-pointer mr-1"></i>{{ __('Select Payment QR') }}
                                                            </p>

                                                            {{-- Pill buttons — one per QR account --}}
                                                            <div class="flex flex-wrap gap-2">
                                                                @foreach($qrOptions as $qrOpt)
                                                                    <button type="button"
                                                                            wire:click="$set('paymentQrId', {{ $qrOpt['id'] }})"
                                                                            wire:loading.attr="disabled"
                                                                            wire:target="$set('paymentQrId', {{ $qrOpt['id'] }})"
                                                                            class="cursor-pointer inline-flex items-center gap-1.5
                                                                                px-3 py-1.5 rounded-xl text-xs font-semibold
                                                                                border transition-all duration-150
                                                                                {{ $selectedQrId == $qrOpt['id']
                                                                                    ? 'bg-blue-600 dark:bg-blue-500 text-white border-blue-600 dark:border-blue-500 shadow-sm shadow-blue-500/25'
                                                                                    : 'bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border-zinc-300 dark:border-zinc-600 hover:border-blue-400 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/10' }}">
                                                                        <i class="fas fa-qrcode text-xs
                                                                            {{ $selectedQrId == $qrOpt['id'] ? 'text-white' : 'text-zinc-400 dark:text-zinc-500' }}">
                                                                        </i>
                                                                        {{ $qrOpt['name'] }}
                                                                    </button>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif

                                                    {{-- QR image + scan hint --}}
                                                    @if($walkinImage)
                                                        <div class="flex flex-col items-center gap-3 pt-1">

                                                            {{-- QR card --}}
                                                            <div class="rounded-2xl border-2 border-dashed
                                                                        border-blue-200 dark:border-blue-800/60
                                                                        bg-linear-to-b from-blue-50/60 to-white
                                                                        dark:from-blue-900/10 dark:to-zinc-800/20
                                                                        p-4 flex flex-col items-center gap-3 w-full">

                                                                {{-- Image wrapper --}}
                                                                <div class="rounded-xl overflow-hidden
                                                                            border border-zinc-200 dark:border-zinc-700
                                                                            bg-white dark:bg-zinc-900
                                                                            shadow-sm">
                                                                    <img src="{{ $walkinImage }}"
                                                                        alt="{{ __('Payment QR Code') }}"
                                                                        class="w-58 h-68 object-contain">
                                                                </div>

                                                                {{-- Amount --}}
                                                                <div class="text-center">
                                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                                        <i class="fas fa-mobile-screen-button mr-1 text-blue-400"></i>
                                                                        {{ __('Scan to pay') }}
                                                                    </p>
                                                                    <p class="text-xl font-black font-mono text-zinc-900 dark:text-zinc-100 mt-0.5">
                                                                        {{ config('storeconfig.currency_symbol') }}{{ number_format($reviewTotal, 2) }}
                                                                    </p>
                                                                </div>
                                                            </div>

                                                            {{-- Switch hint when only 1 option --}}
                                                            @if(count($qrOptions) === 1)
                                                                <p class="text-xs text-center text-zinc-400 dark:text-zinc-500">
                                                                    {{ $qrOptions[0]['name'] }}
                                                                </p>
                                                            @endif
                                                        </div>

                                                    @else
                                                        {{-- No QR selected yet (shouldn't normally appear since auto-select runs, but safe fallback) --}}
                                                        <div class="flex flex-col items-center gap-2 py-6 text-center
                                                                    text-zinc-400 dark:text-zinc-500">
                                                            <i class="fas fa-qrcode text-3xl opacity-20"></i>
                                                            <p class="text-xs">{{ __('Select an account above to show its QR code.') }}</p>
                                                        </div>
                                                    @endif

                                                </div>

                                            @else
                                                {{-- No QR codes configured at all --}}
                                                <div class="flex items-start gap-3 p-4 rounded-xl
                                                            bg-amber-50 dark:bg-amber-900/10
                                                            border border-amber-200 dark:border-amber-700/50">
                                                    <i class="fas fa-triangle-exclamation text-amber-500 dark:text-amber-400 shrink-0 mt-0.5"></i>
                                                    <div>
                                                        <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">
                                                            {{ __('No QR codes configured') }}
                                                        </p>
                                                        <p class="text-xs text-amber-600 dark:text-amber-500 mt-0.5">
                                                            {{ __('Add payment QR codes in Settings → Payment QR.') }}
                                                        </p>
                                                    </div>
                                                </div>
                                            @endif

                                            {{-- Proof of payment upload (always shown for non-cash) --}}
                                            @include('livewire.partials.orders.payment.proof', [
                                                'compact'          => true,
                                                'existingProofUrl' => null,
                                                'paymentType'      => $walkinPaymentType,
                                            ])
                                        @endif

                                    </div>
                                </div>
                            @endif

                            {{-- Proof of payment — confirm mode (Add / Create / Edit pages). --}}
                            @if($modalMode === 'confirm' && ! $isCashPayment)
                                @include('livewire.partials.orders.payment.proof', [
                                    'compact'          => true,
                                    'existingProofUrl' => $confirmProofUrl,
                                    'paymentType'      => $paymentType ?? $payment_type ?? $reviewPaymentLabel,
                                    'readOnly'         => $isEditReviewMode,
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

    </div>

</div>
