<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth', ['title' => '注册 | REGISTER'])] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $username = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'username' => ['required', 'string', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', 
                Rules\Password::min(8) // minimum of 8 characters
                    ->mixedCase() // upper and lower case letters
                    ->letters() // letters
                    ->numbers() // numbers
                    ->symbols() // symbols
            ],
        ]);

        $formatted_data = [
            'name' => ucwords($validated['name']),
            'email' => strtolower($validated['email']),
            'username' => ucwords($validated['username']),
        ];

        $formatted_data['password'] = Hash::make($validated['password']);

        event(new Registered(($user = User::create($formatted_data))));

        // redirect user to login page after creating account
        $this->redirect(route('login'));

        // logs in user after creating account
        // Auth::login($user);
        // $this->redirectIntended(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

    {{-- Session Status --}}
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form method="POST" wire:submit="register" class="flex flex-col gap-6">

        {{-- Name --}}
        <flux:input
            wire:model="name"
            :label="__('Full Name')"
            type="text"
            required
            autofocus
            autocomplete="name"
            :placeholder="__('Full name')"
        />

        {{-- Username --}}
        <flux:input
            wire:model="username"
            :label="__('Username')"
            type="text"
            required
            autofocus
            autocomplete="username"
            :placeholder="__('Please Enter Your Username to be Used for Log In.')"
        />

        {{-- Email Address --}}
        <flux:input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            autocomplete="email"
            placeholder="email@example.com"
        />

        {{-- Password --}}
        <flux:input
            wire:model="password"
            :label="__('Password')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Password')"
            viewable
        />

        {{-- Confirm Password --}}
        <flux:input
            wire:model="password_confirmation"
            :label="__('Confirm password')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Confirm password')"
            viewable
        />

        <div class="flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full cursor-pointer app-btn-alt">
                {{ __('Create account') }}
            </flux:button>
        </div>
    </form>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
        <span>{{ __('Already have an account?') }}</span>
        <flux:link :href="route('login')" wire:navigate class="app-text">{{ __('Log in') }}</flux:link>
    </div>
</div>
