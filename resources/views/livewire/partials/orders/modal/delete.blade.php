{{--
    Delete Confirmation Modal.
    Rendered when $showDeleteModal is truthy.
    Expects: $showDeleteModal, $deleteReceipt
--}}

@if($showDeleteModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-sm bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl overflow-hidden">

            <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <i class="fas fa-triangle-exclamation text-red-500"></i>
                    {{ __('Confirm Deletion') }}
                </h3>
            </div>

            <div class="px-5 py-4 space-y-1.5">
                <p class="text-sm text-zinc-700 dark:text-zinc-300">
                    {{ __("Are you sure you want to delete this order? This action can't be undone.") }}
                </p>
                @if($deleteReceipt)
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 font-mono">
                        {{ __('Receipt #') }}: {{ $deleteReceipt }}
                    </p>
                @endif
            </div>

            <div class="px-5 py-4 bg-zinc-50 dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-700 flex justify-end gap-2">
                <button wire:click="closeDeleteModal"
                    class="cursor-pointer px-4 py-2 text-sm rounded-xl border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    {{ __('Cancel') }}
                </button>
                <button wire:click="deleteOrderConfirmed"
                    class="cursor-pointer px-4 py-2 text-sm font-semibold rounded-xl bg-red-600 text-white hover:bg-red-700 active:scale-95 transition-all">
                    <i class="fas fa-trash mr-1"></i>{{ __('Delete') }}
                </button>
            </div>

        </div>
    </div>
@endif
