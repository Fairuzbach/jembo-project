<nav x-data="{ open: false }"
    class="bg-white/80 backdrop-blur-lg border-b border-slate-200/60 sticky top-0 z-50 shadow-sm">

    {{-- LOGIC PHP: Tentukan Arah Home --}}
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
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                {{-- Logo Section with Animation --}}
                <div class="shrink-0 flex items-center group">
                    <a href="{{ $homeRoute }}"
                        class="flex items-center gap-3 transition-transform duration-200 hover:scale-105">
                        <div class="relative">
                            {{-- Glow effect --}}
                            <div
                                class="absolute inset-0 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg blur-lg opacity-0 group-hover:opacity-30 transition-opacity duration-300">
                            </div>

                            <x-application-logo
                                class="relative block h-9 w-auto fill-current text-slate-800 group-hover:text-indigo-600 transition-colors duration-200" />
                        </div>
                    </a>
                </div>

                {{-- Navigation Links --}}
                <div class="hidden space-x-2 sm:-my-px sm:ms-10 sm:flex items-center">
                    <x-nav-link :href="$homeRoute" :active="$isActive" class="group relative">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            {{ __('Home') }}
                        </span>
                    </x-nav-link>

                    @can('user.manage')
                        <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" class="group relative">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                {{ __('User Management') }}
                            </span>
                        </x-nav-link>
                    @endcan

                    @if (in_array(Auth::user()->role, ['ga.admin', 'super.ga.admin']))
                        <x-nav-link :href="route('ga.dashboard')" :active="request()->routeIs('ga.dashboard')" class="group relative">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                {{ __('Admin Dashboard') }}
                            </span>
                        </x-nav-link>
                    @endif

                    @if (request()->routeIs('fh.*') && $user->role === 'fh.admin')
                        <x-nav-link :href="route('fh.dashboard')" :active="request()->routeIs('fh.dashboard')" class="group relative">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                {{ __('Admin Dashboard') }}
                            </span>
                        </x-nav-link>
                    @endif
                </div>
            </div>

            {{-- Right Side - User Info (Desktop Only) --}}
            <div class="hidden sm:flex items-center gap-4">
                {{-- User Badge --}}
                <div
                    class="flex items-center gap-3 px-4 py-2 bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 rounded-full shadow-sm">
                    {{-- Avatar --}}
                    <div
                        class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-md">
                        <span class="text-xs font-bold text-white">
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        </span>
                    </div>

                    {{-- User Info --}}
                    <div class="hidden lg:block">
                        <div class="text-sm font-semibold text-slate-800 leading-none">
                            {{ Auth::user()->name }}
                        </div>
                        <div class="text-[10px] text-slate-500 mt-0.5">
                            {{ Auth::user()->divisi }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Mobile Menu Button --}}
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 focus:outline-none focus:bg-slate-100 focus:text-slate-600 transition-all duration-200">
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

    {{-- Mobile Menu --}}
    <div :class="{ 'block': open, 'hidden': !open }" class="hidden sm:hidden"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform -translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0">

        {{-- Navigation Links --}}
        <div class="pt-2 pb-3 space-y-1 bg-slate-50/50">
            <x-responsive-nav-link :href="$homeRoute" :active="$isActive">
                <span class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    {{ __('Home') }}
                </span>
            </x-responsive-nav-link>

            @can('user.manage')
                <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        {{ __('User Management') }}
                    </span>
                </x-responsive-nav-link>
            @endcan

            @if (in_array(Auth::user()->role, ['ga.admin', 'super.ga.admin']))
                <x-responsive-nav-link :href="route('ga.dashboard')" :active="request()->routeIs('ga.dashboard')">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        {{ __('Admin Dashboard') }}
                    </span>
                </x-responsive-nav-link>
            @endif

            @if (request()->routeIs('fh.*') && $user->role === 'fh.admin')
                <x-responsive-nav-link :href="route('fh.dashboard')" :active="request()->routeIs('fh.dashboard')">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        {{ __('Admin Dashboard') }}
                    </span>
                </x-responsive-nav-link>
            @endif
        </div>

        {{-- User Info Section --}}
        <div class="pt-4 pb-1 border-t border-slate-200 bg-white">
            <div class="px-4">
                <div class="flex items-center gap-3 mb-3">
                    {{-- Avatar --}}
                    <div
                        class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg">
                        <span class="text-sm font-bold text-white">
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        </span>
                    </div>

                    {{-- Info --}}
                    <div>
                        <div class="font-semibold text-base text-slate-800">{{ Auth::user()->name }}</div>
                        <div class="text-sm text-slate-500">{{ Auth::user()->email }}</div>
                        <div
                            class="inline-block mt-1 px-2 py-0.5 bg-indigo-50 text-indigo-700 text-xs font-medium rounded-full border border-indigo-200">
                            {{ Auth::user()->divisi }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                {{-- Logout Button --}}
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
                            {{ __('Log Out') }}
                        </span>
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
