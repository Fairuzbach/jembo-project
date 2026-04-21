<x-guest-layout>
@php
    $isEng = in_array($dept, ['eng', 'wo-eng']);
    $isGa  = in_array($dept, ['ga', 'wo-ga']);
    $isFh  = $dept === 'wo-fh';
    $isDefault = !$isEng && !$isGa && !$isFh;
@endphp

{{-- Top accent bar --}}
<div class="h-1 -mx-6 -mt-4 mb-6
    @if($isEng) bg-blue-600
    @elseif($isGa) bg-emerald-600
    @elseif($isFh) bg-violet-600
    @else bg-gradient-to-r from-blue-600 via-emerald-500 to-violet-600
    @endif">
</div>

{{-- Brand row --}}
<div class="flex items-center gap-3 mb-6">

    {{-- Tombol back: hanya tampil saat sudah di halaman departemen tertentu --}}
    @if(!$isDefault)
        <a href="{{ url('/login') }}"
           class="inline-flex items-center gap-1 text-[11px] font-semibold text-gray-400
               px-2.5 py-1.5 rounded-lg border border-gray-200 bg-gray-50
               hover:text-gray-700 hover:border-gray-300 hover:bg-white
               transition-all duration-150 flex-shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali
        </a>
    @endif

    {{-- Brand icon --}}
    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0
        @if($isEng) bg-blue-50
        @elseif($isGa) bg-emerald-50
        @elseif($isFh) bg-violet-50
        @else bg-slate-100
        @endif">
        @if($isEng)
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        @elseif($isGa)
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        @elseif($isFh)
            <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
        @else
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        @endif
    </div>

    {{-- Brand text --}}
    <div class="min-w-0">
        <p class="text-sm font-semibold text-gray-900 leading-tight tracking-tight">
            @if($isEng) Engineering
            @elseif($isGa) General Affair
            @elseif($isFh) Facility
            @else JEMBO Work Order
            @endif
        </p>
        <p class="text-xs text-gray-400 mt-0.5">
            @if($isEng) Improvement Order System
            @elseif($isGa) Work Order GA System
            @elseif($isFh) Work Order Facility System
            @else Sistem manajemen work order
            @endif
        </p>
    </div>

    {{-- Dept badge --}}
    @if(!$isDefault)
        <span class="ml-auto text-[10px] font-bold tracking-widest uppercase px-2.5 py-1 rounded-full flex-shrink-0
            @if($isEng) bg-blue-50 text-blue-700
            @elseif($isGa) bg-emerald-50 text-emerald-700
            @else bg-violet-50 text-violet-700
            @endif">
            {{ $isEng ? 'ENG' : ($isGa ? 'GA' : 'FH') }}
        </span>
    @endif

</div>

{{-- Heading --}}
<div class="mb-5 text-center">
    <h2 class="text-[22px] font-bold text-gray-900 tracking-tight">Selamat datang</h2>
    <p class="text-xs text-gray-400 mt-0.5">Masuk untuk melanjutkan ke sistem</p>
</div>

{{-- Department picker (hanya jika belum pilih) --}}
@if($isDefault)
    <div class="mb-5">
        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-2.5 text-center">Pilih departemen</p>
        <div class="grid grid-cols-3 gap-1.5">
            <a href="{{ url('/login/wo-eng') }}"
               class="flex flex-col items-center gap-1.5 px-2 py-3 rounded-xl border-[1.5px] border-slate-200 bg-slate-50 hover:border-blue-300 hover:bg-blue-50 transition-all duration-150 group">
                <div class="w-7 h-7 rounded-lg bg-blue-600 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-semibold text-blue-700 text-center leading-tight">Engineering</span>
            </a>

            <a href="{{ url('/login/wo-ga') }}"
               class="flex flex-col items-center gap-1.5 px-2 py-3 rounded-xl border-[1.5px] border-slate-200 bg-slate-50 hover:border-emerald-300 hover:bg-emerald-50 transition-all duration-150 group">
                <div class="w-7 h-7 rounded-lg bg-emerald-600 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <span class="text-[10px] font-semibold text-emerald-700 text-center leading-tight">General Affair</span>
            </a>

            <a href="{{ url('/login/wo-fh') }}"
               class="flex flex-col items-center gap-1.5 px-2 py-3 rounded-xl border-[1.5px] border-slate-200 bg-slate-50 hover:border-violet-300 hover:bg-violet-50 transition-all duration-150 group">
                <div class="w-7 h-7 rounded-lg bg-violet-600 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                </div>
                <span class="text-[10px] font-semibold text-violet-700 text-center leading-tight">Facility</span>
            </a>
        </div>
    </div>
@endif

{{-- Status & Error --}}
<x-auth-session-status class="mb-4" :status="session('status')" />

@if(session('error'))
    <div class="flex items-start gap-2 bg-red-50 border border-red-100 text-red-600 rounded-xl px-3.5 py-3 mb-4 text-xs">
        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
        <span>{{ session('error') }}</span>
    </div>
@endif

