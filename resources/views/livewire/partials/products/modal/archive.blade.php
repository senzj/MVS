<div x-show="showArchiveModal"
    x-cloak
    wire:key="archive-confirm-modal"
    x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-4">

    <div class="absolute inset-0 bg-black/50" @click="closeArchiveModal()"></div>

    <div x-show="showArchiveModal"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative w-full max-w-sm bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-6 text-center">
            <div class="w-14 h-14 rounded-2xl bg-yellow-100 dark:bg-yellow-900/40 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-eye-slash text-yellow-600 dark:text-yellow-400 text-2xl"></i>
            </div>
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 mb-2">{{ __('Hide Product') }}</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">
                {{ __('Are you sure you want to hide this product? It will be marked as unavailable for sale.') }}
            </p>
            <div class="flex justify-center gap-2">
                <button @click="closeArchiveModal()"
                    class="cursor-pointer px-4 py-2 text-sm font-medium rounded-xl border border-zinc-200 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                    <i class="fas fa-times mr-1"></i>{{ __('Cancel') }}
                </button>
                <button wire:click="archiveProduct" @click="closeArchiveModal()"
                    class="cursor-pointer px-4 py-2 text-sm font-semibold rounded-xl bg-yellow-600 text-white hover:bg-yellow-700 active:scale-95 transition-all">
                    <i class="fas fa-eye-slash mr-1"></i>{{ __('Confirm Hide') }}
                </button>
            </div>
        </div>
    </div>
</div>
