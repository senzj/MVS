<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
            <i class="fas fa-users mr-1"></i>{{ __('Customer') }}
        </label>

        <div x-data="{
                open: false,
                dropUp: false,
                toggle() {
                    this.open = !this.open;
                    if (this.open) this.$nextTick(() => this.reposition());
                },
                reposition() {
                    const t = this.$refs.trigger, p = this.$refs.panel;
                    if (!t || !p) return;
                    const rect = t.getBoundingClientRect();
                    const panelHeight = Math.min((p.scrollHeight || 0), 320);
                    const spaceBelow = window.innerHeight - rect.bottom;
                    const spaceAbove = rect.top;
                    this.dropUp = spaceBelow < panelHeight && spaceAbove > spaceBelow;
                }
            }"
            x-init="
                window.addEventListener('resize', () => open && reposition());
                window.addEventListener('scroll', () => open && reposition(), true);
            "
            class="relative">

            <button type="button"
                    x-ref="trigger"
                    @click="toggle()"
                    data-field="selectedCustomerId"
                    class="cursor-pointer w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 flex items-center justify-between transition">
                <span class="truncate">
                    @if($isCreatingNewCustomer)
                        <i class="fas fa-user-plus mr-2 text-green-500"></i>{{ __('Creating New Customer') }}
                    @else
                        {{ optional($this->selectedCustomer)->name ?? __('Select a customer') }}
                    @endif
                </span>
                <i class="fas fa-chevron-down ml-2 text-sm"></i>
            </button>

            <div x-show="open"
                x-ref="panel"
                @click.outside="open = false"
                @keydown.escape.window="open = false"
                x-cloak
                :class="dropUp ? 'bottom-full mb-1' : 'top-full mt-1'"
                class="absolute z-20 w-full bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-lg shadow-lg">

                <div class="sticky top-0 z-10 p-2 bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-600">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400"></i>
                        <input type="text"
                            wire:model.live.debounce.300ms="customerSearch"
                            placeholder="{{ __('Search customers...') }}"
                            class="w-full pl-9 pr-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <ul class="max-h-80 overflow-y-auto p-2">
                    <li class="mb-2 last:mb-0">
                        <button type="button" @click="$wire.createNewCustomer(); open = false;"
                            class="w-full text-left px-3 py-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition flex items-center gap-2">
                            <i class="fas fa-user-plus text-green-600"></i>
                            <span class="text-sm font-medium">{{ __('Create New Customer') }}</span>
                        </button>
                    </li>

                    @forelse($this->filteredCustomers as $customer)
                        <li class="mb-1 last:mb-0">
                            <button type="button" @click="$wire.selectCustomer({{ $customer->id }}); open = false;"
                                class="w-full text-left px-3 py-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition flex items-center gap-2">
                                <span class="truncate">{{ $customer->name }}</span>
                                <span class="ml-auto text-xs text-zinc-400">#{{ $customer->id }}</span>
                            </button>
                        </li>
                    @empty
                        <li class="text-xs text-zinc-500 p-3">{{ __('No customers found.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50/70 dark:bg-zinc-700/30 p-4">
        @if($selectedCustomerId || $isCreatingNewCustomer)
            @if($isCreatingNewCustomer)
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Create Customer') }}</h4>
                    <button type="button" wire:click="cancelNewCustomer"
                        class="text-xs font-semibold text-red-500 hover:text-red-600 transition">
                        {{ __('Cancel') }}
                    </button>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Name') }}</label>
                    <input type="text" wire:model="customerName"
                        data-field="customerName"
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Contact Number') }}</label>
                    <input type="tel" wire:model="customerContact" maxlength="11"
                        data-field="customerContact"
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Unit') }}</label>
                    <input type="text" wire:model="customerUnit"
                        data-field="customerUnit"
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">{{ __('Address') }}</label>
                    <input type="text" wire:model="customerAddress"
                        data-field="customerAddress"
                        class="w-full px-3 py-2.5 text-sm rounded-xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                </div>
            </div>
        @else
            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                <i class="fas fa-info-circle text-blue-500"></i>
                {{ __('No Customer Selected.') }}
            </p>
        @endif
    </div>
</div>
