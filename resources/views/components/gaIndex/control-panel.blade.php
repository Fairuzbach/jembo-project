@props(['filterOptions' => []])

<div class="bg-white shadow-lg rounded-2xl border border-slate-100 mb-8 hover:shadow-xl transition-shadow duration-300">

    {{-- Header Bar (Yellow Accent) --}}
    <div class="h-2 bg-gradient-to-r from-yellow-400 via-amber-400 to-yellow-500 w-full rounded-t-2xl">
    </div>

    <form action="{{ route('ga.index') }}" method="GET" class="divide-y divide-slate-100">
        <div class="p-6 flex flex-col lg:flex-row gap-4 items-center justify-between">

            {{-- Search Input Group --}}
            <div class="w-full lg:w-1/2 flex relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}"
                    class="block w-full pl-12 pr-4 py-3 border-2 border-slate-200 rounded-l-xl text-sm font-medium text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all bg-slate-50 focus:bg-white shadow-sm group-hover:shadow-md"
                    placeholder="Cari No. Tiket, Nama, atau Lokasi...">
                <button type="submit"
                    class="bg-gradient-to-br from-[#1E3A5F] to-[#152a47] text-white px-6 py-3 rounded-r-xl text-sm font-bold uppercase tracking-wider hover:from-[#162c46] hover:to-[#0f1f33] transition-all border-2 border-slate-700/30 shadow-md hover:shadow-lg">
                    Cari
                </button>
            </div>

            {{-- Action Buttons --}}
            <div class="w-full lg:w-auto flex gap-3 justify-end items-center">

                {{-- 1. Filter Toggle --}}
                <button type="button" @click="showFilters = !showFilters"
                    class="flex items-center gap-2 px-5 py-3 border-2 border-slate-200 bg-white text-slate-600 rounded-xl hover:border-blue-400 hover:text-slate-900 hover:bg-blue-50 font-bold text-xs uppercase transition-all shadow-sm hover:shadow-md"
                    :class="showFilters ? 'bg-blue-50 border-blue-400 text-slate-900 shadow-md' : ''">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                        </path>
                    </svg>
                    Filter
                    @if (request()->anyFilled(['category', 'status', 'parameter', 'start_date']))
                        <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                    @endif
                </button>

                {{-- 2. Export Button (UPDATED) --}}
                {{-- Menggunakan formaction agar mengirim data form (filter/search) ke route export --}}
                <button type="submit" formaction="{{ route('ga.export') }}"
                    class="group flex items-center justify-center gap-2 px-5 py-3 border-2 border-emerald-100 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white hover:border-emerald-600 rounded-xl transition-all shadow-sm hover:shadow-md font-bold text-xs uppercase"
                    title="Export Data ke Excel (.xlsx)">

                    {{-- Icon Download --}}
                    <svg class="w-5 h-5 transition-transform group-hover:-translate-y-0.5" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4">
                        </path>
                    </svg>
                    <span class="hidden sm:inline">Export</span>
                </button>

                {{-- 3. Create Button --}}
                <button @click="showCreateModal = true" type="button"
                    class="flex items-center gap-2 bg-yellow-400 text-slate-900 px-5 py-2.5 rounded-sm font-black uppercase tracking-wider shadow-md hover:bg-yellow-300 hover:shadow-lg transition-all transform active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span class="hidden sm:inline">Buat Tiket</span>
                    <span class="sm:hidden">Baru</span>
                </button>
            </div>
        </div>

        {{-- Collapsible Filter Panel --}}
        <div x-show="showFilters" x-collapse class="bg-slate-50 px-5 pb-5 pt-2 border-t border-slate-200">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                @foreach (['status' => ['pending', 'in_progress', 'completed', 'cancelled', 'waiting_approval', 'waiting_approval_ga', 'rejected'], 'category' => ['BERAT', 'SEDANG', 'RINGAN'], 'parameter' => ['KEBERSIHAN', 'PEMELIHARAAN', 'PERBAIKAN', 'PEMBUATAN BARU', 'PERIZINAN', 'RESERVASI']] as $key => $opts)
                    <div>
                        <label
                            class="text-[10px] font-black text-slate-500 uppercase block mb-1 tracking-wider">{{ ucfirst($key) }}</label>
                        <select name="{{ $key }}" onchange="this.form.submit()"
                            class="w-full text-xs font-bold border-slate-300 focus:border-yellow-400 focus:ring-0 rounded-sm bg-white h-10 uppercase cursor-pointer hover:bg-slate-50 transition-colors">
                            <option value="">SEMUA {{ strtoupper($key) }}</option>
                            @foreach ($opts as $opt)
                                <option value="{{ $opt }}" {{ request($key) == $opt ? 'selected' : '' }}>
                                    {{ str_replace('_', ' ', strtoupper($opt)) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach

                {{-- Date Picker --}}
                <div class="md:col-span-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase block mb-1 tracking-wider">RENTANG
                        TANGGAL</label>
                    <div class="relative">
                        <input type="text" id="date_range_picker"
                            class="w-full text-xs font-bold border-slate-300 focus:border-yellow-400 focus:ring-0 rounded-sm bg-white h-10 pl-9"
                            placeholder="Pilih Tanggal...">
                        <div
                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                        </div>
                        <input type="hidden" name="start_date" id="start_date" value="{{ request('start_date') }}">
                        <input type="hidden" name="end_date" id="end_date" value="{{ request('end_date') }}">
                    </div>
                </div>
            </div>
            <div class="mt-4 flex justify-end border-t border-slate-200 pt-3">
                <a href="{{ route('ga.index') }}"
                    class="text-xs font-bold text-red-500 hover:text-red-700 flex items-center gap-1 uppercase tracking-wide transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg> Reset Filter
                </a>
            </div>
        </div>
    </form>

    @if (Auth::user()->role === 'ga.admin')
        {{-- Bulk Action Bar (MUNCUL SAAT ADA YANG DICENTANG) --}}
        <div x-show="selected.length > 0" x-transition
            class="bg-yellow-50 px-5 py-3 border-t border-yellow-200 flex flex-col md:flex-row justify-between items-center gap-4">

            {{-- Counter Info --}}
            <div class="flex items-center gap-2 text-xs font-bold text-slate-800 uppercase tracking-wider">
                <span class="flex h-3 w-3 relative"><span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"></span><span
                        class="relative inline-flex rounded-full h-3 w-3 bg-yellow-500"></span></span>
                <span x-text="selected.length"></span> ITEM TERPILIH
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap gap-4 items-center justify-center">

                {{-- BUTTON 1: DOWNLOAD CHECKLIST (Hanya ID yang dipilih) --}}
                <form id="exportSelectedForm" action="{{ route('ga.export') }}" method="GET"
                    class="flex items-center">
                    <input type="hidden" name="selected_ids" :value="selected.join(',')">
                    {{-- Loop Filter juga di sini agar aman --}}
                    @foreach (request()->except(['selected_ids', 'page']) as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <button type="submit"
                        class="text-xs font-bold text-slate-800 hover:text-blue-700 uppercase flex items-center gap-1 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Download Checklist (<span x-text="selected.length"></span>)
                    </button>
                </form>

                {{-- BUTTON 2: EXPORT SEMUA FILTERED (Hijau - Tidak peduli checklist) --}}
                <form action="{{ route('ga.export') }}" method="GET"
                    class="flex items-center border-l border-slate-300 pl-4">
                    @foreach (request()->except(['selected_ids', 'page']) as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <button type="submit"
                        class="text-xs font-bold text-green-600 hover:text-green-800 uppercase flex items-center gap-1 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        Export Semua (Filter)
                    </button>
                </form>

                {{-- BUTTON 3: BATAL --}}
                <button type="button" @click="clearSelection()"
                    class="text-xs font-bold text-red-400 hover:text-red-600 uppercase transition-colors ml-2">
                    Batal
                </button>
            </div>
        </div>
    @endif
</div>
