{{--
    vieworders.blade.php  (receipt layout)
    =======================================
    Rendered inside the universal modal when $modalMode === 'view'.

    Props: $order — Order model (orderItems.product, customer, employee loaded)
--}}

@php
    $loc    = app()->getLocale() === 'cn' ? 'zh_CN' : app()->getLocale();
    $previewMode = $previewMode ?? false;
    $showProofSection = $showProofSection ?? true;
    $showFooter = $showFooter ?? true;

    $sourceOrder = $order ?? null;
    $statusKey = $sourceOrder?->status ?? ($reviewStatusKey ?? null);
    $paymentType = strtolower($sourceOrder->payment_type ?? ($reviewPaymentLabel ?? ''));
    $isGcash = $paymentType === 'gcash';
    $isDelivery = $sourceOrder?->order_type === 'deliver' || in_array($reviewOrderType ?? '', [__('Delivery'), 'Delivery', 'deliver']);

    $receiptNumber = $sourceOrder?->receipt_number ?? ($reviewReceiptNumber ?? '');
    $receiptDate = $sourceOrder?->created_at
        ? $sourceOrder->created_at->locale($loc)->isoFormat('ddd, MMM D, YYYY · hh:mm:ss A')
        : ($reviewDateTime ?? __('N/A'));

    $paymentLabel = $sourceOrder
        ? (strtolower($sourceOrder->payment_type ?? '') === 'cash' ? __('Cash') : __('GCash / Online'))
        : ($reviewPaymentLabel ?? __('N/A'));

    $paymentStatus = $sourceOrder?->payment_status ?? strtolower((string)($reviewPaymentStatus ?? ''));
    $displayItems = $sourceOrder
        ? $sourceOrder->orderItems
        : collect($reviewItems ?? [])->map(function ($item) {
            return (object) [
                'product_id' => $item['product_id'] ?? null,
                'product_name' => $item['product_name'] ?? null,
                'quantity' => (int) ($item['quantity'] ?? 0),
                'unit_price' => (float) ($item['price'] ?? 0),
                'total_price' => (float) ($item['total'] ?? 0),
                'is_free' => (bool) ($item['is_free'] ?? false),
                'product' => null,
            ];
        });

    $customerName = $sourceOrder?->customer?->name ?? ($reviewCustomerName ?? null);
    $customerContact = $sourceOrder?->customer?->contact_number ?? ($reviewCustomerContact ?? null);
    $customerUnit = $sourceOrder?->customer?->unit ?? ($reviewCustomerUnit ?? null);
    $customerAddress = $sourceOrder?->customer?->address ?? ($reviewCustomerAddress ?? null);
    $deliveredBy = $sourceOrder?->employee?->name ?? ($reviewDeliveredBy ?? null);
    $orderTotal = $sourceOrder?->order_total ?? (float) ($reviewTotal ?? 0);
    $amountReceived = $sourceOrder?->amount_received ?? null;
    $changeAmount = $sourceOrder?->change_amount ?? null;
    $existingProofUrl = $sourceOrder?->proof_url ?? null;
@endphp

