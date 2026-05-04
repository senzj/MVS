{{-- Full-screen loading overlay to indicate system activity and prevent button spam --}}
<div @if($wireTarget ?? null) wire:loading.class="flex" wire:loading.class.remove="hidden" wire:target="{{ $wireTarget }}" @else wire:loading.class="flex" wire:loading.class.remove="hidden" @endif
     class="hidden fixed inset-0 z-100 items-center justify-center bg-black/40">

    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl p-8 flex flex-col items-center gap-4 max-w-sm mx-4">
        {{-- Spinner --}}
        <div class="relative w-12 h-12 flex items-center justify-center">
            <div class="absolute inset-0 rounded-full border-4 border-blue-200 dark:border-blue-900"></div>
            <div class="absolute inset-0 rounded-full border-4 border-transparent border-t-blue-600 dark:border-t-blue-400 animate-spin"></div>
        </div>

        {{-- Message --}}
        <div class="text-center space-y-1">
            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                {{ __('Processing') }}
            </p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Please wait while we process your request') }}...
            </p>
        </div>

        {{-- Subtle tip --}}
        {{-- <div class="text-xs text-zinc-400 dark:text-zinc-500 italic">
            {{ __('Please Do not Close This Window') }}
        </div> --}}
    </div>
</div>
