{{--
    Delivery Person Dropdown

    Props:
        $forceSelect  – bool: show confirm dialog when employee is in-transit (default true)
                        true  → Create / Add-record pages
                        false → Edit page (simpler list, no confirm dialog)

        $fieldName    – data-field value (default 'selectedEmployeeId')
--}}

@php
    $forceSelect     = $forceSelect ?? true;
    $fieldName       = $fieldName   ?? 'selectedEmployeeId';
    $resolvedEmployee = $this->selectedEmployee ?? $selectedEmployee ?? null;
@endphp

<div x-data="{
        open: false,
        dropUp: false,
        toggle() { this.open = !this.open; if (this.open) this.$nextTick(() => this.reposition()); },
        reposition() {
            const t = this.$refs.trigger, p = this.$refs.panel;
            if (!t || !p) return;
            const rect        = t.getBoundingClientRect();
            const panelHeight = Math.min((p.scrollHeight || 0), 320);
            const spaceBelow  = window.innerHeight - rect.bottom;
            const spaceAbove  = rect.top;
            this.dropUp = spaceBelow < panelHeight && spaceAbove > spaceBelow;
        }
    }"
    x-init="
        window.addEventListener('resize', () => open && reposition());
        window.addEventListener('scroll', () => open && reposition(), true);
    "
    class="relative">

    {{-- Trigger --}}
    <button type="button"
        x-ref="trigger"
        @click="toggle()"
        data-field="{{ $fieldName }}"
        class="cursor-pointer w-full px-3 py-2 border border-zinc-200 dark:border-zinc-600 rounded-lg
               bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100
               flex items-center justify-between transition text-sm">
        <span class="truncate">
            {{ $resolvedEmployee->name ?? __('Select delivery person') }}
        </span>
        <i class="fas fa-chevron-down ml-2 text-xs text-zinc-400"></i>
    </button>

    {{-- Panel --}}
    <div x-show="open"
         x-ref="panel"
         @click.outside="open = false"
         @keydown.escape.window="open = false"
         x-cloak
         :class="dropUp ? 'bottom-full mb-1' : 'top-full mt-1'"
         class="absolute z-30 w-full bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-lg shadow-xl">

        {{-- Search --}}
        <div class="sticky top-0 z-10 p-2 bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-600">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-xs"></i>
                <input type="text"
                    wire:model.live.debounce.300ms="employeeSearch"
                    placeholder="{{ __('Search delivery person...') }}"
                    class="w-full pl-8 pr-3 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-600
                           bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        {{-- List --}}
        <ul class="max-h-80 overflow-y-auto p-1.5 space-y-0.5">
            @forelse(($this->filteredEmployees ?? $employees ?? []) as $employee)
                @php $isInTransit = $this->isEmployeeInTransit($employee->id); @endphp
                <li>
                    @if($forceSelect)
                        {{-- Create / Add-record: confirm before assigning in-transit person --}}
                        <div x-data="{
                                inTransit: {{ $isInTransit ? 'true' : 'false' }},
                                employeeId: {{ $employee->id }},
                                employeeName: @js($employee->name)
                            }"
                            @click="
                                if (inTransit) {
                                    const msg = @js(__('Delivery Person :name is currently delivering. Assign anyway?'))
                                                .replace(':name', employeeName);
                                    if (confirm(msg)) { $wire.forceSelectEmployee(employeeId); open = false; }
                                } else {
                                    $wire.selectEmployee(employeeId);
                                    open = false;
                                }
                            "
                            class="px-3 py-2.5 rounded-lg cursor-pointer transition flex items-center justify-between gap-2
                                   {{ $isInTransit
                                       ? 'bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 hover:bg-orange-100 dark:hover:bg-orange-900/30'
                                       : 'hover:bg-zinc-50 dark:hover:bg-zinc-700' }}">
                            <span class="font-medium text-sm text-zinc-900 dark:text-zinc-100 truncate">
                                <i class="fas fa-user-tie mr-1 text-zinc-400"></i>{{ $employee->name }}
                            </span>
                            <div class="flex items-center gap-2 shrink-0">
                                @if($isInTransit)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300 dark:text-indigo-200">
                                        <i class="fas fa-shipping-fast mr-1"></i>{{ __('In transit') }}
                                    </span>
                                @endif
                                <span class="text-xs text-zinc-400">#{{ $employee->id }}</span>
                            </div>
                        </div>
                    @else
                        {{-- Edit: simple click, no confirm --}}
                        <button type="button"
                            @click="$wire.selectEmployee({{ $employee->id }}); open = false;"
                            class="w-full text-left px-3 py-2.5 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition flex items-center gap-2 text-sm">
                            <i class="fas fa-user-tie mr-1 text-zinc-400 shrink-0"></i>
                            <span class="truncate text-zinc-900 dark:text-zinc-100">{{ $employee->name }}</span>
                            @if($isInTransit)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300 shrink-0">
                                    <i class="fas fa-shipping-fast mr-1"></i>{{ __('In transit') }}
                                </span>
                            @endif
                            <span class="ml-auto text-xs text-zinc-400 shrink-0">#{{ $employee->id }}</span>
                        </button>
                    @endif
                </li>
            @empty
                <li class="px-3 py-8 text-center text-zinc-500 dark:text-zinc-400">
                    <i class="fas fa-user-slash text-2xl block mb-2 opacity-40"></i>
                    <span class="text-sm">{{ __('No delivery personnel found.') }}</span>
                </li>
            @endforelse
        </ul>
    </div>
</div>

{{-- Selected badge --}}
@if($resolvedEmployee)
    @php $selectedIsInTransit = $this->isEmployeeInTransit($resolvedEmployee->id); @endphp
    <p class="text-sm mt-2 flex items-center gap-2 flex-wrap">
        <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
        <span class="text-green-700 dark:text-green-400 font-medium">
            {{ __('Selected') }}: {{ $resolvedEmployee->name }}
        </span>
        @if($selectedIsInTransit)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300 shrink-0">
                <i class="fas fa-shipping-fast mr-1"></i>{{ __('In transit') }}
            </span>
        @endif
    </p>
@endif
