<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ env('STORE_NAME') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-bg-gradient app-text min-h-screen flex flex-col items-center justify-center font-serif">

    {{-- Store Title --}}
    <div class="text-center mb-8">
        @if (env('STORE_NAME_ALT'))
            <h1 class="text-5xl md:text-6xl font-bold mb-3">{{ env('STORE_NAME_ALT') }}</h1>
            <h2 class="text-xl md:text-2xl tracking-wide">{{ env('STORE_NAME') }}</h2>
        @else
            <h1 class="text-5xl md:text-6xl font-bold mb-3">{{ env('STORE_NAME') }}</h1>
        @endif
        <p>{{ env('STORE_ADDRESS') }}</p>
    </div>

    {{-- Login / Register --}}
    @if (Route::has('login'))
        <nav class="flex gap-6 text-lg">
            @auth
                <a href="{{ url('/dashboard') }}" class="hover:underline">{{ __('Dashboard') }}</a>
            @else
                <div class="flex flex-col items-center">
                    {{-- Main login button --}}
                    <a href="{{ route('login') }}"
                    class="px-6 py-2 app-btn font-semibold rounded-lg shadow-md hover:bg-[#e6c200] transition">
                        {{ __('Log in') }}
                    </a>

                    @php
                        $has_accounts = \App\Models\User::limit(1)->exists();
                    @endphp

                    {{-- Small register link --}}
                    @if (!$has_accounts)
                        <a href="{{ route('register') }}"
                        class="mt-3 text-sm app-text hover:underline">
                            {{ __('Register') }}
                        </a>
                    @endif
                </div>
            @endauth
        </nav>
    @endif

</body>
</html>
