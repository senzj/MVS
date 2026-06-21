{{--
    Cash Payment Input
    ==================
    Props:
        $total           – float  (required) order total
        $amountReceived  – float  (optional) initial value, defaults to $total
--}}

<div class="space-y-3"
    x-data="{
        amount: Number({{ (float)($amountReceived ?? $total ?? 0) }}),
        total:  Number({{ (float)($total ?? 0) }}),

        commit() {
            if (!this.amount || this.amount < 0) this.amount = 0;
            $wire.amountReceived = this.amount;
        },

        get change() {
            return Math.round((this.amount - this.total) * 100) / 100;
        }
    }"
    x-init="
        $watch(() => $wire.amountReceived, v => {
            if (v !== null && v !== undefined
                && !document.activeElement?.matches('[data-field=\'amountReceived\']')) {
                amount = parseFloat(v) || 0;
            }
        });
    ">

    {{-- Amount Received --}}
    <div>
        <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
            {{ __('Amount Received') }}
        </label>
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm font-medium pointer-events-none select-none">
                {{ config('storeconfig.currency_symbol') }}
            </span>
            <input type="number"
                   step="0.01" min="0"
                   x-model.number="amount"
                   @blur="commit()"
                   @keydown.enter.prevent="commit()"
                   @keydown.tab="commit()"
                   data-field="amountReceived"
                   class="w-full pl-8 pr-3 py-2.5 rounded-lg border border-zinc-200 dark:border-zinc-600
                          bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100
                          focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500
                          transition text-sm">
        </div>
        @error('amountReceived')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Quick-fill buttons --}}
    <div class="grid grid-cols-4 gap-2">
        @php
            $quickFills = [
                ['label' => __('Exact'),    'expr' => 'total',                    'color' => 'emerald'],
                ['label' => __('Round up'), 'expr' => 'Math.ceil(total)',          'color' => 'blue'],
                ['label' => __('Round 5s'), 'expr' => 'Math.ceil(total/5)*5',     'color' => 'sky'],
                ['label' => '+10',          'expr' => 'total + 10',               'color' => 'indigo'],
                ['label' => '+50',          'expr' => 'total + 50',               'color' => 'indigo'],
                ['label' => '+100',         'expr' => 'total + 100',              'color' => 'amber'],
                ['label' => '+500',         'expr' => 'total + 500',              'color' => 'orange'],
                ['label' => __('Clear'),    'expr' => '0',                        'color' => 'red'],
            ];

            $colorMap = [
                'emerald' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300 hover:bg-emerald-200 dark:hover:bg-emerald-800/40',
                'blue'    => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-800/40',
                'sky'     => 'bg-sky-100 text-sky-900 dark:bg-sky-900/30 dark:text-sky-300 hover:bg-sky-200 dark:hover:bg-sky-800/40',
                'indigo'  => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300 hover:bg-indigo-200 dark:hover:bg-indigo-800/40',
                'amber'   => 'bg-amber-100 text-amber-900 dark:bg-amber-900/30 dark:text-amber-300 hover:bg-amber-200 dark:hover:bg-amber-800/40',
                'orange'  => 'bg-orange-100 text-orange-900 dark:bg-orange-900/30 dark:text-orange-300 hover:bg-orange-200 dark:hover:bg-orange-800/40',
                'red'     => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-800/40',
            ];
        @endphp

        @foreach($quickFills as $btn)
            <button type="button"
                class="px-2 py-3 rounded-lg text-sm font-semibold transition {{ $colorMap[$btn['color']] }}"
                @click="amount = {{ $btn['expr'] }}; commit()">
                {{ $btn['label'] }}
            </button>
        @endforeach
    </div>

    {{-- Change / Amount Short --}}
    <div class="flex items-center justify-between rounded-lg px-3 py-2.5"
        :class="change >= 0 ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-red-50 dark:bg-red-900/20'">
        <p class="text-sm text-zinc-500 dark:text-zinc-400"
            x-text="change < 0 ? '{{ __('Amount Short') }}' : '{{ __('Change') }}'">
        </p>
        <p class="font-mono text-base font-semibold"
            :class="change >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
            <span x-text="(change < 0 ? '-' : '') + '{{ config('storeconfig.currency_symbol') }}' + Math.abs(change).toFixed(2)">
                {{ config('storeconfig.currency_symbol') }}0.00
            </span>
        </p>
    </div>
</div>
