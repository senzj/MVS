@section('title', 'Discount Presets')

<div x-data="{ showDeleteConfirm: false, showToggleConfirm: false, selectedPresetId: null, selectedPresetIsActive: false }" class="w-full max-w-7xl mx-auto overflow-hidden px-3 sm:px-4 lg:px-6 pb-8 space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h2 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
            <i class="fas fa-tags text-emerald-500"></i>
            {{ __('Discount Presets') }}
        </h2>
        <a href="{{ route('orders') }}" wire:navigate
            class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300 transition dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 text-sm font-medium w-full sm:w-auto">
            <i class="fas fa-arrow-left"></i>{{ __('Back to Orders') }}
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
        <div class="lg:col-span-1 bg-white dark:bg-zinc-800 rounded-2xl shadow-sm p-5">
            <h3 class="text-sm font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-4">
                {{ $editingId ? __('Edit Discount Presets') : __('Create Discount Presets') }}
            </h3>

            <form wire:submit.prevent="savePreset" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('Discount Name') }}</label>
                    <input type="text" wire:model.defer="name"
                        class="w-full px-3 py-2.5 text-sm rounded-lg border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition"
                        placeholder="{{ __('e.g. Senior Citizen, 10% off, 50 pesos off, etc.') }}">
                    @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('Discount Type') }}</label>
                        <select wire:model="type"
                            class="w-full px-3 py-2.5 text-sm rounded-lg border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                            <option value="percentage">{{ __('Percentage') }}</option>
                            <option value="fixed">{{ __('Fixed Amount') }}</option>
                        </select>
                        @error('type') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('Value') }}</label>
                        <input type="number" min="0" step="0.01" wire:model.defer="value"
                            class="w-full px-3 py-2.5 text-sm rounded-lg border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition"
                            placeholder="0.00">
                        @error('value') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <input type="checkbox" wire:model="is_active" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                    {{ __('Active') }}
                </label>

                <div class="flex flex-col sm:flex-row gap-2 justify-end">
                    <button type="submit"
                        wire:loading.attr="disabled"
                        wire:target="savePreset,editPreset,toggleActive,deletePreset"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition w-full sm:w-auto">
                        <span wire:loading.remove wire:target="savePreset">
                            <i class="fas fa-save"></i>{{ $editingId ? __('Update Discount Preset') : __('Save Discount Preset') }}
                        </span>
                        <span wire:loading wire:target="savePreset" class="flex items-center gap-2">
                            <i class="fas fa-spinner fa-spin"></i>
                            {{ __('Saving') }}
                        </span>
                    </button>

                    @if($editingId)
                        <button type="button" wire:click="resetForm"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-zinc-100 dark:bg-zinc-700 text-sm font-semibold text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition w-full sm:w-auto">
                            {{ __('Cancel') }}
                        </button>
                    @endif
                </div>
            </form>
        </div>

        {{-- Mobile Layout --}}
        <div class="lg:col-span-2 space-y-4">
            <h3 class="text-sm font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-4">
                {{ __('Available Discount Presets') }}
            </h3>

            <div class="space-y-3 md:hidden">
                @forelse($presets as $preset)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $preset->name }}
                                </p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ ucfirst($preset->type) }}
                                </p>
                            </div>

                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $preset->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' }}">
                                {{ $preset->is_active ? __('Active') : __('Inactive') }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-sm text-zinc-700 dark:text-zinc-300">
                            <span class="text-zinc-500 dark:text-zinc-400">{{ __('Discount Amount') }}</span>
                            <span class="font-medium">
                                @if($preset->type === 'percentage')
                                    {{ rtrim(rtrim(number_format((float) $preset->value, 2, '.', ''), '0'), '.') }}%
                                @else
                                    ₱{{ number_format((float) $preset->value, 2) }}
                                @endif
                            </span>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="selectedPresetId = {{ $preset->id }}; $nextTick(() => { $wire.editPreset(selectedPresetId); })"
                                class="flex-1 min-w-[7rem] px-3 py-2 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/40 transition">
                                {{ __('Edit') }}
                            </button>

                            <button type="button" @click="selectedPresetId = {{ $preset->id }}; selectedPresetIsActive = {{ $preset->is_active ? 'true' : 'false' }}; showToggleConfirm = true"
                                class="flex-1 min-w-[7rem] px-3 py-2 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-300 dark:hover:bg-amber-900/40 transition">
                                {{ $preset->is_active ? __('Disable') : __('Enable') }}
                            </button>

                            <button type="button" @click="selectedPresetId = {{ $preset->id }}; showDeleteConfirm = true"
                                class="flex-1 min-w-[7rem] px-3 py-2 rounded-lg bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/40 transition">
                                {{ __('Delete') }}
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 text-center text-zinc-500 dark:text-zinc-400">
                        {{ __('No discount presets yet. Create your first preset on the left.') }}
                    </div>
                @endforelse
            </div>

            <div class="hidden md:block overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-100 dark:bg-zinc-900/60 text-zinc-600 dark:text-zinc-300 uppercase text-xs tracking-wide">
                        <tr>
                            <th class="text-center px-4 py-3">{{ __('Name') }}</th>
                            <th class="text-center px-4 py-3">{{ __('Discount Type') }}</th>
                            <th class="text-center px-4 py-3">{{ __('Value') }}</th>
                            <th class="text-center px-4 py-3">{{ __('Status') }}</th>
                            <th class="text-center px-4 py-3">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($presets as $preset)
                            <tr class="bg-white dark:bg-zinc-800">
                                {{-- Name --}}
                                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $preset->name }}
                                </td>

                                {{-- Discount Type --}}
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300 text-center">
                                    @if($preset->type === 'percentage')
                                        {{ __('Percentage') }}
                                    @else
                                        {{ __('Fixed Amount') }}
                                    @endif
                                </td>

                                {{-- Value --}}
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300 text-center">
                                    @if($preset->type === 'percentage')
                                        {{ rtrim(rtrim(number_format((float) $preset->value, 2, '.', ''), '0'), '.') }}%
                                    @else
                                        ₱{{ number_format((float) $preset->value, 2) }}
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $preset->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' }}">
                                        {{ $preset->is_active ? __('Active') : __('Inactive') }}
                                    </span>
                                </td>

                                {{-- Actions --}}
                                <td class="px-4 py-3 text-center">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button" @click="selectedPresetId = {{ $preset->id }}; $nextTick(() => { $wire.editPreset(selectedPresetId); })"
                                            class="tbl-action-btn text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20">
                                            <i class="fas fa-edit"></i>{{ __('Edit') }}
                                        </button>

                                        <button type="button" @click="selectedPresetId = {{ $preset->id }}; selectedPresetIsActive = {{ $preset->is_active ? 'true' : 'false' }}; showToggleConfirm = true"
                                            class="tbl-action-btn text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-900/20">
                                            <i class="fas {{ $preset->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                            {{ $preset->is_active ? __('Disable') : __('Enable') }}
                                        </button>

                                        <button type="button" @click="selectedPresetId = {{ $preset->id }}; showDeleteConfirm = true"
                                            class="tbl-action-btn text-red-600 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-900/20">
                                            <i class="fas fa-trash"></i>{{ __('Delete') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">
                                    {{ __('No discount presets yet. Create your first preset on the left.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('livewire.partials.loading-overlay', [
        'wireTarget' => 'savePreset,editPreset,toggleActive,deletePreset,resetForm',
    ])

    {{-- Confirm Toggle Modal --}}
    <div x-cloak x-show="showToggleConfirm" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 bg-zinc-950/60">
        <div class="w-full max-w-md bg-white dark:bg-zinc-800 rounded-3xl shadow-2xl border border-zinc-100 dark:border-zinc-700 overflow-hidden">
            <div class="p-5">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Confirm') }}</h3>
                @php
                    $confirmTplJs = json_encode(__('Are you sure you want to :action this discount preset?'));
                    $enableJs = json_encode(__('Enable'));
                    $disableJs = json_encode(__('Disable'));
                @endphp

                     <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-2"
                         x-text='(function(){ const tpl = {!! $confirmTplJs !!}; const action = selectedPresetIsActive ? {!! $disableJs !!} : {!! $enableJs !!}; return tpl.replace(":action", action); })()'></p>

                <div class="flex items-center justify-end gap-2 mt-4">
                    <button type="button" @click="showToggleConfirm = false" class="px-4 py-2 rounded-xl border border-zinc-200 dark:border-zinc-600 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">{{ __('Cancel') }}</button>
                    <button type="button" @click=" $wire.toggleActive(selectedPresetId).then(() => { showToggleConfirm = false; })" class="px-4 py-2 rounded-xl bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 transition-colors shadow-md" x-text="selectedPresetIsActive ? '{{ __('Disable') }}' : '{{ __('Enable') }}'"></button>
                </div>
            </div>
        </div>
    </div>

    {{-- Confirm Delete Modal --}}
    <div x-cloak x-show="showDeleteConfirm" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 bg-zinc-950/60">
        <div class="w-full max-w-md bg-white dark:bg-zinc-800 rounded-3xl shadow-2xl border border-zinc-100 dark:border-zinc-700 overflow-hidden">
            <div class="p-5">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Confirm Delete') }}</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-2">{{ __('Are you sure you want to permanently delete this discount preset? This action cannot be undone.') }}</p>

                <div class="flex items-center justify-end gap-2 mt-4">
                    <button type="button" @click="showDeleteConfirm = false" class="px-4 py-2 rounded-xl border border-zinc-200 dark:border-zinc-600 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">{{ __('Cancel') }}</button>
                    <button type="button" @click=" $wire.deletePreset(selectedPresetId).then(() => { showDeleteConfirm = false; })" class="px-4 py-2 rounded-xl bg-red-600 text-white text-sm font-semibold hover:bg-red-700 transition-colors shadow-md">{{ __('Delete') }}</button>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .tbl-action-btn {
                display: inline-flex;
                flex-direction: column;
                align-items: center;
                gap: 0.125rem;
                padding: 0.375rem 0.5rem;
                border-radius: 0.5rem;
                font-size: 0.75rem;
                font-weight: 500;
                transition: background-color 0.15s;
                cursor: pointer;
                white-space: nowrap;
                min-width: 3rem;
                text-align: center;
            }
        </style>
    @endpush
</div>
