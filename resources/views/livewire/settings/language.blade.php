<?php

use Livewire\Volt\Component;

new class extends Component {
    public string $language = '';
    public string $search = '';

    public array $languages = [
        'en' => 'English',
        'zh' => 'Chinese',
    ];

    public function mount(): void
    {
        $this->language = session('locale', config('app.locale', 'en'));
    }

    public function updatedLanguage(): void
    {
        session()->put('locale', $this->language);

        // Redirect back to the page URL (not the Livewire update endpoint)
        $referer = request()->headers->get('referer');
        $this->redirect($referer ?: route('settings.language'), navigate: true);
    }

    public function selectLanguage(string $code): void
    {
        if (array_key_exists($code, $this->languages)) {
            $this->language = $code;
            $this->updatedLanguage();
        }
    }

    public function getFilteredLanguagesProperty(): array
    {
        $q = strtolower(trim($this->search));
        if ($q === '') return $this->languages;

        return array_filter(
            $this->languages,
            fn ($label, $code) => str_contains(strtolower($label), $q) || str_contains(strtolower($code), $q),
            ARRAY_FILTER_USE_BOTH
        );
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Language')" :subheading=" __('Update the language settings for your account')">
        <div class="space-y-6">

            {{-- Segmented radios (bound to Livewire) --}}
            {{-- <flux:radio.group variant="segmented" wire:model.live="language">
                <flux:radio value="en">{{ __('English') }}</flux:radio>
                <flux:radio value="cn">{{ __('Chinese') }}</flux:radio>
            </flux:radio.group> --}}

            <div class="">
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-600 dark:text-neutral-300">{{ __('Current language') }}:</span>

                    {{-- Searchable dropdown selector --}}
                    <div x-data="{ open:false }" class="relative" x-cloak>
                        <button type="button"
                                @click="open = !open"
                                class="inline-flex min-w-[12rem] items-center justify-between gap-2 rounded-lg bg-white px-3 py-2 text-sm shadow-sm hover:bg-gray-50 hover:shadow-md
                                       dark:bg-neutral-900 dark:text-neutral-200 dark:hover:bg-neutral-800 dark:shadow">
                            <span>{{ __($languages[$language] ?? 'Select') }}</span>
                            <i class="fa-solid fa-chevron-down text-gray-500 dark:text-neutral-400"></i>
                        </button>

                        <div x-show="open" x-transition @click.outside="open=false"
                             class="absolute z-20 left-0 mt-2 min-w-[14rem] w-max rounded-lg bg-white p-2 shadow-xl
                                    dark:bg-neutral-900">
                            <input type="text"
                                   placeholder="{{ __('Search language...') }}"
                                   class="mb-2 w-full rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-800 shadow-inner outline-none focus:ring-2 focus:ring-blue-500
                                          dark:bg-neutral-800 dark:text-neutral-200 placeholder:text-gray-400 dark:placeholder:text-neutral-500"
                                   wire:model.live="search" />

                            <ul class="max-h-56 overflow-auto">
                                @forelse ($this->filteredLanguages as $code => $label)
                                    <li>
                                        <button type="button"
                                                class="flex w-full items-center justify-between gap-2 rounded-md px-3 py-2 text-left text-sm hover:bg-gray-100
                                                       dark:hover:bg-neutral-800 dark:text-neutral-200"
                                                wire:click="selectLanguage('{{ $code }}')"
                                                @click="open=false">
                                            <span>{{ __($label) }}</span>
                                            @if ($language === $code)
                                                <i class="fa-solid fa-check text-blue-600"></i>
                                            @endif
                                        </button>
                                    </li>
                                @empty
                                    <li class="px-3 py-2 text-sm text-gray-500 dark:text-neutral-400">{{ __('No results') }}</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </x-settings.layout>
</section>
