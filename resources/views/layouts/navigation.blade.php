<nav x-data="{ open: false, compoundOpen: false }"
    class="bg-white/80 backdrop-blur-lg border-b border-slate-200/60 sticky top-0 z-50 shadow-sm">

    @php
        $user = Auth::user();
        $homeRoute = '#';
        $isActive = false;

        if (request()->is('fh*')) {
            $homeRoute = route('fh.index');
            $isActive = request()->is('fh*');
        } elseif (request()->is('ga*')) {
            $homeRoute = route('ga.index');
            $isActive = request()->is('ga*');
        } elseif (request()->is('eng*')) {
            $homeRoute = route('eng.index');
            $isActive = request()->is('eng*');
        } else {
            if ($user->divisi === 'Facility' || str_contains($user->role, 'fh.')) {
                $homeRoute = route('fh.index');
            } elseif ($user->divisi === 'General Affair' || str_contains($user->role, 'ga.')) {
                $homeRoute = route('ga.index');
            } elseif ($user->divisi === 'Engineering' || str_contains($user->role, 'eng.')) {
                $homeRoute = route('eng.index');
            }
        }

        $isCompoundActive = request()->routeIs('eng.compound.*');
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            {{-- LEFT: Logo + Nav --}}
            <div class="flex items-center">

                {{-- Logo --}}
                <div class="shrink-0 flex items-center group mr-8">
                    <a href="{{ $homeRoute }}"
                        class="flex items-center gap-3 transition-transform duration-200 hover:scale-105">
                        <div class="relative">
                            <div
                                class="absolute inset-0 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg
                                        blur-lg opacity-0 group-hover:opacity-30 transition-opacity duration-300">
                            </div>
                            <x-application-logo
                                class="relative block h-9 w-auto fill-current text-slate-800
                                       group-hover:text-indigo-600 transition-colors duration-200" />
                        </div>
                    </a>
                </div>

                {{-- Desktop Nav Links --}}
                <div class="hidden sm:flex items-center gap-1">

                    {{-- Home --}}
                    <x-nav-link :href="$homeRoute" :active="$isActive">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Home
                        </span>
                    </x-nav-link>

                    {{-- User Management --}}
                    @can('user.manage')
                        <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                User Management
                            </span>
                        </x-nav-link>
                    @endcan

                    {{-- GA Admin Dashboard --}}
                    @if (in_array(Auth::user()->role, ['ga.admin', 'super.ga.admin']))
                        <x-nav-link :href="route('ga.dashboard')" :active="request()->routeIs('ga.dashboard')">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                Admin Dashboard
                            </span>
                        </x-nav-link>
                    @endif

                    {{-- FH Admin Dashboard --}}
                    @if (request()->routeIs('fh.*') && $user->role === 'fh.admin')
                        <x-nav-link :href="route('fh.dashboard')" :active="request()->routeIs('fh.dashboard')">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                Admin Dashboard
                            </span>
                        </x-nav-link>
                    @endif

                    {{-- ── Compound Dropdown ─────────────────────── --}}
                    @if (auth()->check() && auth()->user()->role === 'eng.admin')
                        <div class="relative" @click.outside="compoundOpen = false">

                            {{-- Trigger button --}}
                            <button @click="compoundOpen = !compoundOpen"
                                class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold
                                       transition-colors duration-150
                                       {{ $isCompoundActive
                                           ? 'text-indigo-700 bg-indigo-50'
                                           : 'text-slate-600 hover:text-slate-800 hover:bg-slate-100' }}">

                                {{-- Icon --}}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>

                                Compound

                                {{-- Active dot --}}
                                @if ($isCompoundActive)
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                @endif

                                {{-- Chevron --}}
                                <svg class="w-3.5 h-3.5 transition-transform duration-200"
                                    :class="compoundOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            {{-- Dropdown Panel --}}
                            <div x-show="compoundOpen" x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                x-transition:leave-end="opacity-0 scale-95 -translate-y-1" style="display:none;"
                                class="absolute left-0 top-full mt-2 w-56 bg-white rounded-xl shadow-lg
                                       border border-slate-200/80 overflow-hidden origin-top-left">

                                {{-- Header dropdown --}}
                                <div class="px-3 py-2.5 bg-slate-50 border-b border-slate-100">
                                    <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">
                                        Compound Menu
                                    </p>
                                </div>

                                <div class="p-1.5 space-y-0.5">

                                    {{-- Statistik --}}
                                    <a href="{{ route('eng.compound.stats') }}" @click="compoundOpen = false"
                                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold
                                               transition-colors duration-100
                                               {{ request()->routeIs('eng.compound.stats')
                                                   ? 'bg-blue-600 text-white'
                                                   : 'text-slate-600 hover:bg-blue-50 hover:text-blue-700' }}">
                                        <span
                                            class="flex items-center justify-center w-7 h-7 rounded-lg flex-shrink-0
                                                     {{ request()->routeIs('eng.compound.stats') ? 'bg-blue-500/30' : 'bg-slate-100' }}">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                            </svg>
                                        </span>
                                        Statistik Compound
                                        @if (request()->routeIs('eng.compound.stats'))
                                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-white/70"></span>
                                        @endif
                                    </a>

                                    {{-- Master Standard --}}
                                    <a href="{{ route('eng.compound.standards') }}" @click="compoundOpen = false"
                                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold
                                               transition-colors duration-100
                                               {{ request()->routeIs('eng.compound.standards')
                                                   ? 'bg-indigo-600 text-white'
                                                   : 'text-slate-600 hover:bg-indigo-50 hover:text-indigo-700' }}">
                                        <span
                                            class="flex items-center justify-center w-7 h-7 rounded-lg flex-shrink-0
                                                     {{ request()->routeIs('eng.compound.standards') ? 'bg-indigo-500/30' : 'bg-slate-100' }}">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </span>
                                        Master Standard
                                        @if (request()->routeIs('eng.compound.standards'))
                                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-white/70"></span>
                                        @endif
                                    </a>

                                    {{-- Laporan Compound --}}
                                    <a href="{{ route('eng.compound.report') }}" @click="compoundOpen = false"
                                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold
                                               transition-colors duration-100
                                               {{ request()->routeIs('eng.compound.report')
                                                   ? 'bg-emerald-600 text-white'
                                                   : 'text-slate-600 hover:bg-emerald-50 hover:text-emerald-700' }}">
                                        <span
                                            class="flex items-center justify-center w-7 h-7 rounded-lg flex-shrink-0
                                                     {{ request()->routeIs('eng.compound.report') ? 'bg-emerald-500/30' : 'bg-slate-100' }}">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </span>
                                        Laporan Compound
                                        @if (request()->routeIs('eng.compound.report'))
                                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-white/70"></span>
                                        @endif
                                    </a>

                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>

            {{-- RIGHT: User Badge --}}
            <div class="hidden sm:flex items-center gap-4">
                <div
                    class="flex items-center gap-3 px-4 py-2 bg-gradient-to-br from-slate-50 to-slate-100
                            border border-slate-200 rounded-full shadow-sm">
                    <div
                        class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600
                                flex items-center justify-center shadow-md">
                        <span class="text-xs font-bold text-white">
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        </span>
                    </div>
                    <div class="hidden lg:block">
                        <div class="text-sm font-semibold text-slate-800 leading-none">{{ Auth::user()->name }}</div>
                        <div class="text-[10px] text-slate-500 mt-0.5">{{ Auth::user()->divisi }}</div>
                    </div>
                </div>
            </div>

            {{-- Mobile Menu Button --}}
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = !open"
                    class="inline-flex items-center justify-center p-2 rounded-xl text-slate-400
                           hover:text-slate-600 hover:bg-slate-100 transition-colors duration-150">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- ── Mobile Menu ──────────────────────────────────── --}}
    <div :class="{ 'block': open, 'hidden': !open }" class="hidden sm:hidden"
        x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0">

        <div class="pt-2 pb-3 space-y-1 bg-slate-50/50 px-3">

            <x-responsive-nav-link :href="$homeRoute" :active="$isActive">
                <span class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Home
                </span>
            </x-responsive-nav-link>

            @can('user.manage')
                <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        User Management
                    </span>
                </x-responsive-nav-link>
            @endcan

            {{-- Mobile Compound Links --}}
            @if (auth()->check() && auth()->user()->role === 'eng.admin')
                <div class="pt-2 pb-1 px-1">
                    <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Compound</p>
                </div>

                <x-responsive-nav-link :href="route('eng.compound.stats')" :active="request()->routeIs('eng.compound.stats')">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                        </svg>
                        Statistik Compound
                    </span>
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('eng.compound.standards')" :active="request()->routeIs('eng.compound.standards')">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Master Standard
                    </span>
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('eng.compound.report')" :active="request()->routeIs('eng.compound.report')">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Laporan Compound
                    </span>
                </x-responsive-nav-link>
            @endif
        </div>

        {{-- Mobile User Info --}}
        <div class="pt-4 pb-1 border-t border-slate-200 bg-white">
            <div class="px-4">
                <div class="flex items-center gap-3 mb-3">
                    <div
                        class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600
                                flex items-center justify-center shadow-lg">
                        <span class="text-sm font-bold text-white">
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        </span>
                    </div>
                    <div>
                        <div class="font-semibold text-base text-slate-800">{{ Auth::user()->name }}</div>
                        <div class="text-sm text-slate-500">{{ Auth::user()->email }}</div>
                        <div
                            class="inline-block mt-1 px-2 py-0.5 bg-indigo-50 text-indigo-700 text-xs
                                    font-medium rounded-full border border-indigo-200">
                            {{ Auth::user()->divisi }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3 space-y-1 px-3 pb-3">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();"
                        class="!text-red-600 hover:!bg-red-50 hover:!text-red-700">
                        <span class="flex items-center gap-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Log Out
                        </span>
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
