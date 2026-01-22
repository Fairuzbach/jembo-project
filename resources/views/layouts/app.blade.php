@props(['title' => config('app.name', 'Laravel')])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('browser_title', config('app.name', 'Laravel'))</title>
    <link rel="icon" href="{{ asset('logo.webp') }}" type="image/webp">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    {{-- Alpine.js (Pastikan terload jika belum ada di app.js) --}}
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100 relative">

        {{-- ========================================================= --}}
        {{-- TAMBAHAN: TOMBOL LOGOUT & PROFIL DI POJOK KANAN ATAS --}}
        {{-- ========================================================= --}}
        @auth
            <div class="absolute top-4 right-4 z-50 flex items-center gap-3">

                {{-- Nama User (Hidden di Mobile) --}}
                <div class="hidden sm:block text-right">
                    <p class="text-xs text-gray-500">Login sebagai</p>
                    <p class="text-sm font-bold text-gray-700">{{ Auth::user()->name }}</p>
                </div>

                {{-- Tombol Logout --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="group flex items-center justify-center w-10 h-10 bg-white border border-gray-200 rounded-full shadow-sm hover:bg-red-50 hover:border-red-200 hover:text-red-600 text-gray-500 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                        title="Keluar / Logout">

                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-5 h-5 group-hover:scale-110 transition-transform">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                        </svg>
                    </button>
                </form>
            </div>
        @endauth
        {{-- ========================================================= --}}


        @include('layouts.navigation')

        @isset($header)
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 pr-20"> {{-- Tambah padding kanan agar tidak tertutup tombol logout --}}
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main>
            {{ $slot }}
        </main>
    </div>
</body>

</html>
