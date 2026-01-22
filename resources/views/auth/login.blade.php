<x-guest-layout>
    {{-- Container Utama dengan Animasi Masuk --}}
    <div x-data="{ loading: false }" x-init="$el.classList.remove('opacity-0', 'translate-y-4')"
        class="opacity-0 translate-y-4 transition-all duration-700 ease-out">

        {{-- Header dengan Gradient Text --}}
        <div class="mb-10 text-center relative">
            {{-- Decorative circles --}}
            <div class="absolute -top-8 -left-8 w-20 h-20 bg-red-100 rounded-full opacity-50 blur-2xl"></div>
            <div class="absolute -top-4 -right-4 w-16 h-16 bg-blue-100 rounded-full opacity-50 blur-xl"></div>

            <div
                class="relative inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-red-500 to-red-700 text-white mb-6 shadow-2xl shadow-red-500/30 transform hover:scale-110 hover:rotate-6 transition-all duration-500">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1">
                    </path>
                </svg>
            </div>

            <h2 class="text-4xl font-black tracking-tight mb-2">
                <span class="bg-gradient-to-r from-red-600 via-red-500 to-orange-500 bg-clip-text text-transparent">
                    Selamat Datang!
                </span>
            </h2>
            <p class="text-gray-600 text-base font-medium">Silakan masuk untuk melanjutkan akses ke sistem</p>

            {{-- Decorative line --}}
            <div class="flex items-center justify-center mt-4 gap-2">
                <div class="h-1 w-8 bg-gradient-to-r from-transparent to-red-500 rounded-full"></div>
                <div class="h-1 w-12 bg-red-500 rounded-full"></div>
                <div class="h-1 w-8 bg-gradient-to-l from-transparent to-red-500 rounded-full"></div>
            </div>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" @submit="loading = true" class="space-y-6">
            @csrf

            {{-- Input NIK dengan Animasi --}}
            <div class="group" x-data="{ focused: false }">
                <x-input-label for="nik" :value="__('NIK')"
                    class="font-bold text-gray-700 mb-2 transition-colors" />

                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-all duration-300"
                        :class="focused ? 'text-red-500 scale-110' : 'text-gray-400'">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>

                    <x-text-input id="nik" @focus="focused = true" @blur="focused = false"
                        class="block w-full pl-12 pr-4 py-3 rounded-xl border-2 border-gray-200 focus:ring-4 focus:ring-red-500/20 focus:border-red-500 transition-all duration-300 hover:border-red-300 bg-gray-50 focus:bg-white"
                        type="text" name="nik" :value="old('nik')" required autofocus autocomplete="username"
                        placeholder="Contoh: 9001" />

                    {{-- Animated border --}}
                    <div class="absolute bottom-0 left-0 h-0.5 bg-gradient-to-r from-red-500 to-orange-500 transition-all duration-300 rounded-full"
                        :class="focused ? 'w-full' : 'w-0'"></div>
                </div>

                <x-input-error :messages="$errors->get('nik')" class="mt-2" />
            </div>

            {{-- Input Password dengan Toggle Modern --}}
            <div class="group" x-data="{ show: false, focused: false }">
                <x-input-label for="password" :value="__('Password')"
                    class="font-bold text-gray-700 mb-2 transition-colors" />

                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-all duration-300"
                        :class="focused ? 'text-red-500 scale-110' : 'text-gray-400'">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>

                    <x-text-input id="password" @focus="focused = true" @blur="focused = false"
                        class="block w-full pl-12 pr-12 py-3 rounded-xl border-2 border-gray-200 focus:ring-4 focus:ring-red-500/20 focus:border-red-500 transition-all duration-300 hover:border-red-300 bg-gray-50 focus:bg-white"
                        ::type="show ? 'text' : 'password'" name="password" required autocomplete="current-password"
                        placeholder="••••••••" />

                    <button type="button" @click="show = !show"
                        class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-red-600 focus:outline-none transition-all duration-300 cursor-pointer group/eye">
                        <svg x-show="!show" class="h-5 w-5 group-hover/eye:scale-110 transition-transform"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg x-show="show" style="display: none;"
                            class="h-5 w-5 group-hover/eye:scale-110 transition-transform" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        </svg>
                    </button>

                    {{-- Animated border --}}
                    <div class="absolute bottom-0 left-0 h-0.5 bg-gradient-to-r from-red-500 to-orange-500 transition-all duration-300 rounded-full"
                        :class="focused ? 'w-full' : 'w-0'"></div>
                </div>

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            {{-- Remember Me & Links --}}
            <div class="flex items-center justify-between pt-2">
                <label for="remember_me" class="inline-flex items-center group cursor-pointer">
                    <input id="remember_me" type="checkbox"
                        class="rounded-lg border-gray-300 text-red-600 shadow-sm focus:ring-red-500 cursor-pointer transition-all duration-200 ease-in-out group-hover:scale-110 w-4 h-4"
                        name="remember">
                    <span class="ms-2 text-sm font-medium text-gray-600 group-hover:text-red-600 transition-colors">
                        {{ __('Ingat saya') }}
                    </span>
                </label>

                <div class="flex gap-3 text-sm">
                    @if (Route::has('password.request'))
                        <a class="font-medium text-gray-600 hover:text-red-600 hover:underline transition-all decoration-2 underline-offset-2"
                            href="{{ route('password.request') }}">
                            {{ __('Lupa password?') }}
                        </a>
                    @endif
                </div>
            </div>

            {{-- Button Login dengan Gradient --}}
            <div class="mt-8 space-y-4">
                <button type="submit"
                    class="w-full relative flex justify-center items-center py-4 px-4 rounded-xl text-base font-bold text-white bg-gradient-to-r from-red-600 via-red-500 to-orange-500 hover:from-red-700 hover:via-red-600 hover:to-orange-600 focus:outline-none focus:ring-4 focus:ring-red-500/50 transform transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-red-500/50 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none overflow-hidden group/btn"
                    :disabled="loading">

                    {{-- Shine effect --}}
                    <div
                        class="absolute inset-0 w-1/2 h-full bg-gradient-to-r from-transparent via-white/20 to-transparent skew-x-12 -translate-x-full group-hover/btn:translate-x-[200%] transition-transform duration-1000">
                    </div>

                    {{-- Spinner Loading --}}
                    <svg x-show="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>

                    <span x-text="loading ? 'Memproses...' : 'Masuk Sekarang'"></span>

                    <svg x-show="!loading" class="w-5 h-5 ml-2 group-hover/btn:translate-x-1 transition-transform"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </button>

                {{-- Register Link --}}
                <div class="text-center">
                    <span class="text-gray-600 text-sm">Belum punya akun? </span>
                    <a class="text-sm font-bold text-red-600 hover:text-red-700 hover:underline transition-all decoration-2 underline-offset-2"
                        href="{{ route('register') }}">
                        {{ __('Daftar Sekarang') }}
                    </a>
                </div>
            </div>
        </form>

        {{-- Footer Decorative --}}
        <div class="mt-8 text-center">
            <div class="flex items-center justify-center gap-2 text-xs text-gray-400">
                <div class="w-8 h-px bg-gray-300"></div>
                <span>Secure Login</span>
                <div class="w-8 h-px bg-gray-300"></div>
            </div>
        </div>
    </div>
</x-guest-layout>
