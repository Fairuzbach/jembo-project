<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Ganti Password') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">

                    <div class="max-w-md mx-auto">
                        {{-- Header Form --}}
                        <div class="text-center mb-8">
                            <h3 class="text-lg font-bold text-slate-700">Form Ganti Password</h3>
                            <p class="text-sm text-slate-500">Amankan akun Anda dengan password baru.</p>
                        </div>

                        {{-- Alert Success --}}
                        @if (session('success'))
                            <div
                                class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded mb-6 text-sm">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('save.change.password') }}" class="space-y-6">
                            @csrf

                            {{-- Password Lama --}}
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700">Password
                                    Lama</label>
                                <input id="current_password" type="password" name="current_password" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('current_password')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Password Baru --}}
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700">Password
                                    Baru</label>
                                <input id="new_password" type="password" name="new_password" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('new_password')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Konfirmasi Password --}}
                            <div>
                                <label for="new_password_confirmation"
                                    class="block text-sm font-medium text-gray-700">Konfirmasi Password Baru</label>
                                <input id="new_password_confirmation" type="password" name="new_password_confirmation"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>

                            {{-- Tombol --}}
                            <div class="flex justify-end gap-3 pt-4">
                                <a href="{{ url()->previous() }}"
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300 transition">
                                    Batal
                                </a>
                                <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 transition shadow-md">
                                    Simpan Password
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
