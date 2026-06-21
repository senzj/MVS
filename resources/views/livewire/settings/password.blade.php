<?php

use App\Traits\HasPasswordRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component {
    use HasPasswordRule;

    public string $current_password      = '';
    public string $password              = '';
    public string $password_confirmation = '';

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password'         => ['required', 'string', 'confirmed', $this->passwordRule()],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');
            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');
        $this->dispatch('password-updated');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Update Password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
            <flux:input
                wire:model="current_password"
                :label="__('Current Password')"
                type="password"
                required
                autocomplete="current-password"
                viewable
            />
            <flux:input
                wire:model="password"
                :label="__('New Password')"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />
            <flux:input
                wire:model="password_confirmation"
                :label="__('Confirm Password')"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" class="w-full">
                    <span wire:loading.remove wire:target="updatePassword">{{ __('Save') }}</span>
                    <span wire:loading wire:target="updatePassword" class="inline-flex items-center gap-2">
                        <i class="fas fa-spinner fa-spin text-xs"></i>{{ __('Saving…') }}
                    </span>
                </flux:button>

                <x-action-message class="me-3" on="password-updated">
                    {{ __('Saved') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
