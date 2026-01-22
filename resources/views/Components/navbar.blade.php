{{-- A. NAVIGASI ATAS (Login/Logout Check) --}}
<div class="absolute top-6 right-6 z-50">
    @auth
        {{-- Tampilan Jika User SUDAH LOGIN --}}
        <div x-data="{ open: false }" class="relative" @click.outside="open = false">
            <button @click="open = !open" @click.away="open = false"
                class="flex items-center gap-2 bg-white/90 backdrop-blur-sm px-4 py-2 rounded-full shadow-sm hover:shadow-md transition-all border border-slate-200">
                <div
                    class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xs">
                    {{ substr(Auth::user()->name, 0, 2) }}
                </div>
                <span class="text-sm font-medium text-slate-700 hidden sm:block">{{ Auth::user()->name }}</span>
                <svg class="w-4 h-4 text-slate-500 transition-transform duration-200" :class="{ 'rotate-180': open }"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            {{-- Dropdown Logout --}}
            <div x-show="open" x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-slate-100 py-1"
                style="display: none;">
                <div class="px-4 py-2 border-b border-slate-100">
                    <p class="text-xs text-slate-500">Login sebagai:</p>
                    <p class="text-xs font-bold text-slate-800 truncate">{{ Auth::user()->divisi ?? 'User' }}</p>
                </div>
                {{-- Link Dashboard (Opsional) --}}
                <a href="{{ route('dashboard') }}"
                    class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                    Dashboard
                </a>
                <a href="{{ route('profile.edit') }}"
                    class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                    Ganti Password
                </a>
                {{-- Tombol Logout --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    @else
        {{-- Tampilan Jika User BELUM LOGIN (Tamu) --}}
        <a href="{{ route('login') }}"
            class="bg-white/90 backdrop-blur-sm px-5 py-2.5 rounded-full font-semibold text-sm text-indigo-600 shadow-sm hover:shadow-md hover:bg-indigo-50 transition-all border border-indigo-100">
            Masuk
        </a>
        <a href="{{ route('register') }}"
            class="bg-white/90 backdrop-blur-sm px-5 py-2.5 rounded-full font-semibold text-sm text-indigo-600 shadow-sm hover:shadow-md hover:bg-indigo-50 transition-all border border-indigo-100">
            Daftar
        </a>
    @endauth
</div>
