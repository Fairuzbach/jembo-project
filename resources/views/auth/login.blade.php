<x-guest-layout>

    {{-- Header Departemen --}}
    @php
        $isEng = in_array($dept, ['eng', 'wo-eng']);
        $isGa = in_array($dept, ['ga', 'wo-ga']);
        $isFh = $dept === 'wo-fh';
        $isDefault = !$isEng && !$isGa && !$isFh;

        $headerBg = $isEng
            ? 'bg-blue-700 border-blue-400'
            : ($isGa
                ? 'bg-emerald-700 border-emerald-400'
                : ($isFh
                    ? 'bg-violet-700 border-violet-400'
                    : 'bg-slate-700 border-slate-500'));
    @endphp

    <div class="text-center mb-5 -mx-6 -mt-4 px-6 pt-6 pb-5 border-b-4 {{ $headerBg }}">

        <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-white/20 text-white mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
            </svg>
        </div>

        @if ($isEng)
            <h2 class="text-lg font-black text-white">Engineering Login</h2>
            <p class="text-blue-200 text-xs mt-0.5">Engineering Improvement Order</p>
        @elseif ($isGa)
            <h2 class="text-lg font-black text-white">General Affair Login</h2>
            <p class="text-emerald-200 text-xs mt-0.5">Work Order GA System</p>
        @elseif ($isFh)
            <h2 class="text-lg font-black text-white">Facility WO Login</h2>
            <p class="text-violet-200 text-xs mt-0.5">Work Order Facility System</p>
        @else
            <h2 class="text-lg font-black text-white">JEMBO WO Login</h2>
            <p class="text-slate-300 text-xs mt-0.5">Silakan pilih departemen Anda</p>
        @endif
    </div>

    {{-- Judul Sambutan --}}
    <div class="text-center mb-5">
        <h3 class="text-xl font-black bg-gradient-to-r from-red-600 to-orange-500 bg-clip-text text-transparent">
            Selamat Datang!
        </h3>
        <p class="text-gray-400 text-xs mt-1">Silakan masuk untuk melanjutkan akses ke sistem</p>
    </div>

    {{-- Pilih Departemen (hanya tampil jika tidak ada dept) --}}
    @if ($isDefault)
        <div class="mb-5">
            <p class="text-center text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">
                Pilih Departemen
            </p>
            <div class="grid grid-cols-3 gap-2">
                <a href="{{ url('/login/wo-eng') }}"
                    class="flex flex-col items-center gap-1.5 px-2 py-3 rounded-xl border-2 border-blue-100 bg-blue-50 hover:bg-blue-100 hover:border-blue-300 transition-all duration-200 group">
                    <div
                        class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-blue-700 text-center leading-tight">Engineering</span>
                </a>

                <a href="{{ url('/login/wo-ga') }}"
                    class="flex flex-col items-center gap-1.5 px-2 py-3 rounded-xl border-2 border-emerald-100 bg-emerald-50 hover:bg-emerald-100 hover:border-emerald-300 transition-all duration-200 group">
                    <div
                        class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-emerald-700 text-center leading-tight">General Affair</span>
                </a>

                <a href="{{ url('/login/wo-fh') }}"
                    class="flex flex-col items-center gap-1.5 px-2 py-3 rounded-xl border-2 border-violet-100 bg-violet-50 hover:bg-violet-100 hover:border-violet-300 transition-all duration-200 group">
                    <div
                        class="w-8 h-8 rounded-lg bg-violet-600 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-violet-700 text-center leading-tight">Facility</span>
                </a>
            </div>
        </div>

        <div class="flex items-center gap-2 mb-5">
            <div class="h-px flex-1 bg-gray-100"></div>
            <span class="text-xs text-gray-400">atau masuk langsung</span>
            <div class="h-px flex-1 bg-gray-100"></div>
        </div>
    @endif

    {{-- Pesan Status & Error --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (session('error'))
        <div
            class="flex items-start gap-2.5 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm">
            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                    clip-rule="evenodd" />
            </svg>
            <span><strong>Perhatian!</strong> {{ session('error') }}</span>
        </div>
    @endif

    {{-- Form --}}
    <form method="POST" action="{{ route('login') }}" x-data="{ loading: false }" @submit="loading = true"
        class="space-y-4">
        @csrf

        {{-- NIK --}}
        <div x-data="{ focused: false }">
            <x-input-label for="nik" :value="__('NIK')" class="text-sm font-bold text-gray-700 mb-1.5 block" />
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none transition-colors duration-200"
                    :class="focused ? 'text-red-500' : 'text-gray-400'">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <x-text-input id="nik" @focus="focused = true" @blur="focused = false"
                    class="block w-full pl-9 pr-4 py-2.5 text-sm rounded-xl
                        border-2 border-gray-200 bg-gray-50
                        hover:border-gray-300
                        focus:border-red-500 focus:bg-white focus:ring-4 focus:ring-red-500/10
                        transition-all duration-200"
                    type="text" name="nik" :value="old('nik')" required autofocus autocomplete="username"
                    placeholder="Contoh: 9001" />
            </div>
            <x-input-error :messages="$errors->get('nik')" class="mt-1.5" />
        </div>

        {{-- Password --}}
        <div x-data="{ show: false, focused: false }">
            <x-input-label for="password" :value="__('Password')" class="text-sm font-bold text-gray-700 mb-1.5 block" />
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none transition-colors duration-200"
                    :class="focused ? 'text-red-500' : 'text-gray-400'">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <x-text-input id="password" @focus="focused = true" @blur="focused = false"
                    class="block w-full pl-9 pr-10 py-2.5 text-sm rounded-xl
                        border-2 border-gray-200 bg-gray-50
                        hover:border-gray-300
                        focus:border-red-500 focus:bg-white focus:ring-4 focus:ring-red-500/10
                        transition-all duration-200"
                    ::type="show ? 'text' : 'password'" name="password" required autocomplete="current-password"
                    placeholder="••••••••" />
                <button type="button" @click="show = !show"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500 transition-colors duration-200 cursor-pointer focus:outline-none">
                    <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="show" style="display:none" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        {{-- Remember Me & Lupa Password --}}
        <div class="flex items-center justify-between pt-1">
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer group">
                <input id="remember_me" type="checkbox" name="remember"
                    class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500 w-4 h-4 cursor-pointer" />
                <span class="text-sm text-gray-600 group-hover:text-red-600 transition-colors duration-200">
                    {{ __('Ingat saya') }}
                </span>
            </label>

            @php
                $waNumber = '6285156469296';
                $waMessage =
                    'Halo Admin, saya lupa password akun WO JEMBO saya. Berikut data untuk reset password:%0A%0A' .
                    'Nama Lengkap : %0A' .
                    'NIK : %0A' .
                    'Departemen : %0A%0A' .
                    'Mohon bantuannya. Terima kasih.';
                $waLink = "https://wa.me/{$waNumber}?text={$waMessage}";
            @endphp

            <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer"
                class="text-sm text-slate-500 hover:text-red-600 underline underline-offset-2 transition-colors duration-200">
                Lupa Password?
            </a>
        </div>

        {{-- Tombol Login --}}
        <div class="pt-1">
            <button type="submit" :disabled="loading"
                class="w-full relative flex justify-center items-center gap-2
                    py-2.5 px-4 rounded-xl text-sm font-bold text-white
                    bg-gradient-to-r from-red-600 via-red-500 to-orange-500
                    hover:from-red-700 hover:via-red-600 hover:to-orange-600
                    focus:outline-none focus:ring-4 focus:ring-red-500/40
                    transform transition-all duration-200
                    hover:-translate-y-0.5 hover:shadow-lg hover:shadow-red-500/30
                    active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed
                    disabled:transform-none overflow-hidden group">

                <span
                    class="absolute inset-0 w-1/3 bg-gradient-to-r from-transparent via-white/20 to-transparent
                    skew-x-12 -translate-x-full group-hover:translate-x-[350%] transition-transform duration-700 pointer-events-none"></span>

                <svg x-show="loading" class="animate-spin h-4 w-4 text-white flex-shrink-0"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>

                <span x-text="loading ? 'Memproses...' : 'Masuk Sekarang'"></span>

                <svg x-show="!loading" class="w-4 h-4 group-hover:translate-x-1 transition-transform duration-200"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
            </button>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-center gap-2 pt-3 border-t border-gray-100">
            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <span class="text-xs text-gray-400 tracking-wide">Koneksi Aman &amp; Terenkripsi</span>
        </div>
    </form>

</x-guest-layout>
