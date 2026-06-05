<div
    x-data="{
        get show() { return $wire.show }
    }"
    x-effect="document.body.style.overflow = show ? 'hidden' : ''"
    x-on:keydown.escape.window="$wire.close()"
>

    {{-- Payment-scoped loading overlay — listens for browser events from the Payment component --}}
    <div
        x-data="{ processing: false }"
        x-on:payment-processing-start.window="processing = true"
        x-on:payment-processing-done.window="processing = false"
        x-show="processing"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[200] flex items-center justify-center bg-black/40"
        style="display: none;">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl p-8 flex flex-col items-center gap-4 max-w-sm mx-4">
            <div class="relative w-12 h-12 flex items-center justify-center">
                <div class="absolute inset-0 rounded-full border-4 border-blue-200 dark:border-blue-900"></div>
                <div class="absolute inset-0 rounded-full border-4 border-transparent border-t-blue-600 dark:border-t-blue-400 animate-spin"></div>
            </div>
            <div class="text-center space-y-1">
                <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Processing') }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Please wait while we process your request') }}...</p>
            </div>
        </div>
    </div>

    {{-- modal --}}
    <template x-if="show">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="relative w-full max-w-md p-4"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2">

                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg p-5 space-y-4">

                    {{-- Header --}}
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">
                            {{ __('Confirm Payment') }}
                        </h3>
                        <button type="button"
                                x-on:click="$wire.close()"
                                class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    @if($order)
                        {{-- Order info --}}
                        <div class="flex items-baseline justify-between gap-3 pb-3 border-b border-zinc-100 dark:border-zinc-700">
                            <div>
                                <p class="text-xs text-zinc-500">{{ __('Order') }}</p>
                                <p class="font-semibold text-sm text-zinc-700 dark:text-zinc-200">
                                    {{ $order->receipt_number }}
                                </p>
                                @if($order->customer)
                                    <p class="text-xs text-zinc-400">{{ $order->customer->name }}</p>
                                @endif
                                <p class="text-xs text-zinc-400 mt-1">
                                    <span class="inline-block px-2 py-1 rounded text-white text-xs font-medium"
                                          :class="'{{ $order->payment_type }}' === 'cash' ? 'bg-green-600' : 'bg-blue-600'">
                                        {{ ucfirst($order->payment_type) }}
                                    </span>
                                </p>
                            </div>

                            {{-- Payment due info --}}
                            <div class="text-right">
                                {{-- Discount --}}
                                @php
                                    $subtotal    = 0;
                                    $discountSum = 0;

                                    foreach ($order->orderItems as $item) {
                                        $itemTotal = $item->quantity * $item->unit_price;
                                        $subtotal += $itemTotal;

                                        if ($order->discount_type === 'percentage') {
                                            $discountSum += $itemTotal * ($order->discount_value / 100);
                                        } elseif ($order->discount_type === 'fixed') {
                                            // will be set cleanly after the loop
                                        }
                                    }

                                    // Fixed discount is a flat amount, not distributed per-item
                                    if ($order->discount_type === 'fixed') {
                                        $discountSum = (float) $order->discount_value;
                                    }

                                    // Build the label: "PWD - 15%" or "PWD - ₱50.00"
                                    $preset      = $order->discountPreset;
                                    $presetLabel = null;
                                    if ($preset) {
                                        $valueStr    = $order->discount_type === 'percentage'
                                            ? number_format($order->discount_value, 0) . '%'
                                            : '₱' . number_format($order->discount_value, 2);
                                        $presetLabel = $preset->name . ' · ' . $valueStr;
                                    }
                                @endphp

                                @if ($discountSum > 0)
                                    <p class="text-xs text-zinc-500">
                                        {{ __('Discount') }}
                                        @if ($presetLabel)
                                            <span class="ml-1 font-medium text-zinc-600 dark:text-zinc-300">({{ $presetLabel }})</span>
                                        @endif
                                    </p>
                                    <p class="text-sm font-medium text-red-600 dark:text-red-400">
                                        -₱{{ number_format($discountSum, 2) }}
                                    </p>
                                @endif

                                {{-- Total due --}}
                                <p class="text-xs text-zinc-500 mt-1">{{ __('Total due') }}</p>
                                <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                                    ₱{{ number_format($order->order_total, 2) }}
                                </p>
                            </div>
                        </div>

                        {{-- Amount received & Change —  Alpine local buffer prevents interrupting typing --}}
                        <div class="space-y-3"
                             x-data="{
                                amount: Number(@js($amountReceived ?? $order->order_total)),
                                total: Number(@js($order->order_total ?? 0)),
                                commit() {
                                    if (!this.amount || this.amount < 0) this.amount = 0;
                                    $wire.amountReceived = this.amount;
                                },
                                get change() {
                                    return Math.max(0, this.amount - this.total);
                                }
                             }"
                             x-init="
                                $watch(() => $wire.amountReceived, v => {
                                    if (v !== null && v !== undefined && !document.activeElement?.matches('input[data-field=\"amountReceived\"]')) {
                                        amount = parseFloat(v) || 0;
                                    }
                                });
                             ">
                            <div>
                                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                                    {{ __('Amount Received') }}
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm font-medium">₱</span>
                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           x-model.number="amount"
                                           @blur="commit()"
                                           @keydown.enter.prevent="commit()"
                                           @keydown.tab="commit()"
                                           data-field="amountReceived"
                                           class="w-full pl-7 pr-3 py-2.5 rounded-lg border border-zinc-200 dark:border-zinc-600
                                                  bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100
                                                  focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition text-sm">
                                </div>
                                @error('amountReceived')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Quick Fill Buttons --}}
                            <div class="my-4 grid grid-cols-3 sm:grid-cols-4 gap-2">

                                {{-- EXACT --}}
                                <button type="button"
                                    class="px-3 py-4 rounded-lg text-sm font-semibold
                                        bg-emerald-100 text-emerald-800
                                        dark:bg-emerald-900/30 dark:text-emerald-300
                                        hover:bg-emerald-200 dark:hover:bg-emerald-800/40 transition"
                                    @click="amount = total">
                                    Exact
                                </button>

                                {{-- ROUND UP --}}
                                <button type="button"
                                    class="px-3 py-4 rounded-lg text-sm font-semibold
                                        bg-blue-100 text-blue-800
                                        dark:bg-blue-900/30 dark:text-blue-300
                                        hover:bg-blue-200 dark:hover:bg-blue-800/40 transition"
                                    @click="amount = Math.ceil(total)">
                                    Round Up
                                </button>

                                {{-- +10 --}}
                                <button type="button"
                                    class="px-3 py-4 rounded-lg text-sm font-semibold
                                        bg-indigo-100 text-indigo-800
                                        dark:bg-indigo-900/30 dark:text-indigo-300
                                        hover:bg-indigo-200 dark:hover:bg-indigo-800/40 transition"
                                    @click="amount = total + 10">
                                    +10
                                </button>

                                {{-- +50 --}}
                                <button type="button"
                                    class="px-3 py-4 rounded-lg text-sm font-semibold
                                        bg-indigo-100 text-indigo-800
                                        dark:bg-indigo-900/30 dark:text-indigo-300
                                        hover:bg-indigo-200 dark:hover:bg-indigo-800/40 transition"
                                    @click="amount = total + 50">
                                    +50
                                </button>

                                {{-- +100 --}}
                                <button type="button"
                                    class="px-3 py-4 rounded-lg text-sm font-semibold
                                        bg-amber-100 text-amber-900
                                        dark:bg-amber-900/30 dark:text-amber-300
                                        hover:bg-amber-200 dark:hover:bg-amber-800/40 transition"
                                    @click="amount = total + 100">
                                    +100
                                </button>

                                {{-- +500 --}}
                                <button type="button"
                                    class="px-3 py-4 rounded-lg text-sm font-semibold
                                        bg-orange-100 text-orange-900
                                        dark:bg-orange-900/30 dark:text-orange-300
                                        hover:bg-orange-200 dark:hover:bg-orange-800/40 transition"
                                    @click="amount = total + 500">
                                    +500
                                </button>

                                {{-- CUSTOM ROUND (nearest 5) --}}
                                <button type="button"
                                    class="px-3 py-4 rounded-lg text-sm font-semibold
                                        bg-sky-100 text-sky-900
                                        dark:bg-sky-900/30 dark:text-sky-300
                                        hover:bg-sky-200 dark:hover:bg-sky-800/40 transition"
                                    @click="amount = Math.ceil(total / 5) * 5">
                                    Round 5s
                                </button>

                                {{-- CLEAR --}}
                                <button type="button"
                                    class="px-3 py-4 rounded-lg text-sm font-semibold
                                        bg-red-100 text-red-800
                                        dark:bg-red-900/30 dark:text-red-300
                                        hover:bg-red-200 dark:hover:bg-red-800/40 transition"
                                    @click="amount = 0">
                                    Clear
                                </button>

                            </div>

                            {{-- Change — computed client-side for instant feedback --}}
                            <div
                                :class="change >= 0
                                    ? 'bg-emerald-50 dark:bg-emerald-900/20'
                                    : 'bg-red-50 dark:bg-red-900/20'"
                                class="flex items-center justify-between rounded-lg px-3 py-2"
                            >
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Change') }}</p>
                                <p :class="change >= 0
                                    ? 'text-emerald-700 dark:text-emerald-400'
                                    : 'text-red-600 dark:text-red-400'"
                                    class="font-mono text-base font-semibold">
                                    ₱<span x-text="change.toFixed(2)">0.00</span>
                                </p>
                            </div>
                        </div>

                        {{-- Proof of Payment --}}
                        @if($order->payment_type !== 'cash')
                            <div>
                                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">
                                    {{ __('Proof of Payment') }}
                                    <span class="font-normal normal-case text-zinc-400 ml-1">({{ __('optional') }})</span>
                                </label>
                                @include('livewire.partials.orders.proof-of-payment', [
                                    'existingProofUrl'  => $order->proof_url ?? null,
                                    'paymentType'       => $order->payment_type,
                                    'allowCamera'       => true,
                                    'readOnly'          => false,
                                    'compact'           => true,
                                    'allowUploadInView' => true,
                                ])
                            </div>
                        @endif

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 justify-end pt-1">
                            <button type="button"
                                    x-on:click="$wire.close()"
                                    wire:loading.attr="disabled"
                                    wire:target="confirmPayment"
                                    class="px-4 py-2 rounded-lg bg-zinc-100 dark:bg-zinc-700 text-sm font-medium
                                           text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-600
                                           disabled:opacity-50 transition">
                                {{ __('Cancel') }}
                            </button>

                            <button type="button"
                                    wire:click="confirmPayment"
                                    wire:loading.attr="disabled"
                                    wire:target="confirmPayment,proofOfPayment"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg
                                           bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold
                                           disabled:opacity-60 disabled:cursor-not-allowed transition">
                                <svg wire:loading wire:target="confirmPayment"
                                     class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="3" class="opacity-25"></circle>
                                    <path fill="currentColor"
                                          d="M12 2a10 10 0 0 1 10 10h-3a7 7 0 0 0-7-7V2z"></path>
                                </svg>
                                <i wire:loading.remove wire:target="confirmPayment"
                                   class="fas fa-check text-xs"></i>
                                <span>{{ __('Confirm & Save') }}</span>
                            </button>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </template>
</div>
