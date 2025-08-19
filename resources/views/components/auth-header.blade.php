@props([
    'title' => null,
    'subtitle' => null,
    'description' => null,
    'subdescription' => null,
])

<div class="text-center space-y-1">
    {{-- First Title --}}
    @if($title)
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
            {{ $title }}
        </h1>
    @endif

    {{-- Optional Second Title --}}
    @if($subtitle)
        <h2 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
            {{ $subtitle }}
        </h2>
    @endif

    {{-- Description --}}
    @if($description)
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
            {{ $description }}
        </p>
    @endif

    {{-- Optional Second Description --}}
    @if($subdescription)
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
            {{ $subdescription }}
        </p>
    @endif
</div>
