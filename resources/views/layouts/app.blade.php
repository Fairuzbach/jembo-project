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

    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, #6366f1, #8b5cf6);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(to bottom, #4f46e5, #7c3aed);
        }

        /* Animated Background */
        .animated-bg {
            background: linear-gradient(-45deg, #f8fafc, #f1f5f9, #e0e7ff, #ede9fe);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }

        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        /* Floating Animation */
        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen animated-bg">

        @auth
            {{-- Floating Action Buttons - Desktop Only --}}
            <div class="hidden sm:flex fixed top-20 right-6 z-40 flex-col gap-3" x-data="{ showTooltip: null }">

                {{-- Ganti Password Button --}}
                <div class="relative group">
                    <a href="{{ route('view.change.password') }}" @mouseenter="showTooltip = 'password'"
                        @mouseleave="showTooltip = null"
                        class="flex items-center justify-center w-12 h-12 bg-white rounded-full shadow-lg hover:shadow-xl border-2 border-slate-200 hover:border-indigo-400 transition-all duration-300 hover:scale-110 float-animation">
                        <svg class="w-5 h-5 text-slate-600 group-hover:text-indigo-600 transition-colors" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </a>

                    {{-- Tooltip --}}
                    <div x-show="showTooltip === 'password'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform translate-x-2"
                        x-transition:enter-end="opacity-100 transform translate-x-0"
                        class="absolute right-full mr-3 top-1/2 -translate-y-1/2 whitespace-nowrap px-3 py-2 bg-slate-800 text-white text-xs font-medium rounded-lg shadow-lg">
                        Ganti Password
                        <div class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-full">
                            <div class="border-8 border-transparent border-l-slate-800"></div>
                        </div>
                    </div>
                </div>

                {{-- User Info Card --}}
                <div class="relative group">
                    <div @mouseenter="showTooltip = 'user'" @mouseleave="showTooltip = null"
                        class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full shadow-lg hover:shadow-xl border-2 border-white transition-all duration-300 hover:scale-110 cursor-pointer">
                        <span class="text-sm font-bold text-white">
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        </span>
                    </div>

                    {{-- Tooltip User Info --}}
                    <div x-show="showTooltip === 'user'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform translate-x-2"
                        x-transition:enter-end="opacity-100 transform translate-x-0"
                        class="absolute right-full mr-3 top-1/2 -translate-y-1/2 w-64 bg-white rounded-xl shadow-2xl border border-slate-200 overflow-hidden">

                        {{-- Header with Gradient --}}
                        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                                    <span class="text-sm font-bold text-white">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-white/80 font-medium">Login sebagai</p>
                                    <p class="text-sm font-bold text-white truncate">{{ Auth::user()->name }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Body --}}
                        <div class="px-4 py-3 space-y-2">
                            <div class="flex items-center gap-2 text-xs">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span class="text-slate-600 truncate">{{ Auth::user()->email }}</span>
                            </div>

                            <div class="flex items-center gap-2 text-xs">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span class="text-slate-600">{{ Auth::user()->divisi ?? 'N/A' }}</span>
                            </div>

                            @if (Auth::user()->role)
                                <div class="pt-2">
                                    <span
                                        class="inline-block px-2 py-1 bg-indigo-50 text-indigo-700 text-[10px] font-semibold rounded-full border border-indigo-200">
                                        {{ Auth::user()->role }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Arrow --}}
                        <div class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-full">
                            <div class="border-8 border-transparent border-l-white"></div>
                        </div>
                    </div>
                </div>

                {{-- Logout Button --}}
                <div class="relative group">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" @mouseenter="showTooltip = 'logout'" @mouseleave="showTooltip = null"
                            class="flex items-center justify-center w-12 h-12 bg-white rounded-full shadow-lg hover:shadow-xl border-2 border-slate-200 hover:border-red-400 transition-all duration-300 hover:scale-110 group">
                            <svg class="w-5 h-5 text-slate-600 group-hover:text-red-600 transition-colors" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>

                    {{-- Tooltip --}}
                    <div x-show="showTooltip === 'logout'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform translate-x-2"
                        x-transition:enter-end="opacity-100 transform translate-x-0"
                        class="absolute right-full mr-3 top-1/2 -translate-y-1/2 whitespace-nowrap px-3 py-2 bg-slate-800 text-white text-xs font-medium rounded-lg shadow-lg">
                        Keluar / Logout
                        <div class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-full">
                            <div class="border-8 border-transparent border-l-slate-800"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endauth

        {{-- Navigation --}}
        @include('layouts.navigation')

        {{-- Header --}}
        @isset($header)
            <header class="bg-white/80 backdrop-blur-lg shadow-sm border-b border-slate-200">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        {{-- Main Content - HAPUS max-width agar full width --}}
        <main>
            {{ $slot }}
        </main>

        {{-- Decorative Elements - PERBAIKI overflow --}}
        <div class="fixed top-0 left-0 w-full h-full pointer-events-none overflow-hidden -z-10">
            {{-- Gradient Orbs --}}
            <div
                class="absolute top-20 -left-20 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse">
            </div>
            <div class="absolute top-40 -right-20 w-72 h-72 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"
                style="animation-delay: 2s"></div>
            <div class="absolute -bottom-20 left-1/2 w-72 h-72 bg-pink-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"
                style="animation-delay: 4s"></div>
        </div>
    </div>

    {{-- Alpine.js for tooltips --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>

</html>
