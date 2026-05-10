@if($showCancelModal)
    <div class="fixed inset-0 z-40 flex items-center justify-center" role="dialog" aria-modal="true">
        <button type="button" wire:click="closeCancelModal" class="fixed inset-0 bg-black/50" aria-label="{{ __('Close') }}"></button>

        <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-lg p-6 z-50 w-full max-w-md">
            <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">{{ __('Confirm Cancellation') }}</h3>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Are you sure you want to cancel this order? This will restore inventory and set the order status to cancelled.') }}</p>

            <div class="mt-4 flex justify-end gap-3">
                <button type="button"
                    wire:click="closeCancelModal"
                    wire:loading.attr="disabled" wire:target="confirmCancel"
                    class="px-4 py-2 rounded-lg bg-zinc-100 dark:bg-zinc-700 text-sm">{{ __('No') }}</button>

                <button type="button"
                    wire:click="confirmCancel"
                    wire:loading.attr="disabled" wire:target="confirmCancel"
                    class="px-4 py-2 rounded-lg bg-orange-600 text-white text-sm">
                    <span wire:loading.remove wire:target="confirmCancel">{{ __('Cancel Order') }}</span>
                    <span wire:loading wire:target="confirmCancel" class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                            <path class="opacity-75" fill="currentColor" d="M12 2a10 10 0 0 1 10 10h-3a7 7 0 0 0-7-7V2z"></path>
                        </svg>
                        {{ __('Cancelling') }}...
                    </span>
                </button>
            </div>
        </div>
    </div>
@endif
