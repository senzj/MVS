<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public string $theme = '';

    public function mount(): void
    {
        if (Auth::check() && Auth::user()->theme) {
            $this->theme = Auth::user()->theme;
        } else {
            $this->theme = session('theme', config('app.theme', 'system'));
        }
    }

    public function updatedTheme(): void
    {
        session()->put('theme', $this->theme);

        if (Auth::check()) {
            Auth::user()->update(['theme' => $this->theme]);
        }
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading=" __('Update the appearance settings for your account')">
        <flux:radio.group
            x-data
            x-model="$flux.appearance"
            x-init="$nextTick(() => { $flux.appearance = @js($theme) })"
            x-on:change="$wire.set('theme', $event.target.value)"
            variant="segmented"
        >
            <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
            <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
            <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
        </flux:radio.group>
    </x-settings.layout>
</section>
