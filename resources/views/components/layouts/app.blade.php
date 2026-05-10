{{-- Template layout (sidebar, header) --}}
<x-layouts.appTemplate.sidebar :title="$title ?? null">

    <flux:main id="main-content" class="bg-white dark:bg-zinc-800/85">
        {{ $slot }}
    </flux:main>
</x-layouts.appTemplate.sidebar>
