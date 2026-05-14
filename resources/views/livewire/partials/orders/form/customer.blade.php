{{--
    Customer Dropdown + Inline Form

    Used in: Create Order, Edit Order, Record Sales
--}}

<div
    id="customer-section"
    x-data="{
        hasError: false,
        isDelivery: '{{ ($order_type ?? 'walk_in') === 'deliver' ? 'true' : 'false' }}' === 'true',
        onError() {
            this.hasError = true;
            this.$nextTick(() => {
                const el = document.getElementById('customer-section');
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            // Auto-clear after 4 s so the highlight doesn't linger forever
            setTimeout(() => { this.hasError = false; }, 4000);
        }
    }"
    @customer-validation-error.window="onError()"
    @customer-validation-clear.window="hasError = false"
    class="space-y-4"
    :class="(hasError && isDelivery) ? 'ring-2 ring-red-400/60 rounded-2xl p-1' : ''">

    {{-- Customer picker --}}
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
            <i class="fas fa-users mr-1"></i>
            {{ __('Customer') }}
            @if(($order_type ?? 'walk_in') === 'deliver')
                <span class="text-red-500">*</span>
            @else
                <span class="text-zinc-400 normal-case font-normal">({{ __('optional') }})</span>
            @endif
        </label>

        <div x-data="{
                open: false,
                dropUp: false,
                toggle() { this.open = !this.open; if (this.open) this.$nextTick(() => this.reposition()); },
                reposition() {
                    const t = this.$refs.trigger, p = this.$refs.panel;
                    if (!t || !p) return;
                    const rect        = t.getBoundingClientRect();
                    const panelHeight = Math.min((p.scrollHeight || 0), 320);
                    this.dropUp = (window.innerHeight - rect.bottom) < panelHeight && rect.top > (window.innerHeight - rect.bottom);
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
                class="cursor-pointer w-full px-3 py-2 border rounded-lg
                       flex items-center justify-between transition
                       bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100"
                :class="hasError && !{{ $selectedCustomerId ?? 0 }} && !{{ $isCreatingNewCustomer ? 'true' : 'false' }} && '{{ ($order_type ?? 'walk_in') === 'deliver' ? 'true' : 'false' }}' === 'true'
                    ? 'border-red-400 dark:border-red-500'
                    : 'border-zinc-200 dark:border-zinc-600'">
                <span class="truncate text-sm">
                    @if($isCreatingNewCustomer)
                        <i class="fas fa-user-plus mr-2 text-green-500"></i>{{ __('Creating New Customer') }}
                    @else
                        {{ optional($this->selectedCustomer)->name ?? __('Select a customer') }}
                    @endif
                </span>
                <i class="fas fa-chevron-down ml-2 text-xs text-zinc-400"></i>
            </button>

            <div x-show="open"
                x-ref="panel"
                @click.outside="open = false"
                @keydown.escape.window="open = false"
                x-cloak
                :class="dropUp ? 'bottom-full mb-1' : 'top-full mt-1'"
                class="absolute z-30 w-full bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-lg shadow-xl">

                <div class="sticky top-0 z-10 p-2 bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-600">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-xs"></i>
                        <input type="text"
                            wire:model.live.debounce.300ms="customerSearch"
                            placeholder="{{ __('Search customers') }}"
                            class="w-full pl-8 pr-3 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-600
                                   bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100
                                   focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <ul class="max-h-72 overflow-y-auto p-1.5 space-y-0.5">
                    {{-- Create new --}}
                    <li>
                        <button type="button"
                            @click="$wire.createNewCustomer(); open = false;"
                            class="w-full text-left px-3 py-2 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20
                                   transition flex items-center gap-2 text-sm">
                            <i class="fas fa-user-plus text-green-600 dark:text-green-400"></i>
                            <span class="font-medium text-green-700 dark:text-green-400">{{ __('Create New Customer') }}</span>
                        </button>
                    </li>

                    <li class="border-t border-zinc-100 dark:border-zinc-700 my-1"></li>

                    @forelse($this->filteredCustomers as $customer)
                        <li>
                            <button type="button"
                                @click="$wire.selectCustomer({{ $customer->id }}); open = false;"
                                class="w-full text-left px-3 py-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700
                                       transition flex items-center gap-2 text-sm">
                                <i class="fas fa-user text-zinc-400 shrink-0"></i>
                                <span class="truncate text-zinc-900 dark:text-zinc-100">{{ $customer->name }}</span>
                                <span class="ml-auto text-xs text-zinc-400 shrink-0">#{{ $customer->id }}</span>
                            </button>
                        </li>
                    @empty
                        <li class="px-3 py-6 text-center text-zinc-500 dark:text-zinc-400 text-sm">
                            <i class="fas fa-user-slash block text-2xl mb-2 opacity-40"></i>
                            {{ __('No customers found.') }}
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    {{-- Customer fields panel --}}
    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50/70 dark:bg-zinc-700/30 p-4">

        @if($selectedCustomerId || $isCreatingNewCustomer)

            @if($isCreatingNewCustomer)
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                        <i class="fas fa-user-plus mr-1 text-green-500"></i>{{ __('New Customer') }}
                    </h4>
                    <button type="button" wire:click="cancelNewCustomer"
                        class="text-xs font-semibold text-red-500 hover:text-red-600 transition">
                        {{ __('Cancel') }}
                    </button>
                </div>
            @endif

            @php
                $fieldClass = "w-full px-3 py-2.5 text-sm rounded-xl border bg-white dark:bg-zinc-800
                               text-zinc-900 dark:text-zinc-100
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition";
                $errorFieldClass = "border-red-400 dark:border-red-500";
                $normalFieldClass = "border-zinc-200 dark:border-zinc-600";
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

                {{-- Name --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                        {{ __('Name') }}
                        @if(($order_type ?? 'walk_in') === 'deliver')
                            <span class="text-red-500 normal-case font-normal">*</span>
                        @endif
                    </label>
                    <input type="text"
                        wire:model.live="customerName"
                        data-field="customerName"
                        class="{{ $fieldClass }}"
                        :class="hasError && !$wire.customerName?.trim() && '{{ ($order_type ?? 'walk_in') === 'deliver' ? 'true' : 'false' }}' === 'true' ? '{{ $errorFieldClass }}' : '{{ $normalFieldClass }}'">
                    @error('customerName')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Contact --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                        {{ __('Contact Number') }} <span class="text-zinc-400 normal-case font-normal">({{ __('optional') }})</span>
                    </label>
                    <input type="tel"
                        wire:model.live="customerContact"
                        data-field="customerContact"
                        maxlength="11"
                        class="{{ $fieldClass }} {{ $normalFieldClass }}">
                    @error('customerContact')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Unit --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                        {{ __('Unit') }}
                        @if(($order_type ?? 'walk_in') === 'deliver')
                            <span class="text-red-500 normal-case font-normal">*</span>
                        @endif
                    </label>
                    <input type="text"
                        wire:model.live="customerUnit"
                        data-field="customerUnit"
                        class="{{ $fieldClass }}"
                        :class="hasError && !$wire.customerUnit?.trim() && '{{ ($order_type ?? 'walk_in') === 'deliver' ? 'true' : 'false' }}' === 'true' ? '{{ $errorFieldClass }}' : '{{ $normalFieldClass }}'">
                    @error('customerUnit')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Address --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
                        {{ __('Address') }}
                        @if(($order_type ?? 'walk_in') === 'deliver')
                            <span class="text-red-500 normal-case font-normal">*</span>
                        @endif
                    </label>
                    <input type="text"
                        wire:model.live="customerAddress"
                        data-field="customerAddress"
                        class="{{ $fieldClass }}"
                        :class="hasError && !$wire.customerAddress?.trim() && '{{ ($order_type ?? 'walk_in') === 'deliver' ? 'true' : 'false' }}' === 'true' ? '{{ $errorFieldClass }}' : '{{ $normalFieldClass }}'">
                    @error('customerAddress')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Validation banner --}}
            <div x-show="hasError"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="mt-3 flex items-center gap-2 px-3 py-2 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-xs text-red-700 dark:text-red-300">
                <i class="fas fa-exclamation-circle shrink-0"></i>
                @if(($order_type ?? 'walk_in') === 'deliver')
                    {{ __('Please select or create a customer and fill in all delivery details before saving.') }}
                @else
                    {{ __('Please fill in all required customer fields before saving.') }}
                @endif
            </div>

        @else
            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                <i class="fas fa-info-circle text-blue-400"></i>
                @if(($order_type ?? 'walk_in') === 'deliver')
                    {{ __('Please select or create a customer for delivery.') }}
                @else
                    {{ __('No customer selected. Choose one above or create a new one.') }}
                @endif
            </div>
        @endif
    </div>
</div>
