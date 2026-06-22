<?php

use App\Traits\HasPasswordRule;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth', ['title' => 'Register'])] class extends Component {
    use HasPasswordRule;

    public string $name                  = '';
    public string $username              = '';
    public string $password              = '';
    public string $password_confirmation = '';
    public string $birth_date            = '';
    public string $pin_code              = '';
    public string $pin_code_confirmation = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required', 'string', 'max:255',
                function ($attribute, $value, $fail) {
                    if (User::whereRaw('LOWER(username) = LOWER(?)', [ucwords($value)])->exists()) {
                        $fail(__('The username has already been taken.'));
                    }
                },
            ],
            'password'              => ['required', 'string', 'confirmed', $this->passwordRule()],
            'birth_date'            => ['required', 'date', 'before:today'],
            'pin_code'              => ['required', 'digits:6', 'same:pin_code_confirmation'],
            'pin_code_confirmation' => ['required', 'digits:6'],
        ]);

        $user = User::create([
            'name'       => ucwords($validated['name']),
            'username'   => ucwords($validated['username']),
            'password'   => Hash::make($validated['password']),
            'birth_date' => $validated['birth_date'],
            'pin_code'   => (int) $validated['pin_code'],
        ]);

        event(new Registered($user));

        $this->redirect(route('login'));
    }
};
?>

<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Create an account')"
        :description="__('Enter your details below to create your account')"
    />

    <x-auth-session-status class="text-center" :status="session('status')" />

    <form method="POST" wire:submit="register" class="flex flex-col gap-6">

        {{-- ── Personal Info ─────────────────────── --}}
        <div class="space-y-4">
            <p class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">
                {{ __('Personal Information') }}
            </p>

            <flux:input
                wire:model="name"
                :label="__('Full Name')"
                type="text"
                required autofocus
                autocomplete="name"
                :placeholder="__('Juan Dela Cruz')"
            />

            <flux:input
                wire:model="birth_date"
                :label="__('Date of Birth')"
                type="date"
                required
                autocomplete="bday"
                :max="now()->subDay()->toDateString()"
            />
        </div>

        {{-- ── Account Credentials ───────────────── --}}
        <div class="space-y-4 pt-2 border-t border-zinc-100 dark:border-zinc-700">
            <p class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">
                {{ __('Account Credentials') }}
            </p>

            <flux:input
                wire:model="username"
                :label="__('Username')"
                type="text"
                required
                autocomplete="username"
                :placeholder="__('Used for log in')"
            />

            <flux:input
                wire:model="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                viewable
            />

            <flux:input
                wire:model="password_confirmation"
                :label="__('Confirm Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
            />
        </div>

        {{-- ── Recovery PIN ──────────────────────── --}}
        <div class="space-y-4 pt-2 border-t border-zinc-100 dark:border-zinc-700">
            <div>
                <p class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">
                    {{ __('Recovery PIN') }}
                </p>
                <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">
                    {{ __('Your 6-digit PIN is used to reset your password if you ever get locked out.') }}
                </p>
            </div>

            <flux:input
                wire:model="pin_code"
                :label="__('6-Digit PIN')"
                type="password"
                inputmode="numeric"
                maxlength="6"
                pattern="\d{6}"
                required
                placeholder="••••••"
            />

            <flux:input
                wire:model="pin_code_confirmation"
                :label="__('Confirm PIN')"
                type="password"
                inputmode="numeric"
                maxlength="6"
                pattern="\d{6}"
                required
                placeholder="******"
            />
        </div>

        <flux:button type="submit" variant="primary" class="w-full cursor-pointer app-btn-alt">
            <span wire:loading.remove wire:target="register">{{ __('Create Account') }}</span>
            <span wire:loading wire:target="register" class="inline-flex items-center gap-2">
                <i class="fas fa-spinner fa-spin text-xs"></i>{{ __('Creating…') }}
            </span>
        </flux:button>
    </form>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
        <span>{{ __('Already have an account?') }}</span>
        <flux:link :href="route('login')" wire:navigate class="app-text">{{ __('Log In') }}</flux:link>
    </div>
</div>
