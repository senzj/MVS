<?php

use App\Models\User;
use App\Traits\HasPasswordRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth', ['title' => 'Reset Password'])] class extends Component {
    use HasPasswordRule;

    public string $pin_code = '';
    public string $password = '';
    public string $password_confirmation = '';

    public ?string $throttledUntil = null;

    protected function throttleKey(): string
    {
        return 'custom-pwd-reset:' . request()->ip();
    }

    protected function checkThrottle(): void
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            $seconds = RateLimiter::availableIn($this->throttleKey());
            $this->throttledUntil = now()->addSeconds($seconds)->format('g:i:s A');

            throw ValidationException::withMessages([
                'pin_code' => __('Too many verification attempts. Please try again at :time.', ['time' => $this->throttledUntil]),
            ]);
        }
        $this->throttledUntil = null;
    }

    public function resetPasswordCustom(): void
    {
        $this->checkThrottle();

        // 1. Only validate the components actually present in your form layout
        $this->validate([
            'pin_code'              => ['required', 'digits:6'],
            'password'              => ['required', 'string', 'confirmed', $this->passwordRule()],
        ]);

        // 2. Fetch the logged-in user safely from session context
        $user = Auth::user();

        if (! $user) {
            $this->redirectRoute('login');
            return;
        }

        // 3. Security Check: Validate PIN code matches database parameters
        if (str_pad((string)$user->pin_code, 6, '0', STR_PAD_LEFT) !== $this->pin_code) {
            RateLimiter::hit($this->throttleKey(), 900); // 15-minute penalty lockout

            throw ValidationException::withMessages([
                'pin_code' => __('The security PIN code provided is incorrect.'),
            ]);
        }

        // 4. Update the user credentials & clear flags
        $user->forceFill([
            'password'        => Hash::make($this->password),
            'change_password' => false,
        ])->save();

        RateLimiter::clear($this->throttleKey());

        // 5. Clear session tracking, flush notification states, and redirect cleanly out
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        session()->flash('status', __('Your password has been successfully updated. Please log in using your new credentials.'));

        $this->redirectRoute('login', navigate: false);
    }

    // 6. Added Missing Logout Action for Back Button
    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirectRoute('login', navigate: false);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Reset Password')" :description="__('Verify your account identity parameters to establish a new secure system access password.')" />

    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="resetPasswordCustom" class="flex flex-col gap-4">

        {{-- Password --}}
        <flux:field>
            <flux:input
                wire:model="password"
                :label="__('New Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Enter your new password')"
                viewable
                :disabled="(bool) $throttledUntil"
            />
            <flux:error name="password" />
        </flux:field>

        {{-- Confirm Password --}}
        <flux:field>
            <flux:input
                wire:model="password_confirmation"
                :label="__('Confirm New Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm your new password')"
                viewable
                :disabled="(bool) $throttledUntil"
            />
            <flux:error name="password_confirmation" />
        </flux:field>

        <hr class="border-zinc-200 dark:border-zinc-700 my-2" />

        {{-- 6-Digit PIN Code --}}
        <flux:field>
            <flux:input
                wire:model="pin_code"
                :label="__('6-Digit PIN Code')"
                type="password"
                inputmode="numeric"
                maxlength="6"
                pattern="\d{6}"
                required
                autocomplete="one-time-code"
                placeholder="******"
                :disabled="(bool) $throttledUntil"
            />
            <flux:error name="pin_code" />
        </flux:field>

        <div class="mt-2">
            <flux:button type="submit" variant="primary" class="w-full cursor-pointer" :disabled="(bool) $throttledUntil">
                <span wire:loading.remove wire:target="resetPasswordCustom">
                    {{ __('Update Credentials & Login') }}
                </span>
                <span wire:loading wire:target="resetPasswordCustom" class="inline-flex items-center gap-2">
                    <i class="fas fa-spinner fa-spin text-xs mr-1"></i> {{ __('Saving') }}
                </span>
            </flux:button>

            {{-- Back Button (Triggers newly added logout method) --}}
            <flux:button type="button" variant="ghost" class="w-full mt-2 cursor-pointer" wire:click="logout">
                {{ __('Back to Login') }}
            </flux:button>
        </div>
    </form>
</div>
