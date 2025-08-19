<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MGM 888 Variety Store</title>

    @vite('resources/css/app.css')
</head>
<body class="bg-[#B22222] text-[#FFD700] min-h-screen flex flex-col items-center justify-center font-serif">

    {{-- Store Title --}}
    <div class="text-center mb-8">
        <h1 class="text-5xl md:text-6xl font-bold mb-3">紅運雜貨铺</h1>
        <h2 class="text-xl md:text-2xl tracking-wide">MGM 888 VARIETY STORE</h2>
        <p>842 Masangkay St. Binondo, Manila</p>
    </div>

    {{-- Login / Register --}}
    @if (Route::has('login'))
        <nav class="flex gap-6 text-lg">
            @auth
                <a href="{{ url('/dashboard') }}" class="hover:underline">Dashboard</a>
            @else
                <div class="flex flex-col items-center">
                    {{-- Main login button --}}
                    <a href="{{ route('login') }}" 
                    class="px-6 py-2 bg-[#FFD700] text-[#B22222] font-semibold rounded-lg shadow-md hover:bg-[#e6c200] transition">
                        Log in
                    </a>

                    {{-- Small register link --}}
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" 
                        class="mt-3 text-sm text-[#FFD700] hover:underline">
                            Register
                        </a>
                    @endif
                </div>
            @endauth
        </nav>
    @endif

</body>
</html>
