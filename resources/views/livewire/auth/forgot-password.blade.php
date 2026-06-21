<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth', ['title' => 'Forgot Password'])] class extends Component {

    public string $username = '';
    public string $pin_code = '';

    protected function throttleKey(): string
    {
        return 'pin-reset:' . Str::lower($this->username) . '|' . request()->ip();
    }

    protected function ensureNotThrottled(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'pin_code' => __('Too many failed attempts. Try again in :seconds seconds.', [
                'seconds' => $seconds,
            ]),
        ]);
    }

    /**
     * Pad every request (success or failure) out to roughly the same
     * total duration, so response time can't leak whether a username
     * exists or how close a guessed PIN was. Done server-side so it
     * can't be bypassed by calling the action directly.
     */
    protected function settleResponseTime(float $startedAt): void
    {
        $targetSeconds = random_int(1500, 3000) / 1000; // 1.5–3s, tweak as needed
        $remaining     = $targetSeconds - (microtime(true) - $startedAt);

        if ($remaining > 0) {
            usleep((int) ($remaining * 1_000_000));
        }
    }

    public function resetPassword(): void
    {
        $startedAt = microtime(true);

        $this->validate([
            'username' => ['required', 'string'],
            'pin_code' => ['required', 'digits:6'],
        ]);

        $this->ensureNotThrottled();

        $user = User::whereRaw('LOWER(username) = LOWER(?)', [ucwords($this->username)])->first();

        // Always use the same error + hit the limiter — prevents username enumeration
        if (! $user || str_pad((string) $user->pin_code, 6, '0', STR_PAD_LEFT) !== $this->pin_code) {
            RateLimiter::hit($this->throttleKey(), 900); // 15-min decay
            $this->settleResponseTime($startedAt);

            throw ValidationException::withMessages([
                'pin_code' => __('Incorrect username or PIN code.'),
            ]);
        }

        // Temporary password = birthdate in Ymd format (e.g. 19901225)
        $tempPassword = $user->birth_date->format('Ymd');

        $user->password        = Hash::make($tempPassword);
        $user->change_password = true;
        $user->save();

        RateLimiter::clear($this->throttleKey());
        $this->settleResponseTime($startedAt);

        session()->flash('status', __('Password reset. Log in using your birthdate (YYYYMMDD).'));
        $this->redirect(route('login'), navigate: true);
    }
};
?>

<div class="flex flex-col gap-6">

    <x-auth-header
        :title="__('Forgot Password')"
        :description="__('Enter your username and 6-digit PIN to reset your password')"
    />

    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="resetPassword" class="flex flex-col gap-4">

        <flux:input
            wire:model="username"
            :label="__('Username')"
            type="text"
            required autofocus
            autocomplete="username"
            :placeholder="__('Enter your username')"
            wire:loading.attr="disabled"
            wire:target="resetPassword"
        />

        <flux:input
            wire:model="pin_code"
            :label="__('6-Digit PIN Code')"
            type="password"
            inputmode="numeric"
            maxlength="6"
            pattern="\d{6}"
            required
            :placeholder="__('Enter your 6-digit PIN')"
            wire:loading.attr="disabled"
            wire:target="resetPassword"
        />

        <flux:button
            type="submit"
            variant="primary"
            class="w-full cursor-pointer"
            wire:loading.attr="disabled"
            wire:target="resetPassword">
            <span wire:loading.remove wire:target="resetPassword">{{ __('Reset Password') }}</span>
            <span wire:loading wire:target="resetPassword">{{ __('Processing') }}</span>
        </flux:button>
    </form>

    <div class="space-x-1 text-center text-sm text-zinc-400">
        <span>{{ __('Remembered it?') }}</span>
        <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
    </div>

    @include('partials.loading-overlay')
</div>
