<?php

use App\Traits\HasPasswordRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component {

    public string $current_pin = '';
    public string $pin_code    = '';
    public string $pin_confirm = '';

    protected function throttleKey(): string
    {
        return 'pin-update:' . Auth::id() . '|' . request()->ip();
    }

    protected function ensureNotThrottled(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'current_pin' => __('Too many failed attempts. Try again in :seconds seconds.', [
                'seconds' => $seconds,
            ]),
        ]);
    }

    public function updatePin(): void
    {
        $this->validate([
            'current_pin' => ['required', 'digits:6'],
            'pin_code'    => ['required', 'digits:6', 'same:pin_confirm'],
            'pin_confirm' => ['required', 'digits:6'],
        ]);

        $this->ensureNotThrottled();

        $user = Auth::user();

        if (str_pad((string) $user->pin_code, 6, '0', STR_PAD_LEFT) !== $this->current_pin) {
            RateLimiter::hit($this->throttleKey(), 900);
            throw ValidationException::withMessages([
                'current_pin' => __('Current PIN is incorrect.'),
            ]);
        }

        if ($this->current_pin === $this->pin_code) {
            throw ValidationException::withMessages([
                'pin_code' => __('New PIN must be different from your current PIN.'),
            ]);
        }

        $user->pin_code = (int) $this->pin_code;
        $user->save();

        RateLimiter::clear($this->throttleKey());

        $this->reset('current_pin', 'pin_code', 'pin_confirm');
        $this->dispatch('pin-updated');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout
        :heading="__('Recovery PIN')"
        :subheading="__('Your 6-digit PIN is used to reset your password if you ever get locked out.')">

        @php
            $maskedPin = str_pad((string) Auth::user()->pin_code, 6, '0', STR_PAD_LEFT);
        @endphp

        {{-- Current PIN display --}}
        <div class="mb-6 rounded-xl border border-zinc-200 dark:border-zinc-700
                    bg-zinc-50 dark:bg-zinc-800/60 px-4 py-3"
            x-data="{ revealed: false }">

            <p class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-2">
                {{ __('Current PIN') }}
            </p>

            <div class="flex items-center justify-between gap-3">

                {{-- Censored / revealed PIN --}}
                <span class="font-mono text-xl font-bold tracking-[0.35em] text-zinc-800 dark:text-zinc-100 select-none"
                    x-text="revealed ? '{{ $maskedPin }}' : '••••••'">
                </span>

                {{-- Hold-to-reveal button --}}
                <button type="button"
                    @mousedown="revealed = true"
                    @mouseup="revealed = false"
                    @mouseleave="revealed = false"
                    @touchstart.prevent="revealed = true"
                    @touchend.prevent="revealed = false"
                    @touchcancel.prevent="revealed = false"
                    title="{{ __('Hold to reveal') }}"
                    class="group relative flex items-center gap-1.5 select-none
                           px-3 py-1.5 rounded-lg text-xs font-semibold
                           text-zinc-500 dark:text-zinc-400
                           border border-zinc-200 dark:border-zinc-600
                           bg-white dark:bg-zinc-700
                           hover:border-zinc-300 dark:hover:border-zinc-500
                           active:bg-zinc-100 dark:active:bg-zinc-600
                           transition-colors cursor-pointer touch-none">

                    {{-- Eye icon toggles on reveal --}}
                    <i x-show="!revealed" class="fas fa-eye text-xs"></i>
                    <i x-show="revealed"  class="fas fa-eye-slash text-xs" x-cloak></i>

                    <span x-show="!revealed">{{ __('Show PIN') }}</span>
                    <span x-show="revealed"  x-cloak>{{ __('Hide PIN') }}</span>
                </button>
            </div>
        </div>

        {{-- Change PIN form --}}
        <form method="POST" wire:submit="updatePin" class="space-y-4">

            <flux:input
                wire:model="current_pin"
                :label="__('Current PIN')"
                type="password"
                inputmode="numeric"
                maxlength="6"
                pattern="\d{6}"
                required
                placeholder="••••••"
                viewable
            />

            <div class="pt-2 border-t border-zinc-100 dark:border-zinc-700 space-y-4">
                <p class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">
                    {{ __('New PIN') }}
                </p>

                <flux:input
                    wire:model="pin_code"
                    :label="__('New 6-Digit PIN')"
                    type="password"
                    inputmode="numeric"
                    maxlength="6"
                    pattern="\d{6}"
                    required
                    placeholder="••••••"
                    viewable
                />

                <flux:input
                    wire:model="pin_confirm"
                    :label="__('Confirm New PIN')"
                    type="password"
                    inputmode="numeric"
                    maxlength="6"
                    pattern="\d{6}"
                    required
                    placeholder="••••••"
                    viewable
                />
            </div>

            <div class="flex items-center gap-4 pt-2">
                <flux:button variant="primary" type="submit" class="w-full">
                    <span wire:loading.remove wire:target="updatePin">
                        <i class="fas fa-key mr-1.5 text-xs"></i>{{ __('Update PIN') }}
                    </span>
                    <span wire:loading wire:target="updatePin" class="inline-flex items-center gap-2">
                        <i class="fas fa-spinner fa-spin text-xs"></i>{{ __('Saving') }}
                    </span>
                </flux:button>

                <x-action-message class="me-3" on="pin-updated">
                    {{ __('PIN updated successfully') }}
                </x-action-message>
            </div>
        </form>

    </x-settings.layout>
</section>
