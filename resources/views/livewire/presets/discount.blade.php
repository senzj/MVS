@section('title', 'Discount Presets')

<div class="w-full max-w-7xl mx-auto overflow-hidden px-3 sm:px-4 lg:px-6 pb-8 space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h2 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
            <i class="fas fa-tags text-emerald-500"></i>Discount Presets
        </h2>
        <a href="{{ route('orders') }}" wire:navigate
            class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300 transition dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 text-sm font-medium w-full sm:w-auto">
            <i class="fas fa-arrow-left"></i>Back to Orders
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
        <div class="lg:col-span-1 bg-white dark:bg-zinc-800 rounded-2xl shadow-sm p-5">
            <h3 class="text-sm font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-4">
                {{ $editingId ? 'Edit Preset' : 'Create Preset' }}
            </h3>

            <form wire:submit.prevent="savePreset" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Preset Name</label>
                    <input type="text" wire:model.defer="name"
                        class="w-full px-3 py-2.5 text-sm rounded-lg border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition"
                        placeholder="e.g. Senior Citizen">
                    @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Type</label>
                        <select wire:model="type"
                            class="w-full px-3 py-2.5 text-sm rounded-lg border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                            <option value="percentage">Percentage</option>
                            <option value="fixed">Fixed Amount</option>
                        </select>
                        @error('type') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Value</label>
                        <input type="number" min="0" step="0.01" wire:model.defer="value"
                            class="w-full px-3 py-2.5 text-sm rounded-lg border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700/60 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition"
                            placeholder="0.00">
                        @error('value') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <input type="checkbox" wire:model="is_active" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                    Active
                </label>

                <div class="flex flex-col sm:flex-row gap-2 justify-end">
                    <button type="submit"
                        wire:loading.attr="disabled"
                        wire:target="savePreset,editPreset,toggleActive,deletePreset"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition w-full sm:w-auto">
                        <span wire:loading.remove wire:target="savePreset">
                            <i class="fas fa-save"></i>{{ $editingId ? 'Update Preset' : 'Save Preset' }}
                        </span>
                        <span wire:loading wire:target="savePreset" class="flex items-center gap-2">
                            <i class="fas fa-spinner fa-spin"></i>Saving
                        </span>
                    </button>

                    @if($editingId)
                        <button type="button" wire:click="resetForm"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-zinc-100 dark:bg-zinc-700 text-sm font-semibold text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition w-full sm:w-auto">
                            Cancel Edit
                        </button>
                    @endif
                </div>
            </form>
        </div>

        <div class="lg:col-span-2 space-y-4">
            <h3 class="text-sm font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-4">
                Available Presets
            </h3>

            <div class="space-y-3 md:hidden">
                @forelse($presets as $preset)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $preset->name }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ ucfirst($preset->type) }}</p>
                            </div>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $preset->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' }}">
                                {{ $preset->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-sm text-zinc-700 dark:text-zinc-300">
                            <span class="text-zinc-500 dark:text-zinc-400">Value</span>
                            <span class="font-medium">
                                @if($preset->type === 'percentage')
                                    {{ rtrim(rtrim(number_format((float) $preset->value, 2, '.', ''), '0'), '.') }}%
                                @else
                                    ₱{{ number_format((float) $preset->value, 2) }}
                                @endif
                            </span>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="button" wire:click="editPreset({{ $preset->id }})"
                                class="flex-1 min-w-[7rem] px-3 py-2 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/40 transition">
                                Edit
                            </button>
                            <button type="button" wire:click="toggleActive({{ $preset->id }})"
                                class="flex-1 min-w-[7rem] px-3 py-2 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-300 dark:hover:bg-amber-900/40 transition">
                                {{ $preset->is_active ? 'Disable' : 'Enable' }}
                            </button>
                            <button type="button" wire:click="deletePreset({{ $preset->id }})"
                                class="flex-1 min-w-[7rem] px-3 py-2 rounded-lg bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/40 transition">
                                Delete
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 text-center text-zinc-500 dark:text-zinc-400">
                        No discount presets yet. Create your first preset on the left.
                    </div>
                @endforelse
            </div>

            <div class="hidden md:block overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-100 dark:bg-zinc-900/60 text-zinc-600 dark:text-zinc-300 uppercase text-xs tracking-wide">
                        <tr>
                            <th class="text-left px-4 py-3">Name</th>
                            <th class="text-left px-4 py-3">Type</th>
                            <th class="text-left px-4 py-3">Value</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-right px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($presets as $preset)
                            <tr class="bg-white dark:bg-zinc-800">
                                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $preset->name }}</td>
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ ucfirst($preset->type) }}</td>
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                    @if($preset->type === 'percentage')
                                        {{ rtrim(rtrim(number_format((float) $preset->value, 2, '.', ''), '0'), '.') }}%
                                    @else
                                        ₱{{ number_format((float) $preset->value, 2) }}
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $preset->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' }}">
                                        {{ $preset->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button" wire:click="editPreset({{ $preset->id }})"
                                            class="px-2.5 py-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/40 transition">
                                            Edit
                                        </button>
                                        <button type="button" wire:click="toggleActive({{ $preset->id }})"
                                            class="px-2.5 py-1.5 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-300 dark:hover:bg-amber-900/40 transition">
                                            {{ $preset->is_active ? 'Disable' : 'Enable' }}
                                        </button>
                                        <button type="button" wire:click="deletePreset({{ $preset->id }})"
                                            class="px-2.5 py-1.5 rounded-lg bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/40 transition">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">
                                    No discount presets yet. Create your first preset on the left.
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
</div>
