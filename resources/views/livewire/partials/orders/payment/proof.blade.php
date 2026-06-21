{{--
    proof-of-payment.blade.php
    Reusable proof of payment upload & display component for order forms and view modals.
    Props:
    - $existingProofUrl (string|null): URL of existing proof image to display (for edit/view modes)
    - $allowCamera (bool): Whether to show "Take Photo" button (default: true)
    - $readOnly (bool): If true, component is in view mode and does not allow changes (default: false)
    - $compact (bool): If true, use more compact styling (default: false)
    - $allowUploadInView (bool): When $readOnly is true, whether to still show upload buttons (default: false)
--}}

@php
    $existingProofUrl = $existingProofUrl ?? null;
    $allowCamera      = $allowCamera      ?? true;
    $readOnly         = $readOnly         ?? false;
    $compact          = $compact          ?? false;
    $paymentType      = strtolower(trim((string) ($paymentType ?? '')));
    $isCashPayment    = $paymentType === 'cash';
    $borderClass      = $compact
        ? 'rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/40 p-3 space-y-3'
        : 'rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-900/40 p-4 space-y-3';
@endphp

<div x-data="{ captureMode: null }" class="relative overflow-visible {{ $borderClass }}">

    {{-- ── Cash payments: display only if there is an existing proof ───────── --}}
    @if($isCashPayment)

        @if($existingProofUrl)
            <div class="flex items-center gap-2">
                <i class="fas fa-receipt text-blue-400 text-sm" aria-hidden="true"></i>
                <p class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                    {{ __('Image of Payment') }}
                </p>
            </div>

            <div class="space-y-2">
                <a href="{{ $existingProofUrl }}" target="_blank" rel="noopener"
                   class="block overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <img src="{{ $existingProofUrl }}"
                         alt="{{ __('Image of Payment') }}"
                         class="w-full max-h-60 object-contain bg-white dark:bg-zinc-800">
                </a>

                <p class="text-xs text-zinc-400 text-center">
                    {{ __('Tap image to open full size') }}
                </p>
            </div>
        @elseif(!$readOnly)
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('No proof of payment is required for cash payments.') }}
            </p>
        @endif

    {{-- ── Read-only / view mode ─────────────────────────────── --}}
    @elseif($readOnly)
        <div class="flex items-center gap-2">
            <i class="fas fa-receipt text-blue-400 text-sm" aria-hidden="true"></i>
            <p class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                {{ __('Image of Payment') }}
            </p>
        </div>

        @if($existingProofUrl)
            <div class="space-y-2">
                <a href="{{ $existingProofUrl }}" target="_blank" rel="noopener"
                   class="block overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <img src="{{ $existingProofUrl }}"
                         alt="{{ __('Image of Payment') }}"
                         class="w-full max-h-60 object-contain bg-white dark:bg-zinc-800">
                </a>

                <p class="text-xs text-zinc-400 text-center">
                    {{ __('Tap image to open full size') }}
                </p>
            </div>
        @else
            {{-- No proof uploaded — show upload/camera buttons in view mode --}}
            <div class="rounded-lg border border-dashed border-zinc-300 dark:border-zinc-600 p-4 text-center space-y-3">
                <i class="fas fa-image text-2xl text-zinc-300 dark:text-zinc-600 block" aria-hidden="true"></i>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('No proof of payment uploaded.') }}
                </p>
            </div>
        @endif

    {{-- ── Editable mode ─────────────────────────────────────── --}}
    @else
        <div class="flex items-center gap-2">
            <i class="fas fa-receipt text-blue-400 text-sm" aria-hidden="true"></i>
            <p class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                {{ __('Image of Payment') }}
                <span class="text-zinc-400 font-normal normal-case ml-1">({{ __('optional') }})</span>
            </p>
        </div>

        {{-- Existing proof preview (Edit form) --}}
        @if($existingProofUrl)
            <div class="space-y-2">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Current upload') }}</p>
                <div class="relative rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700">
                    <img src="{{ $existingProofUrl }}"
                         alt="{{ __('Image of Payment') }}"
                         class="w-full max-h-48 object-contain bg-white dark:bg-zinc-800">

                    <button type="button"
                        wire:click="removeProof"
                        class="absolute top-2 right-2 w-7 h-7 flex items-center justify-center
                               rounded-full bg-red-600 text-white hover:bg-red-700 text-xs transition-colors"
                        title="{{ __('Remove') }}">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </div>
                <p class="text-xs text-zinc-400">{{ __('Upload a new image to replace the current one.') }}</p>
            </div>
        @endif

        {{-- Upload / Camera buttons --}}
        <div class="flex flex-col sm:flex-row gap-2" wire:loading.class="opacity-40 pointer-events-none" wire:target="proofOfPayment">
            <button type="button"
                x-on:click="captureMode = null; $refs.proofInput.click()"
                class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2.5 text-sm
                       rounded-lg border border-zinc-300 dark:border-zinc-600
                       text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800
                       hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                <i class="fas fa-upload" aria-hidden="true"></i>{{ __('Upload') }}
            </button>

            @if($allowCamera)
                <button type="button"
                    x-on:click="captureMode = 'environment'; $refs.proofInput.click()"
                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2.5 text-sm
                           rounded-lg border border-zinc-300 dark:border-zinc-600
                           text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800
                           hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                    <i class="fas fa-camera" aria-hidden="true"></i>{{ __('Take Photo') }}
                </button>
            @endif
        </div>

        <input x-ref="proofInput"
               type="file"
               wire:model="proofOfPayment"
               accept="image/*"
               x-bind:capture="captureMode === 'environment' ? 'environment' : null"
               class="hidden">

        @error('proofOfPayment')
            <p class="text-xs text-red-500">{{ $message }}</p>
        @enderror

        {{-- New file preview (Livewire temporary upload) --}}
        @if(isset($proofOfPayment) && $proofOfPayment && method_exists($proofOfPayment, 'temporaryUrl'))
            <div class="space-y-1.5">
                <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">
                    {{ __('Preview') }}
                </p>
                <div class="relative rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700">
                    <img src="{{ $proofOfPayment->temporaryUrl() }}"
                         alt="{{ __('Proof of payment preview') }}"
                         class="w-full max-h-52 object-contain bg-white dark:bg-zinc-800">

                    <button type="button"
                        wire:click="$set('proofOfPayment', null)"
                        class="absolute top-2 right-2 w-7 h-7 flex items-center justify-center
                               rounded-full bg-red-600 text-white hover:bg-red-700 text-xs transition-colors"
                        title="{{ __('Remove') }}">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        @endif

        <div wire:loading.flex wire:target="proofOfPayment"
             class="absolute inset-0 z-20 flex items-center justify-center bg-black/40 backdrop-blur-[1px]">
            <div class="flex flex-col items-center gap-2 rounded-xl border border-white/15 bg-white/90 px-4 py-3 shadow-lg max-w-56 mx-3 dark:bg-zinc-900/90">
                <div class="relative flex h-9 w-9 items-center justify-center">
                    <div class="absolute inset-0 rounded-full border-3 border-blue-200 dark:border-blue-900"></div>
                    <div class="absolute inset-0 rounded-full border-3 border-transparent border-t-blue-600 dark:border-t-blue-400 animate-spin"></div>
                </div>

                <div class="text-center">
                    <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ __('Uploading payment proof') }}
                    </p>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                        {{ __('Please wait while the image is being uploaded.') }}
                    </p>
                </div>
            </div>
        </div>

    @endif
</div>