{{-- Form --}}
<form method="POST" action="{{ route('login') }}" x-data="{ loading: false, showPass: false }" @submit="loading = true" class="space-y-3.5">
    @csrf

    {{-- NIK --}}
    <div x-data="{ focused: false }">
        <label for="nik" class="block text-[11px] font-semibold text-gray-500 uppercase tracking-widest mb-1.5">NIK</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                 :class="focused ? '{{ $isEng ? 'text-blue-500' : ($isGa ? 'text-emerald-500' : ($isFh ? 'text-violet-500' : 'text-gray-900')) }}' : 'text-gray-300'">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                </svg>
            </div>
            <x-text-input id="nik"
                @focus="focused = true" @blur="focused = false"
                class="block w-full pl-9 pr-4 py-2.5 text-sm rounded-xl
                    border-[1.5px] border-gray-200 bg-gray-50
                    hover:border-gray-300 hover:bg-white
                    focus:bg-white focus:ring-0 focus:outline-none transition-all duration-150
                    {{ $isEng ? 'focus:border-blue-500' : ($isGa ? 'focus:border-emerald-500' : ($isFh ? 'focus:border-violet-500' : 'focus:border-gray-900')) }}"
                type="text" name="nik" :value="old('nik')"
                required autofocus autocomplete="username"
                placeholder="Contoh: 9001"/>
        </div>
        <x-input-error :messages="$errors->get('nik')" class="mt-1.5"/>
    </div>

    {{-- Password --}}
    <div x-data="{ show: false, focused: false }">
        <label for="password" class="block text-[11px] font-semibold text-gray-500 uppercase tracking-widest mb-1.5">Password</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                 :class="focused ? '{{ $isEng ? 'text-blue-500' : ($isGa ? 'text-emerald-500' : ($isFh ? 'text-violet-500' : 'text-gray-900')) }}' : 'text-gray-300'">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                </svg>
            </div>
            <x-text-input id="password"
                @focus="focused = true" @blur="focused = false"
                class="block w-full pl-9 pr-10 py-2.5 text-sm rounded-xl
                    border-[1.5px] border-gray-200 bg-gray-50
                    hover:border-gray-300 hover:bg-white
                    focus:bg-white focus:ring-0 focus:outline-none transition-all duration-150
                    {{ $isEng ? 'focus:border-blue-500' : ($isGa ? 'focus:border-emerald-500' : ($isFh ? 'focus:border-violet-500' : 'focus:border-gray-900')) }}"
                ::type="show ? 'text' : 'password'"
                name="password" required autocomplete="current-password"
                placeholder="••••••••"/>
            <button type="button" @click="show = !show"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-300 hover:text-gray-500 transition-colors duration-150 focus:outline-none">
                <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <svg x-show="show" style="display:none" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                </svg>
            </button>
        </div>
        <x-input-error :messages="$errors->get('password')" class="mt-1.5"/>
    </div>

    {{-- Remember & Lupa Password --}}
    <div class="flex items-center justify-between pt-0.5">
        <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
            <input id="remember_me" type="checkbox" name="remember"
                class="rounded border-gray-300 w-3.5 h-3.5 cursor-pointer
                    {{ $isEng ? 'text-blue-600 focus:ring-blue-500' : ($isGa ? 'text-emerald-600 focus:ring-emerald-500' : ($isFh ? 'text-violet-600 focus:ring-violet-500' : 'text-gray-900 focus:ring-gray-700')) }}"/>
            <span class="text-xs text-gray-500">{{ __('Ingat saya') }}</span>
        </label>

        @php
            $waNumber = '6285156469296';
            $waMsg = 'Halo Admin, saya lupa password akun WO JEMBO saya. Berikut data untuk reset password:%0A%0ANama Lengkap : %0ANIK : %0ADepartemen : %0A%0AMohon bantuannya. Terima kasih.';
            $waLink = "https://wa.me/{$waNumber}?text={$waMsg}";
        @endphp

        <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer"
           class="text-xs text-gray-400 hover:text-gray-700 border-b border-gray-200 hover:border-gray-400 transition-colors duration-150 pb-px">
            Lupa password?
        </a>
    </div>

    {{-- Submit --}}
    <div class="pt-1">
        <button type="submit" :disabled="loading"
            class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-semibold text-white
                transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed tracking-tight
                {{ $isEng
                    ? 'bg-blue-600 hover:bg-blue-700 active:scale-[0.98]'
                    : ($isGa
                        ? 'bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98]'
                        : ($isFh
                            ? 'bg-violet-600 hover:bg-violet-700 active:scale-[0.98]'
                            : 'bg-gray-900 hover:bg-gray-800 active:scale-[0.98]')) }}">
            <svg x-show="loading" class="animate-spin h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
            </svg>
            <span x-text="loading ? 'Memproses...' : 'Masuk sekarang'"></span>
            <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
        </button>
    </div>

    {{-- Footer --}}
    <div class="flex items-center justify-center gap-1.5 pt-4 border-t border-gray-100">
        <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
        <span class="text-[11px] text-gray-300">Koneksi aman &amp; terenkripsi</span>
    </div>
</form>
</x-guest-layout>