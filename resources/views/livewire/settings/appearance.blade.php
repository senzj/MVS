<?php

use Livewire\Volt\Component;

new class extends Component {
    public string $theme = '';

    public function mount(): void
    {
        $this->theme = session('theme', config('app.theme', 'system'));
    }

    public function updatedTheme(): void
    {
        // Save to session for immediate use
        session()->put('theme', $this->theme);

        // Save to database if user is authenticated
        if (Auth::check()) {
            Auth::user()->update(['theme' => $this->theme]);
        }

        // Redirect back to the page URL (not the Livewire update endpoint)
        $referer = request()->headers->get('referer');
        $this->redirect($referer ?: route('settings.appearance'), navigate: true);
    }

    public function selectTheme(string $theme): void
    {
        if (in_array($theme, ['light', 'dark', 'system'])) {
            $this->theme = $theme;
            $this->updatedTheme();
        }
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading=" __('Update the appearance settings for your account')">
        <flux:radio.group variant="segmented" wire:model.live="theme">
            <flux:radio value="light">{{ __('Light') }}</flux:radio>
            <flux:radio value="dark">{{ __('Dark') }}</flux:radio>
            <flux:radio value="system">{{ __('System') }}</flux:radio>
        </flux:radio.group>
    </x-settings.layout>
</section>
