{{-- Template layout (sidebar, header) --}}
<x-layouts.appTemplate.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts.appTemplate.sidebar>
