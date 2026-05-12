@section('title', __('Edit Orders'))

<div class="w-full max-w-full overflow-x-hidden px-2 sm:px-4 pb-8">

    {{-- HEADER --}}
    <div class="flex items-start justify-between gap-3 py-2 mb-5">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <i class="fas fa-file-pen text-blue-500"></i>
                {{ __('Edit Order') }}
            </h2>
            @include('livewire.partials.clock')
        </div>
        <button wire:click="cancel"
            class="cursor-pointer inline-flex items-center gap-2 px-3 py-2 rounded-lg
                   bg-gray-200 text-gray-800 hover:bg-gray-300 transition
                   dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 text-sm font-medium">
            <i class="fas fa-arrow-left"></i>
            <span class="hidden sm:inline">{{ __('Back') }}</span>
        </button>
    </div>

    <div class="grid grid-cols-1 gap-5">

        {{-- Order meta (read-only) --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm p-5">
            <h3 class="text-sm font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">
                <i class="fas fa-file-invoice text-blue-500 mr-2"></i>{{ __('Order Information') }}
            </h3>
            @php $loc = app()->getLocale() === 'cn' ? 'zh_CN' : app()->getLocale(); @endphp
            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                <div class="flex flex-col gap-0.5">
                    <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Order ID') }}</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">#{{ $order->id }}</dd>
                </div>
                <div class="flex flex-col gap-0.5">
                    <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Receipt') }}</dt>
                    <dd class="font-mono text-zinc-900 dark:text-zinc-100">{{ $order->receipt_number }}</dd>
                </div>
                <div class="flex flex-col gap-0.5">
                    <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Date') }}</dt>
                    <dd class="text-zinc-900 dark:text-zinc-100">
                        {{ $order->created_at->locale($loc)->isoFormat('MMM D, YYYY · hh:mm A') }}
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Status & Payment --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm p-5 space-y-4">
            <h3 class="text-sm font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                <i class="fas fa-sliders text-blue-500 mr-2"></i>{{ __('Order Settings') }}
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                {{-- Order Status --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                        {{ __('Status') }} <span class="text-red-500 normal-case font-normal">*</span>
                    </label>
                    <select wire:model="status"
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                               bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="preparing">{{ __('Preparing') }}</option>
                        <option value="in_transit">{{ __('In Transit') }}</option>
                        <option value="delivered">{{ __('Delivered') }}</option>
                        <option value="completed">{{ __('Completed') }}</option>
                        <option value="cancelled">{{ __('Cancelled') }}</option>
                    </select>
                </div>

                {{-- Order Type --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                        {{ __('Order Type') }} <span class="text-red-500 normal-case font-normal">*</span>
                    </label>
                    <select wire:model.defer="order_type"
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                               bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                        <option value="deliver">{{ __('Delivery') }}</option>
                        <option value="walk_in">{{ __('Walk-In') }}</option>
                    </select>
                </div>

                {{-- Payment Method --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                        {{ __('Payment Method') }} <span class="text-red-500 normal-case font-normal">*</span>
                    </label>
                    <select wire:model.defer="payment_type"
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                               bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                        <option value="cash">{{ __('Cash') }}</option>
                        <option value="gcash">{{ __('GCash / Online') }}</option>
                    </select>
                </div>

                {{-- Payment Status (replaces is_paid toggle) --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                        {{ __('Payment Status') }} <span class="text-red-500 normal-case font-normal">*</span>
                    </label>
                    <select wire:model="payment_status"
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600
                               bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                        <option value="unpaid">{{ __('Unpaid') }}</option>
                        <option value="paid">{{ __('Paid') }}</option>
                        <option value="refunded">{{ __('Refunded') }}</option>
                    </select>
                    {{-- Current badge for quick reference --}}
                    <div class="mt-1.5">
                        @include('livewire.partials.orders.status.payment-badge', [
                            'status' => $payment_status,
                        ])
                    </div>
                </div>

                {{-- Delivery Person --}}
                @if($order_type === 'deliver')
                    <div class="col-span-1 sm:col-span-2 lg:col-span-4">
                        <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-2">
                            <i class="fas fa-user-tie mr-1"></i>{{ __('Delivery Person') }}
                            <span class="text-red-500 normal-case font-normal">*</span>
                        </label>
                        @include('livewire.partials.orders.form.employee.dropdown', ['forceSelect' => false])
                    </div>
                @endif
            </div>
        </div>

        {{-- Customer --}}
        @if($order_type === 'deliver')
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm p-5">
                <h3 class="text-sm font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-4">
                    <i class="fas fa-user text-blue-500 mr-2"></i>{{ __('Customer Information') }}
                </h3>
                @include('livewire.partials.orders.form.customer')
            </div>
        @endif

        {{-- Order Items --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h3 class="text-sm font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                    <i class="fas fa-shopping-cart text-blue-500 mr-2"></i>{{ __('Order Items') }}
                </h3>
                <button type="button" wire:click="addOrderItem"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-600 text-white
                           text-sm font-semibold hover:bg-blue-700 active:scale-95 transition-all shadow-sm">
                    <i class="fas fa-plus"></i>{{ __('Add Item') }}
                </button>
            </div>

            @if($orderItems)
                <div class="space-y-3">
                    @foreach($orderItems as $index => $item)
                        @php
                            // Collect product IDs from all other items to exclude from this item's dropdown
                            $excludeIds = [];
                            foreach ($orderItems as $otherIndex => $otherItem) {
                                if ($otherIndex !== $index && !empty($otherItem['product_id'])) {
                                    $excludeIds[] = $otherItem['product_id'];
                                }
                            }
                        @endphp
                        @include('livewire.partials.orders.form.itemrow', [
                            'index' => $index,
                            'item'  => $item,
                            'count' => count($orderItems),
                            'excludeProductIds' => $excludeIds,
                        ])
                    @endforeach
                </div>

                {{--
                    Proof of payment:
                    Show for every GCash order.
                    Walk-in orders can take a photo; delivery orders upload only.
                --}}
                @if($payment_type === 'gcash')
                    <div class="pt-2">
                        @if($this->showQr)
                            {{-- QR code for unpaid walk-in gcash --}}
                            @php $qrImage = \App\Helpers\PaymentImageHelper::getPaymentImageUrl(); @endphp
                            @if($qrImage)
                                <div class="mb-3 flex flex-col items-center gap-2 p-4
                                            rounded-xl border border-zinc-200 dark:border-zinc-700
                                            bg-zinc-50 dark:bg-zinc-900/40">
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Scan to pay') }}</p>
                                    <img src="{{ $qrImage }}" alt="{{ __('GCash QR') }}"
                                        class="max-w-[160px] max-h-[160px] object-contain rounded-lg">
                                </div>
                            @endif
                        @endif

                        @include('livewire.partials.orders.proof-of-payment', [
                            'existingProofUrl' => $existingProof ? asset('storage/' . $existingProof) : null,
                            'allowCamera'      => $order_type === 'walk_in',
                            'readOnly'         => false,
                        ])
                    </div>
                @endif

                <div class="pt-4 border-t border-zinc-200 dark:border-zinc-600 flex justify-between items-center">
                    <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wide">
                        {{ __('Total Amount') }}
                    </span>
                    <span class="text-2xl font-black font-mono text-zinc-900 dark:text-zinc-100">
                        ₱{{ number_format($this->editedTotal, 2) }}
                    </span>
                </div>
            @endif
        </div>

        {{-- Action buttons --}}
        <div class="flex gap-3">
            <button type="button" wire:click="cancel"
                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl
                       bg-zinc-100 dark:bg-zinc-700 text-sm font-semibold
                       text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition">
                <i class="fas fa-times"></i>{{ __('Cancel') }}
            </button>

            <button type="button" wire:click="openSaveConfirmation"
                wire:loading.attr="disabled"
                wire:target="openSaveConfirmation"
                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl
                       bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700
                       active:scale-95 transition-all shadow-md shadow-blue-500/20 disabled:opacity-60">
                <span wire:loading.remove wire:target="openSaveConfirmation">
                    <i class="fas fa-save mr-1"></i>{{ __('Save Changes') }}
                </span>
                <span wire:loading wire:target="openSaveConfirmation" class="flex items-center gap-2">
                    <i class="fas fa-spinner fa-spin"></i>{{ __('Saving') }}
                </span>
            </button>
        </div>
    </div>

    {{-- Universal confirm modal --}}
    @include('livewire.partials.orders.modal.order', [
        'modalMode'   => 'confirm',
        'confirmData' => $confirmData,
    ])

    {{-- Loading overlay --}}
    @include('livewire.partials.loading-overlay', [
        'wireTarget' => 'save,selectProduct,addOrderItem,removeOrderItem,openSaveConfirmation,deleteExistingProof',
    ])
</div>