<div class="mx-auto w-full max-w-md space-y-3 text-sm text-gray-800 dark:text-gray-100 select-text">

    {{-- Header --}}
    <div class="space-y-2.5 pb-3 border-b border-zinc-800/50 dark:border-gray-200/50">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xl font-bold tracking-tight text-zinc-800 dark:text-zinc-200 truncate">
                    #{{ $receiptNumber }}
                </p>
            </div>
            <div class="text-right">
                <p class="text-xs text-zinc-800 dark:text-zinc-200 leading-tight">
                    {{ $receiptDate }}
                </p>
            </div>
        </div>
    </div>

    {{-- Invoice metadata --}}
    <div class="space-y-2.5 pb-3 border-b border-zinc-800/50 dark:border-gray-200/50">
        @if($sourceOrder)
            <div class="text-sm text-zinc-700 dark:text-zinc-300">
                {{ __('Order ID') }}:
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                    #{{ $sourceOrder->id }}
                </span>
            </div>
        @endif

        {{-- Customer / delivery block --}}
        @if($isDelivery)
            @php $addr = implode(', ', array_filter([$customerUnit, $customerAddress])); @endphp
            <div class="space-y-2 border-b border-zinc-800/50 dark:border-zinc-200/50 pb-2">
                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">

                    <div class="space-y-0.5">
                        <p class="text-[13px] uppercase text-zinc-700 dark:text-zinc-300">{{ __('Customer Name') }}</p>
                        <p class="font-semibold text-zinc-900 dark:text-zinc-100 leading-snug">{{ $customerName ?: 'N/A' }}</p>
                    </div>

                    <div class="space-y-0.5 text-right">
                        <p class="text-[13px] uppercase text-zinc-700 dark:text-zinc-300">{{ __('Contact Number') }}</p>
                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $customerContact ?: __('Not Provided') }}</p>
                    </div>
                </div>

                @if($addr)
                    <div class="space-y-0.5">
                        <p class="text-[13px] uppercase text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Address') }}</p>
                        <p class="font-semibold text-zinc-900 dark:text-zinc-100 leading-snug">{{ $addr }}</p>
                    </div>
                @endif
            </div>
        @endif

        <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
            <div class="space-y-0.5">
                <p class="text-[13px] uppercase  text-zinc-700 dark:text-zinc-300">{{ __('Cashier') }}</p>
                <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $sourceOrder?->staff?->name ?? __('Staff') }}</p>
            </div>
            <div class="space-y-0.5 text-right">
                <p class="text-[13px] uppercase  text-zinc-700 dark:text-zinc-300">{{ __('Courier') }}</p>
                <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $deliveredBy ?? __('N/A') }}</p>
            </div>

            <div class="space-y-0.5">
                <p class="text-[13px] uppercase  text-zinc-700 dark:text-zinc-300">{{ __('Order Type') }}</p>
                <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $isDelivery ? __('Delivery') : __('Walk-In') }}</p>
            </div>
            <div class="space-y-0.5 text-right">
                <p class="text-[13px] uppercase  text-zinc-700 dark:text-zinc-300">{{ __('Payment') }}</p>
                <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $paymentLabel }}</p>
            </div>

            <div class="space-y-0.5">
                <p class="text-[13px] uppercase  text-zinc-700 dark:text-zinc-300">{{ __('Order Status') }}</p>
                <div class="inline-flex justify-start">
                    @include('livewire.partials.orders.status.order-badge', ['order' => $sourceOrder])
                </div>
            </div>

            <div class="space-y-0.5 text-right">
                <p class="text-[13px] uppercase  text-zinc-700 dark:text-zinc-300">{{ __('Payment Status') }}</p>
                <div class="inline-flex justify-end">
                    @include('livewire.partials.orders.status.payment-badge', ['status' => $paymentStatus])
                </div>
            </div>
        </div>
    </div>

    {{-- Items --}}
    @if($displayItems && $displayItems->count() > 0)
        <div class="space-y-2.5 pb-3 border-b border-zinc-800/50 dark:border-gray-200/50">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <p class="text-[13px] uppercase tracking-[0.22em] text-zinc-700 dark:text-zinc-300">{{ __('Orders') }}</p>
                </div>
                <p class="text-xs text-zinc-700 dark:text-zinc-300">{{ $displayItems->count() }} {{ __('item(s)') }}</p>
            </div>

            <div class="overflow-hidden">
                <div class="grid grid-cols-[1fr_3.25rem_4.5rem_5rem] gap-2 px-3 py-2 text-[13px] uppercase text-zinc-700 dark:text-zinc-300 border-b border-dashed border-gray-500/80">
                    <span class="text-center">{{ __('Item') }}</span>
                    <span class="text-center">{{ __('Qty / kg') }}</span>
                    <span class="text-center">{{ __('Price') }}</span>
                    <span class="text-center">{{ __('Total') }}</span>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($displayItems as $item)
                        @php
                            $refundedQty = (int) ($item->refunded_quantity ?? 0);
                            $isFullyRefunded = $refundedQty > 0 && $refundedQty >= (int) $item->quantity;
                            $isPartiallyRefunded = $refundedQty > 0 && $refundedQty < (int) $item->quantity;
                        @endphp
                        <div class="grid grid-cols-[1fr_3.25rem_4.5rem_5rem] gap-2 px-3 py-2.5 items-center
                                    {{ $isFullyRefunded ? 'opacity-50' : '' }}">
                            <div class="min-w-0 pr-1">
                                <div class="flex items-center gap-1 min-w-0 flex-wrap">
                                    <span class="font-semibold text-gray-900 dark:text-gray-100 leading-snug break-words
                                                {{ $isFullyRefunded ? 'line-through' : '' }}">
                                        {{ $item->product?->name ?? $item->product_name ?? '#' . $item->product_id }}
                                    </span>
                                    @if($isFullyRefunded)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold
                                                    bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                                            <i class="fas fa-undo text-[9px]"></i>{{ __('Refunded') }}
                                        </span>
                                    @elseif($isPartiallyRefunded)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold
                                                    bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">
                                            <i class="fas fa-undo text-[9px]"></i>{{ __('Partial') }} ({{ $refundedQty }} {{ __('returned') }})
                                        </span>
                                    @elseif((float) ($item->total_price ?? 0) <= 0 && (int) ($item->quantity ?? 0) > 0)
                                        <span class="inline-flex items-center rounded bg-emerald-50 dark:bg-emerald-900/20 px-1.5 py-0.5 text-[9px] font-semibold text-emerald-700 dark:text-emerald-300">
                                            {{ __('No Charge') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-center tabular-nums pt-0.5
                                        {{ $isFullyRefunded ? 'line-through text-zinc-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                {{ (int) ($item->quantity ?? 0) }}
                            </div>
                            <div class="text-center font-mono tabular-nums pt-0.5
                                        {{ $isFullyRefunded ? 'line-through text-zinc-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                ₱{{ number_format((float) ($item->unit_price ?? 0), 2) }}
                            </div>
                            <div class="text-center font-semibold font-mono tabular-nums pt-0.5
                                        {{ $isFullyRefunded ? 'line-through text-zinc-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                ₱{{ number_format((float) ($item->total_price ?? 0), 2) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Totals --}}
    <div class="space-y-2">
        @if($amountReceived !== null || $changeAmount !== null)
            <div class="pt-2 space-y-1.5">
                @if($amountReceived !== null)
                    <div class="flex items-center justify-between gap-3 text-xs">
                        <span class="text-zinc-700 dark:text-zinc-300">{{ __('Amount Received') }}</span>
                        <span class="font-mono text-zinc-900 dark:text-zinc-100 tabular-nums">₱{{ number_format((float) $amountReceived, 2) }}</span>
                    </div>
                @endif
                @if($changeAmount !== null)
                    <div class="flex items-center justify-between gap-3 text-xs">
                        <span class="text-zinc-700 dark:text-zinc-300">{{ __('Amount Changed') }}</span>
                        <span class="font-mono text-zinc-900 dark:text-zinc-100 tabular-nums">₱{{ number_format((float) $changeAmount, 2) }}</span>
                    </div>
                @endif
            </div>
        @endif

        <div class="pt-2">
            <div class="flex items-end justify-between gap-3">
                <span class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                    <i class="fas fa-receipt mr-2"></i>
                    {{ __('Total Amount') }}
                </span>
                <span class="text-2xl font-black text-zinc-900 dark:text-zinc-100 font-mono tabular-nums leading-none">
                    ₱{{ number_format((float) $orderTotal, 2) }}
                </span>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    @if($showFooter)
        <div class="pt-4 text-center space-y-2.5">
            <p class="text-xs uppercase tracking-wider text-zinc-500">
                {{ __('Thank you! Please come again.') }}
            </p>
        </div>
    @endif

    {{-- Payment proof --}}
    @if($showProofSection && $isGcash)
        <div class="pt-3 border-t border-gray-200/80 dark:border-gray-700/80">
            <p class="text-xs uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500 mb-2">
                {{ __('Payment Proof') }}
            </p>
            @include('livewire.partials.orders.proof-of-payment', [
                'readOnly'          => true,
                'allowUploadInView' => in_array($paymentStatus, ['unpaid']),
                'existingProofUrl'  => $existingProofUrl,
                'compact'           => true,
            ])
        </div>
    @endif
</div>
