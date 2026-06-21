{{--
    Refund Modal
    ==================
    Uses x-if (not x-show) to fully destroy DOM when closed — prevents lag.
    Sliders replace number inputs + progress bars.
    Restore stock toggle per item (default ON).
--}}

<div @open-refund.window="$wire.openRefund($event.detail.orderId)">

    <template x-if="$wire.show">
        <div
            x-data
            x-init="
                document.body.style.overflow = 'hidden';
                return () => { document.body.style.overflow = ''; }
            "
            class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
            wire:key="refund-modal-{{ $orderId }}"
        >

            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                @click="$wire.closeRefund()"
            ></div>

            {{-- Panel --}}
            <div
                class="relative bg-white dark:bg-zinc-800 rounded-t-2xl sm:rounded-2xl
                       w-full sm:max-w-lg max-h-[92dvh] flex flex-col overflow-hidden
                       shadow-xl"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                @keydown.escape.window="$wire.closeRefund()"
                @click.stop
            >

                {{-- Header --}}
                <div class="flex items-center justify-between gap-3 px-5 py-4
                            border-b border-zinc-200 dark:border-zinc-700 shrink-0">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <i class="fas fa-rotate-left text-purple-500 shrink-0"></i>
                        <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ __('Process refund') }}
                        </span>
                        @if($order)
                            <span class="font-mono text-xs text-zinc-400 bg-zinc-100 dark:bg-zinc-700
                                         px-2 py-0.5 rounded-md truncate">
                                {{ $order->receipt_number }}
                            </span>
                        @endif
                    </div>
                    <button type="button" wire:click="closeRefund"
                        class="cursor-pointer w-8 h-8 flex items-center justify-center rounded-full
                               text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200
                               hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors shrink-0">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3">

                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Slide to set how many units to return. Toggle off if an item is damaged and should not go back to stock.') }}
                    </p>

                    {{-- Global error --}}
                    @error('refundLines')
                        <div class="flex items-center gap-2 px-3 py-2.5 rounded-xl
                                    bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800
                                    text-xs text-red-700 dark:text-red-300">
                            <i class="fas fa-exclamation-circle shrink-0"></i>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror

                    {{-- Line items --}}
                    @foreach($refundLines as $index => $line)
                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700
                                    bg-white dark:bg-zinc-900/40 p-4
                                    {{ $line['returnable'] === 0 ? 'opacity-50' : '' }}">

                            {{-- Name + meta --}}
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">
                                        {{ $line['product_name'] }}
                                    </p>
                                    <div class="flex flex-wrap gap-x-3 gap-y-0.5 mt-0.5 text-xs text-zinc-400">
                                        <span>{{ __('Ordered') }}: {{ $line['ordered'] }}</span>
                                        @if($line['already_refunded'] > 0)
                                            <span class="text-purple-500">
                                                {{ __('Returned') }}: {{ $line['already_refunded'] }}
                                            </span>
                                        @endif
                                        <span>₱{{ number_format($line['unit_price'], 2) }}/{{ __('unit') }}</span>
                                    </div>
                                </div>

                                @if($line['returnable'] === 0)
                                    <span class="shrink-0 flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                        <i class="fas fa-check-circle"></i>
                                        {{ __('Fully returned') }}
                                    </span>
                                @endif
                            </div>

                            @if($line['returnable'] > 0)

                                {{-- Slider --}}
                                <div
                                    x-data="{
                                        qty: {{ (int)($line['refund_qty'] ?? 0) }},
                                        max: {{ (int)$line['returnable'] }},
                                        commit(v) {
                                            this.qty = v;
                                            $wire.set('refundLines.{{ $index }}.refund_qty', v);
                                        }
                                    }"
                                    class="mb-3"
                                >
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ __('Return quantity') }}
                                        </label>
                                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100"
                                              x-text="qty + ' / ' + max"></span>
                                    </div>

                                    <input
                                        type="range"
                                        min="0"
                                        :max="max"
                                        step="1"
                                        x-model.number="qty"
                                        @change="commit($event.target.valueAsNumber)"
                                        class="w-full accent-purple-600 cursor-pointer"
                                    >

                                    <div class="flex justify-between text-[10px] text-zinc-300 dark:text-zinc-600 mt-0.5 select-none">
                                        <span>0</span>
                                        <span>{{ $line['returnable'] }}</span>
                                    </div>
                                </div>

                                {{-- Restore stock toggle --}}
                                <div class="flex items-center justify-between gap-3 pt-3
                                            border-t border-zinc-100 dark:border-zinc-700"
                                    x-data="{ restore: @js($line['restore_stock']) }"
                                >
                                    <div>
                                        <p class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                            {{ __('Restore to inventory') }}
                                        </p>
                                        <p class="text-xs text-zinc-400 mt-0.5">
                                            {{ __('Turn off if damaged or unsellable.') }}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        role="switch"
                                        :aria-checked="restore.toString()"
                                        @click="
                                            restore = !restore;
                                            $wire.set('refundLines.{{ $index }}.restore_stock', restore)
                                        "
                                        class="relative w-10 h-6 rounded-full transition-colors duration-200 shrink-0 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
                                        :class="restore ? 'bg-green-500' : 'bg-zinc-300 dark:bg-zinc-600'"
                                    >
                                        <span
                                            class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow-sm transition-transform duration-200"
                                            :class="restore ? 'translate-x-4' : 'translate-x-0'"
                                        ></span>
                                    </button>
                                </div>

                                @error("refundLines.{$index}.refund_qty")
                                    <p class="mt-2 text-xs text-red-500 flex items-center gap-1">
                                        <i class="fas fa-exclamation-circle"></i>{{ $message }}
                                    </p>
                                @enderror

                            @endif
                        </div>
                    @endforeach

                    {{-- Inline summary (only when qty > 0) --}}
                    @if($this->totalRefundQty > 0)
                        <div class="flex items-center justify-between px-4 py-3 rounded-xl
                                    bg-purple-50 dark:bg-purple-900/20
                                    border border-purple-200 dark:border-purple-800">
                            <div class="flex items-center gap-2 text-xs text-purple-700 dark:text-purple-300">
                                <i class="fas fa-rotate-left shrink-0"></i>
                                <span>{{ __(':n unit(s) to return', ['n' => $this->totalRefundQty]) }}</span>
                                @php
                                    $anyNotRestoring = collect($refundLines)->some(
                                        fn($l) => (int)($l['refund_qty'] ?? 0) > 0 && !($l['restore_stock'] ?? true)
                                    );
                                @endphp

                                @if($anyNotRestoring)
                                    <span class="text-amber-600 dark:text-amber-400 flex items-center gap-1">
                                        · <i class="fas fa-exclamation-triangle"></i> {{ __('Partial restocked') }}
                                    </span>
                                @endif
                            </div>
                            <span class="text-sm font-semibold text-purple-800 dark:text-purple-200">
                                ₱{{ number_format($this->refundAmount, 2) }}
                            </span>
                        </div>
                    @endif

                </div>

                {{-- Footer --}}
                <div class="flex gap-3 px-5 py-4 bg-zinc-50 dark:bg-zinc-900
                            border-t border-zinc-200 dark:border-zinc-700 shrink-0">
                    <button type="button" wire:click="closeRefund"
                        class="cursor-pointer flex-1 px-4 py-2.5 text-sm font-medium rounded-xl
                               border border-zinc-300 dark:border-zinc-600
                               bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300
                               hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        {{ __('Cancel') }}
                    </button>

                    <button type="button"
                        wire:click="confirmRefund"
                        wire:loading.attr="disabled"
                        wire:target="confirmRefund"
                        @if($this->totalRefundQty === 0) disabled @endif
                        class="cursor-pointer flex-[2] px-5 py-2.5 text-sm font-semibold rounded-xl
                               bg-purple-600 text-white hover:bg-purple-700 active:scale-95
                               disabled:opacity-40 disabled:cursor-not-allowed
                               transition-all">
                        <span wire:loading.remove wire:target="confirmRefund" class="flex items-center justify-center gap-1.5">
                            <i class="fas fa-rotate-left"></i>{{ __('Confirm refund') }}
                        </span>
                        <span wire:loading wire:target="confirmRefund" class="flex items-center justify-center gap-2">
                            <i class="fas fa-spinner fa-spin"></i>{{ __('Processing…') }}
                        </span>
                    </button>
                </div>

            </div>
        </div>
    </template>

</div>
