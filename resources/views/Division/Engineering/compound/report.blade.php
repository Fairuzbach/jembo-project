<x-app-layout>
    @php
        $checkService = app(\App\Services\Engineering\CompoundCheckService::class);
    @endphp
    <div class="py-4 sm:py-8" x-data="{ activeTab: 1 }" x-cloak>
        <div class="max-w-screen-2xl mx-auto px-3 sm:px-6 lg:px-8">

            {{-- ============================================ --}}
            {{-- HEADER & FILTER                             --}}
            {{-- ============================================ --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border-t-4 border-blue-600 mb-4 sm:mb-6">
                <div class="p-4 sm:p-6 bg-white border-b border-slate-100">

                    {{-- Title --}}
                    <div class="flex items-start justify-between gap-2 mb-4 sm:mb-5">
                        <div>
                            <h2
                                class="text-lg sm:text-xl font-extrabold text-slate-800 tracking-tight flex items-center gap-2">
                                <span
                                    class="inline-flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg shrink-0">
                                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-blue-600" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </span>
                                Laporan Compound
                            </h2>
                            <p class="text-xs text-slate-400 mt-0.5 ml-9">Pilih parameter untuk menampilkan data laporan
                            </p>
                        </div>
                    </div>

                    {{-- Filter Form --}}
                    <form action="{{ route('eng.compound.report') }}" method="GET">
                        {{-- Row 1: Dropdowns --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:flex lg:flex-wrap gap-2 sm:gap-3 mb-3">

                            {{-- Pilih Plant --}}
                            <div class="col-span-2 sm:col-span-1 lg:flex-1 lg:min-w-[200px]">
                                <label
                                    class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Plant</label>
                                <div class="relative">
                                    <select name="plant_id"
                                        class="w-full rounded-lg border-slate-200 text-sm shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 bg-white text-slate-700 font-medium py-2.5 pl-3 pr-8 appearance-none hover:border-slate-300 transition-colors"
                                        required>
                                        <option value="">— Pilih Plant —</option>
                                        <option value="1" {{ request('plant_id') == '1' ? 'selected' : '' }}>Plant
                                            A</option>
                                        <option value="2" {{ request('plant_id') == '2' ? 'selected' : '' }}>
                                            Autowire (Multi 3 Honta)</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            {{-- Pilih Bulan --}}
                            <div class="lg:w-40">
                                <label
                                    class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Bulan</label>
                                <div class="relative">
                                    <select name="bulan"
                                        class="w-full rounded-lg border-slate-200 text-sm shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 bg-white text-slate-700 font-medium py-2.5 pl-3 pr-8 appearance-none hover:border-slate-300 transition-colors"
                                        required>
                                        @foreach (range(1, 12) as $m)
                                            <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>
                                                {{ Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            {{-- Pilih Tahun --}}
                            <div class="lg:w-32">
                                <label
                                    class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Tahun</label>
                                <div class="relative">
                                    <select name="tahun"
                                        class="w-full rounded-lg border-slate-200 text-sm shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 bg-white text-slate-700 font-medium py-2.5 pl-3 pr-8 appearance-none hover:border-slate-300 transition-colors"
                                        required>
                                        @foreach (range(date('Y') - 2, date('Y')) as $y)
                                            <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>
                                                {{ $y }}</option>
                                        @endforeach
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Row 2: Buttons --}}
                        <div class="flex gap-2">
                            <button type="submit"
                                class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold py-2.5 px-5 rounded-lg shadow-sm hover:shadow-blue-500/30 hover:shadow-md transition-all active:scale-95 text-sm">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Tampilkan
                            </button>
                            <a href="{{ route('eng.index') }}"
                                class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 px-5 rounded-lg border border-slate-200 transition-colors active:scale-95 text-sm">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ============================================ --}}
            {{-- HASIL LAPORAN                               --}}
            {{-- ============================================ --}}
            @if ($plantId)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl">
                    <div class="p-4 sm:p-5 sm:p-6">

                        {{-- Sub-header + Export --}}
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-3 mb-4 sm:mb-5">
                            <div class="flex-1 min-w-0">
                                <h3
                                    class="text-sm sm:text-base font-extrabold text-slate-800 flex items-center gap-2 flex-wrap">
                                    <span
                                        class="inline-block w-2 h-2 rounded-full bg-blue-500 animate-pulse shrink-0"></span>
                                    <span class="truncate">{{ $plantName }}</span>
                                    <span class="text-slate-300 font-light">/</span>
                                    <span class="text-blue-600">{{ $namaBulan }} {{ $tahun }}</span>
                                </h3>

                                <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                    <p class="text-xs text-slate-500 font-medium">Data pengecekan compound harian</p>
                                    {{-- Legend Badge --}}
                                    <div class="flex items-center gap-1.5 px-2 py-0.5 bg-rose-50 border border-rose-200 rounded shadow-sm text-rose-700"
                                        title="Nilai tidak sesuai standar">
                                        <span class="relative flex h-2.5 w-2.5 shrink-0">
                                            <span
                                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                            <span
                                                class="relative inline-flex rounded-full h-2.5 w-2.5 bg-rose-500"></span>
                                        </span>
                                        <span
                                            class="text-[10px] font-extrabold tracking-wider uppercase whitespace-nowrap">Tidak
                                            Sesuai Standar</span>
                                    </div>
                                </div>
                            </div>

                            <form action="{{ route('eng.compound.export') }}" method="GET" class="shrink-0">
                                <input type="hidden" name="plant_id" value="{{ $plantId }}">
                                <input type="hidden" name="bulan" value="{{ $bulan }}">
                                <input type="hidden" name="tahun" value="{{ $tahun }}">
                                <button type="submit"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2.5 rounded-lg shadow-sm hover:shadow-emerald-500/30 hover:shadow-md transition-all active:scale-95">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Download Excel
                                </button>
                            </form>
                        </div>

                        {{-- Tab Navigation --}}
                        <div
                            class="flex overflow-x-auto whitespace-nowrap gap-1 sm:gap-1.5 mb-4 sm:mb-5 bg-slate-100 p-1.5 rounded-xl border border-slate-200 scrollbar-hide snap-x">
                            @foreach ($baksMap as $key => $bak)
                                <button type="button" @click="activeTab = {{ $key }}"
                                    :class="activeTab === {{ $key }} ?
                                        'bg-white text-blue-700 shadow-sm font-extrabold' :
                                        'text-slate-500 hover:text-slate-700 hover:bg-white/60 font-semibold'"
                                    class="snap-start flex-none px-3 sm:px-4 py-2 rounded-lg text-xs transition-colors duration-150">
                                    {{-- Nama singkat di mobile --}}
                                    <span class="sm:hidden">
                                        @if (isset($bak['short']))
                                            {{ $bak['short'] }}
                                        @else
                                            {{ Str::before($bak['nama'], ' (') ?: $bak['nama'] }}
                                        @endif
                                    </span>
                                    <span class="hidden sm:inline">{{ $bak['nama'] }}</span>
                                </button>
                            @endforeach
                        </div>

                        {{-- Hint scroll (mobile only) --}}
                        <div class="flex items-center gap-1.5 text-[10px] text-slate-400 mb-2 sm:hidden">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            Geser tabel ke kiri/kanan untuk melihat semua kolom
                        </div>

                        @foreach ($baksMap as $key => $bak)
                            @php
                                $mesinId = $bak['id_mesin'];
                                $dataHarian = $dataChecks[$mesinId] ?? collect();

                                // Lookup standar — coba $standards[$mesinId] dulu,
                                // fallback ke $stdAutowire untuk plant Autowire
                                $stdMesin = $standards[$mesinId] ?? collect();
                                if ($stdMesin->isEmpty() && isset($stdAutowire)) {
                                    $stdMesin = $stdAutowire['cek_1'] ?? collect();
                                }

                                $stdDraw = $stdMesin->first(fn($item) => strtolower($item->proses) === 'drawing');
                                $stdAnn = $stdMesin->first(fn($item) => strtolower($item->proses) === 'annealing');
                            @endphp

                            <div x-show="activeTab === {{ $key }}" style="display:none;">
                                <div class="rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                                    <div class="overflow-x-auto compound-scrollbar"
                                        style="-webkit-overflow-scrolling: touch; max-height: 65vh;">
                                        <table class="w-full text-[11px] text-left border-collapse whitespace-nowrap">

                                            <thead class="sticky top-0 z-10">

                                                {{-- Row 1: Group Headers --}}
                                                <tr
                                                    class="text-center text-white font-extrabold uppercase text-[10px] tracking-wide">
                                                    <th rowspan="2"
                                                        class="p-2 sm:p-3 bg-slate-700 sticky left-0 z-20 w-20 sm:w-24 border-r border-slate-600 shadow-[2px_0_6px_-1px_rgba(0,0,0,0.25)]">
                                                        Tanggal
                                                    </th>
                                                    <th colspan="6"
                                                        class="px-2 sm:px-3 py-2 sm:py-2.5 bg-blue-700 border-x border-blue-600 tracking-widest">
                                                        Compound Drawing
                                                    </th>
                                                    <th colspan="6"
                                                        class="px-2 sm:px-3 py-2 sm:py-2.5 bg-teal-700 border-x border-teal-600 tracking-widest">
                                                        Compound Annealing{{ $bak['is_bak_6'] ? ' 1' : '' }}
                                                    </th>
                                                    @if ($bak['is_bak_6'])
                                                        <th colspan="6"
                                                            class="px-2 sm:px-3 py-2 sm:py-2.5 bg-violet-700 border-x border-violet-600 tracking-widest">
                                                            Compound Annealing 2
                                                        </th>
                                                    @endif
                                                    <th rowspan="2"
                                                        class="px-2 sm:px-3 py-2 bg-slate-700 border-x border-slate-600">
                                                        Hourmeter</th>
                                                    <th rowspan="2"
                                                        class="px-2 sm:px-3 py-2 bg-slate-700 border-x border-slate-600">
                                                        Diperiksa</th>
                                                    <th rowspan="2"
                                                        class="px-2 sm:px-3 py-2 bg-slate-700 min-w-[160px] sm:min-w-[180px]">
                                                        Keterangan</th>
                                                </tr>

                                                {{-- Row 2: Sub-column Headers --}}
                                                <tr class="text-center font-bold uppercase text-[10px] tracking-wide">
                                                    @foreach (['Type', 'Supplier', 'Warna', 'Kons(%)', 'pH', 'Temp(°C)'] as $col)
                                                        <th
                                                            class="px-2 py-1.5 sm:py-2 bg-blue-600 text-blue-50 border-x border-blue-500 min-w-[60px] sm:min-w-[72px]">
                                                            {{ $col }}</th>
                                                    @endforeach
                                                    @foreach (['Type', 'Supplier', 'Warna', 'Kons(%)', 'pH', 'Temp(°C)'] as $col)
                                                        <th
                                                            class="px-2 py-1.5 sm:py-2 bg-teal-600 text-teal-50 border-x border-teal-500 min-w-[60px] sm:min-w-[72px]">
                                                            {{ $col }}</th>
                                                    @endforeach
                                                    @if ($bak['is_bak_6'])
                                                        @foreach (['Type', 'Supplier', 'Warna', 'Kons(%)', 'pH', 'Temp(°C)'] as $col)
                                                            <th
                                                                class="px-2 py-1.5 sm:py-2 bg-violet-600 text-violet-50 border-x border-violet-500 min-w-[60px] sm:min-w-[72px]">
                                                                {{ $col }}</th>
                                                        @endforeach
                                                    @endif
                                                </tr>

                                                {{-- Row 3: Standard Values --}}
                                                <tr
                                                    class="text-center font-semibold text-[10px] border-b-2 border-amber-300">
                                                    <td
                                                        class="px-2 py-1.5 sm:py-2 bg-amber-50 text-amber-800 font-extrabold sticky left-0 z-20 border-r border-amber-200 shadow-[2px_0_6px_-1px_rgba(0,0,0,0.1)]">
                                                        STD
                                                    </td>
                                                    {{-- Drawing STD --}}
                                                    <td class="px-2 py-1.5 bg-blue-50 text-blue-800">
                                                        {{ $stdDraw->std_tipe ?? '—' }}</td>
                                                    <td class="px-2 py-1.5 bg-blue-50 text-blue-800">
                                                        {{ $stdDraw->std_supplier ?? '—' }}</td>
                                                    <td class="px-2 py-1.5 bg-blue-50 text-blue-800">
                                                        {{ $stdDraw->std_warna ?? '—' }}</td>
                                                    <td class="px-2 py-1.5 bg-blue-50 text-blue-800 font-bold">
                                                        {{ $stdDraw->std_konsentrasi ?? '—' }}</td>
                                                    <td class="px-2 py-1.5 bg-blue-50 text-blue-800">
                                                        {{ $stdDraw->std_ph ?? '—' }}</td>
                                                    <td class="px-2 py-1.5 bg-blue-50 text-blue-800">
                                                        {{ $stdDraw->std_temp ?? '—' }}</td>
                                                    {{-- Annealing STD --}}
                                                    <td class="px-2 py-1.5 bg-teal-50 text-teal-800">
                                                        {{ $stdAnn->std_tipe ?? '—' }}</td>
                                                    <td class="px-2 py-1.5 bg-teal-50 text-teal-800">
                                                        {{ $stdAnn->std_supplier ?? '—' }}</td>
                                                    <td class="px-2 py-1.5 bg-teal-50 text-teal-800">
                                                        {{ $stdAnn->std_warna ?? '—' }}</td>
                                                    <td class="px-2 py-1.5 bg-teal-50 text-teal-800 font-bold">
                                                        {{ $stdAnn->std_konsentrasi ?? '—' }}</td>
                                                    <td class="px-2 py-1.5 bg-teal-50 text-teal-800">
                                                        {{ $stdAnn->std_ph ?? '—' }}</td>
                                                    <td class="px-2 py-1.5 bg-teal-50 text-teal-800">
                                                        {{ $stdAnn->std_temp ?? '—' }}</td>
                                                    @if ($bak['is_bak_6'])
                                                        <td class="px-2 py-1.5 bg-violet-50 text-violet-800">
                                                            {{ $stdAnn->std_tipe ?? '—' }}</td>
                                                        <td class="px-2 py-1.5 bg-violet-50 text-violet-800">
                                                            {{ $stdAnn->std_supplier ?? '—' }}</td>
                                                        <td class="px-2 py-1.5 bg-violet-50 text-violet-800">
                                                            {{ $stdAnn->std_warna ?? '—' }}</td>
                                                        <td class="px-2 py-1.5 bg-violet-50 text-violet-800 font-bold">
                                                            {{ $stdAnn->std_konsentrasi ?? '—' }}</td>
                                                        <td class="px-2 py-1.5 bg-violet-50 text-violet-800">
                                                            {{ $stdAnn->std_ph ?? '—' }}</td>
                                                        <td class="px-2 py-1.5 bg-violet-50 text-violet-800">
                                                            {{ $stdAnn->std_temp ?? '—' }}</td>
                                                    @endif
                                                    <td class="px-2 py-1.5 bg-slate-50"></td>
                                                    <td class="px-2 py-1.5 bg-slate-50"></td>
                                                    <td class="px-2 py-1.5 bg-slate-50"></td>
                                                </tr>
                                            </thead>

                                            {{-- Data Rows --}}
                                            <tbody class="divide-y divide-slate-100 bg-white">
                                                @forelse ($dataHarian as $cek)
                                                    <tr
                                                        class="hover:bg-slate-50 transition-colors duration-100 text-center divide-x divide-slate-100 text-slate-700">

                                                        <td
                                                            class="px-2 py-2 sm:py-2.5 font-bold text-slate-600 sticky left-0 bg-white z-10 border-r border-slate-200 shadow-[2px_0_4px_-2px_rgba(0,0,0,0.08)] text-xs">
                                                            {{ \Carbon\Carbon::parse($cek->tanggal_cek)->format('d-m-Y') }}
                                                        </td>

                                                        {{-- Drawing --}}
                                                        <td class="px-2 py-2 text-slate-600">
                                                            {{ $cek->draw_type ?? '—' }}</td>
                                                        <td class="px-2 py-2 text-slate-600">
                                                            {{ $cek->draw_supplier ?? '—' }}</td>
                                                        <td
                                                            class="px-2 py-2 text-slate-600 {{ !empty($cek->draw_warna) && !empty($stdDraw->std_warna) && $cek->draw_warna !== $stdDraw->std_warna ? 'bg-rose-100 text-rose-700 font-extrabold border-rose-300' : '' }}">
                                                            {{ $cek->draw_warna ?? '—' }}</td>
                                                        <td
                                                            class="px-2 py-2 font-bold text-blue-700 bg-blue-50/50 {{ $checkService->checkIsOos($cek->draw_konsentrasi, $stdDraw->std_konsentrasi ?? '') ? 'bg-rose-100 text-rose-700 font-extrabold border-rose-300' : '' }}">
                                                            {{ $cek->draw_konsentrasi ?? '—' }}</td>
                                                        <td
                                                            class="px-2 py-2 text-slate-600 {{ $checkService->checkIsOos($cek->draw_ph, $stdDraw->std_ph ?? '') ? 'bg-rose-100 text-rose-700 font-extrabold border-rose-300' : '' }}">
                                                            {{ $cek->draw_ph ?? '—' }}</td>
                                                        <td
                                                            class="px-2 py-2 text-slate-600 {{ isset($cek->draw_temp) && (float) str_replace(['°C', 'C', ','], ['', '', '.'], $cek->draw_temp) > 40 ? 'bg-rose-100 text-rose-700 font-extrabold border-rose-300' : '' }}">
                                                            {{ $cek->draw_temp ?? '—' }}
                                                        </td>

                                                        {{-- Annealing 1 --}}
                                                        <td class="px-2 py-2 text-slate-600">
                                                            {{ $cek->ann_type ?? '—' }}</td>
                                                        <td class="px-2 py-2 text-slate-600">
                                                            {{ $cek->ann_supplier ?? '—' }}</td>
                                                        <td
                                                            class="px-2 py-2 text-slate-600 {{ !empty($cek->ann_warna) && !empty($stdAnn->std_warna) && $cek->ann_warna !== $stdAnn->std_warna ? 'bg-rose-100 text-rose-700 font-extrabold border-rose-300' : '' }}">
                                                            {{ $cek->ann_warna ?? '—' }}</td>
                                                        <td
                                                            class="px-2 py-2 font-bold text-teal-700 bg-teal-50/50 {{ $checkService->checkIsOos($cek->ann_konsentrasi, $stdAnn->std_konsentrasi ?? '') ? 'bg-rose-100 text-rose-700 font-extrabold border-rose-300' : '' }}">
                                                            {{ $cek->ann_konsentrasi ?? '—' }}</td>
                                                        <td
                                                            class="px-2 py-2 text-slate-600 {{ $checkService->checkIsOos($cek->ann_ph, $stdAnn->std_ph ?? '') ? 'bg-rose-100 text-rose-700 font-extrabold border-rose-300' : '' }}">
                                                            {{ $cek->ann_ph ?? '—' }}</td>
                                                        <td
                                                            class="px-2 py-2 text-slate-600 {{ isset($cek->ann_temp) && (float) str_replace(['°C', 'C', ','], ['', '', '.'], $cek->ann_temp) > 40 ? 'bg-rose-100 text-rose-700 font-extrabold border-rose-300' : '' }}">
                                                            {{ $cek->ann_temp ?? '—' }}
                                                        </td>

                                                        {{-- Annealing 2 (BAK 6 only) --}}
                                                        @if ($bak['is_bak_6'])
                                                            <td class="px-2 py-2 text-slate-600 bg-violet-50/20">
                                                                {{ $cek->ann_type_2 ?? '—' }}</td>
                                                            <td class="px-2 py-2 text-slate-600 bg-violet-50/20">
                                                                {{ $cek->ann_supplier_2 ?? '—' }}</td>
                                                            <td
                                                                class="px-2 py-2 text-slate-600 {{ !empty($cek->ann_warna_2) && !empty($stdAnn->std_warna) && $cek->ann_warna_2 !== $stdAnn->std_warna ? 'bg-rose-100 text-rose-700 font-extrabold border-rose-300' : 'bg-violet-50/20' }}">
                                                                {{ $cek->ann_warna_2 ?? '—' }}</td>
                                                            <td
                                                                class="px-2 py-2 font-bold text-violet-700 bg-violet-50/50 {{ $checkService->checkIsOos($cek->ann_konsentrasi_2, $stdAnn->std_konsentrasi ?? '') ? 'bg-rose-100 text-rose-700 font-extrabold border-rose-300' : '' }}">
                                                                {{ $cek->ann_konsentrasi_2 ?? '—' }}</td>
                                                            <td
                                                                class="px-2 py-2 text-slate-600 bg-violet-50/20 {{ $checkService->checkIsOos($cek->ann_ph_2, $stdAnn->std_ph ?? '') ? 'bg-rose-100 text-rose-700 font-extrabold border-rose-300' : '' }}">
                                                                {{ $cek->ann_ph_2 ?? '—' }}</td>
                                                            <td
                                                                class="px-2 py-2 text-slate-600 {{ isset($cek->ann_temp_2) && (float) str_replace(['°C', 'C', ','], ['', '', '.'], $cek->ann_temp_2) > 40 ? 'bg-rose-100 text-rose-700 font-extrabold border-rose-300' : '' }}">
                                                                {{ $cek->ann_temp_2 ?? '—' }}
                                                            </td>
                                                        @endif

                                                        <td class="px-2 py-2 text-slate-600">
                                                            {{ $cek->hourmeter ? number_format($cek->hourmeter, 2, ',', '.') : '—' }}
                                                        </td>
                                                        <td class="px-2 py-2 font-medium text-slate-700">
                                                            {{ $cek->pemeriksa->name ?? ($cek->diperiksa_oleh ?? '—') }}
                                                        </td>
                                                        <td
                                                            class="px-2 py-2 text-left whitespace-normal leading-relaxed text-slate-500 text-[10px] min-w-[160px] sm:min-w-[180px]">
                                                            {{ $cek->keterangan ?? '—' }}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="{{ $bak['is_bak_6'] ? 22 : 16 }}"
                                                            class="py-12 sm:py-16 text-center">
                                                            <div class="inline-flex flex-col items-center gap-3">
                                                                <div
                                                                    class="w-12 h-12 sm:w-14 sm:h-14 bg-slate-100 rounded-2xl flex items-center justify-center">
                                                                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-slate-300"
                                                                        fill="none" stroke="currentColor"
                                                                        viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="1.5"
                                                                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                    </svg>
                                                                </div>
                                                                <p class="text-xs text-slate-400 font-medium">Belum ada
                                                                    data pengecekan untuk bulan ini</p>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                {{-- Empty State --}}
                <div class="bg-white rounded-xl border border-slate-200 p-10 sm:p-16 text-center shadow-sm">
                    <div
                        class="empty-float w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-br from-blue-50 to-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4 sm:mb-5 shadow-sm">
                        <svg class="w-7 h-7 sm:w-9 sm:h-9 text-blue-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-sm sm:text-base font-bold text-slate-600">Pilih Parameter Laporan</h3>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1.5">
                        Pilih Plant, Bulan, dan Tahun di atas lalu klik
                        <span class="font-semibold text-blue-600">Tampilkan</span>.
                    </p>
                    <div class="mt-4 flex items-center justify-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-300 animate-bounce"
                            style="animation-delay:0ms"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-bounce"
                            style="animation-delay:150ms"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-bounce"
                            style="animation-delay:300ms"></span>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <style>
        [x-cloak] {
            display: none !important;
        }

        @keyframes gentle-float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-6px);
            }
        }

        .empty-float {
            animation: gentle-float 3s ease-in-out infinite;
            will-change: transform;
        }

        /* Scrollbar */
        .compound-scrollbar::-webkit-scrollbar {
            height: 6px;
            width: 6px;
        }

        .compound-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 999px;
        }

        .compound-scrollbar::-webkit-scrollbar-thumb {
            background: linear-gradient(90deg, #93c5fd, #5eead4);
            border-radius: 999px;
            border: 2px solid #f1f5f9;
        }

        .compound-scrollbar::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(90deg, #3b82f6, #14b8a6);
        }

        .compound-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #93c5fd #f1f5f9;
        }

        /* Hide scrollbar on mobile (keep scroll functionality) */
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</x-app-layout>
