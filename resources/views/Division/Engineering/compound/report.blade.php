<x-app-layout>
    {{-- x-cloak di parent: sembunyikan SEMUA konten sampai Alpine siap --}}
    <div class="py-8" x-data="{ activeTab: 1 }" x-cloak>
        <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-8">

            {{-- ============================================ --}}
            {{-- HEADER & FILTER                             --}}
            {{-- ============================================ --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border-t-4 border-blue-600 mb-6">
                <div class="p-6 bg-white border-b border-slate-100">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-5">
                        <div>
                            <h2 class="text-xl font-extrabold text-slate-800 tracking-tight flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 rounded-lg">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </span>
                                Laporan Compound
                            </h2>
                            <p class="text-xs text-slate-400 mt-0.5 ml-10">Pilih parameter untuk menampilkan data
                                laporan</p>
                        </div>
                    </div>

                    <form action="{{ route('eng.compound.report') }}" method="GET"
                        class="flex flex-wrap items-end gap-3">

                        {{-- Pilih Plant --}}
                        <div class="flex-1 min-w-[200px]">
                            <label
                                class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Plant</label>
                            <div class="relative">
                                <select name="plant_id"
                                    class="w-full rounded-lg border-slate-200 text-sm shadow-sm
                                           focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400
                                           bg-white text-slate-700 font-medium py-2.5 pl-3 pr-8 appearance-none
                                           hover:border-slate-300 transition-colors duration-150"
                                    required>
                                    <option value="">— Pilih Plant —</option>
                                    <option value="1" {{ request('plant_id') == '1' ? 'selected' : '' }}>Plant A
                                    </option>
                                    <option value="2" {{ request('plant_id') == '2' ? 'selected' : '' }}>Autowire
                                        (Multi 3 Honta)</option>
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
                        <div class="w-full sm:w-40">
                            <label
                                class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Bulan</label>
                            <div class="relative">
                                <select name="bulan"
                                    class="w-full rounded-lg border-slate-200 text-sm shadow-sm
                                           focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400
                                           bg-white text-slate-700 font-medium py-2.5 pl-3 pr-8 appearance-none
                                           hover:border-slate-300 transition-colors duration-150"
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
                        <div class="w-full sm:w-32">
                            <label
                                class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Tahun</label>
                            <div class="relative">
                                <select name="tahun"
                                    class="w-full rounded-lg border-slate-200 text-sm shadow-sm
                                           focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400
                                           bg-white text-slate-700 font-medium py-2.5 pl-3 pr-8 appearance-none
                                           hover:border-slate-300 transition-colors duration-150"
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

                        {{-- Tombol --}}
                        <div class="flex gap-2 w-full sm:w-auto">
                            <button type="submit"
                                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800
                                       text-white font-bold py-2.5 px-5 rounded-lg shadow-sm
                                       hover:shadow-blue-500/30 hover:shadow-md
                                       transition-all duration-150 active:scale-95 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Tampilkan
                            </button>
                            <a href="{{ route('eng.index') }}"
                                class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-600
                                       font-bold py-2.5 px-5 rounded-lg border border-slate-200
                                       transition-colors duration-150 active:scale-95 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    <div class="p-5 sm:p-6">

                        {{-- Sub-header + Export --}}
                        <div class="flex flex-wrap justify-between items-center gap-3 mb-5">
                            <div>
                                <h3 class="text-base font-extrabold text-slate-800 flex items-center gap-2">
                                    <span class="inline-block w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                                    {{ $plantName }}
                                    <span class="text-slate-300 font-light mx-0.5">/</span>
                                    <span class="text-blue-600">{{ $namaBulan }} {{ $tahun }}</span>
                                </h3>
                                <p class="text-xs text-slate-400 mt-0.5">Data pengecekan compound harian</p>
                            </div>

                            <form action="{{ route('eng.compound.export') }}" method="GET">
                                <input type="hidden" name="plant_id" value="{{ $plantId }}">
                                <input type="hidden" name="bulan" value="{{ $bulan }}">
                                <input type="hidden" name="tahun" value="{{ $tahun }}">
                                <button type="submit"
                                    class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700
                                           text-white text-xs font-bold px-4 py-2.5 rounded-lg
                                           shadow-sm hover:shadow-emerald-500/30 hover:shadow-md
                                           transition-all duration-150 active:scale-95">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Download Excel
                                </button>
                            </form>
                        </div>

                        {{-- Tab Navigation --}}
                        <div
                            class="flex overflow-x-auto whitespace-nowrap gap-1.5 mb-5
                                    bg-slate-100 p-1.5 rounded-xl border border-slate-200">
                            @foreach ($baksMap as $key => $bak)
                                <button type="button" @click="activeTab = {{ $key }}"
                                    :class="activeTab === {{ $key }} ?
                                        'bg-white text-blue-700 shadow-sm font-extrabold' :
                                        'text-slate-500 hover:text-slate-700 hover:bg-white/60 font-semibold'"
                                    class="px-4 py-2 rounded-lg text-xs transition-colors duration-150">
                                    {{ $bak['nama'] }}
                                </button>
                            @endforeach
                        </div>

                        {{-- Konten Per Tab — tanpa x-transition, pakai CSS opacity saja --}}
                        @foreach ($baksMap as $key => $bak)
                            @php
                                $mesinId = $bak['id_mesin'];
                                $dataHarian = $dataChecks[$mesinId] ?? collect();
                                $stdMesin = $standards[$mesinId] ?? collect();
                                $stdDraw = $stdMesin->first(fn($item) => strtolower($item->proses) === 'drawing');
                                $stdAnn = $stdMesin->first(fn($item) => strtolower($item->proses) === 'annealing');
                            @endphp

                            {{-- Tidak pakai x-transition sama sekali: instant show/hide, tidak ada flicker --}}
                            <div x-show="activeTab === {{ $key }}" style="display:none;">
                                <div class="rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                                    <div class="overflow-x-auto max-h-[600px] compound-scrollbar">
                                        <table class="w-full text-[11px] text-left border-collapse whitespace-nowrap">

                                            <thead class="sticky top-0 z-10">

                                                {{-- Row 1: Group Headers --}}
                                                <tr
                                                    class="text-center text-white font-extrabold uppercase text-[10px] tracking-wide">
                                                    <th rowspan="2"
                                                        class="p-3 bg-slate-700 sticky left-0 z-20 w-24
                                                               border-r border-slate-600
                                                               shadow-[2px_0_6px_-1px_rgba(0,0,0,0.25)]">
                                                        Tanggal
                                                    </th>
                                                    <th colspan="6"
                                                        class="px-3 py-2.5 bg-blue-700 border-x border-blue-600 tracking-widest">
                                                        Compound Drawing
                                                    </th>
                                                    <th colspan="6"
                                                        class="px-3 py-2.5 bg-teal-700 border-x border-teal-600 tracking-widest">
                                                        Compound Annealing 1
                                                    </th>
                                                    @if ($bak['is_bak_6'])
                                                        <th colspan="6"
                                                            class="px-3 py-2.5 bg-violet-700 border-x border-violet-600 tracking-widest">
                                                            Compound Annealing 2
                                                        </th>
                                                    @endif
                                                    <th rowspan="2"
                                                        class="px-3 py-2 bg-slate-700 border-x border-slate-600">
                                                        Diperiksa</th>
                                                    <th rowspan="2" class="px-3 py-2 bg-slate-700 min-w-[180px]">
                                                        Keterangan</th>
                                                </tr>

                                                {{-- Row 2: Sub-column Headers --}}
                                                <tr class="text-center font-bold uppercase text-[10px] tracking-wide">
                                                    <th
                                                        class="px-2 py-2 bg-blue-600 text-blue-50 border-x border-blue-500">
                                                        Type</th>
                                                    <th
                                                        class="px-2 py-2 bg-blue-600 text-blue-50 border-x border-blue-500">
                                                        Supplier</th>
                                                    <th
                                                        class="px-2 py-2 bg-blue-600 text-blue-50 border-x border-blue-500">
                                                        Warna</th>
                                                    <th
                                                        class="px-2 py-2 bg-blue-600 text-blue-50 border-x border-blue-500">
                                                        Kons(%)</th>
                                                    <th
                                                        class="px-2 py-2 bg-blue-600 text-blue-50 border-x border-blue-500">
                                                        pH</th>
                                                    <th
                                                        class="px-2 py-2 bg-blue-600 text-blue-50 border-x border-blue-500">
                                                        Temp(°C)</th>
                                                    <th
                                                        class="px-2 py-2 bg-teal-600 text-teal-50 border-x border-teal-500">
                                                        Type</th>
                                                    <th
                                                        class="px-2 py-2 bg-teal-600 text-teal-50 border-x border-teal-500">
                                                        Supplier</th>
                                                    <th
                                                        class="px-2 py-2 bg-teal-600 text-teal-50 border-x border-teal-500">
                                                        Warna</th>
                                                    <th
                                                        class="px-2 py-2 bg-teal-600 text-teal-50 border-x border-teal-500">
                                                        Kons(%)</th>
                                                    <th
                                                        class="px-2 py-2 bg-teal-600 text-teal-50 border-x border-teal-500">
                                                        pH</th>
                                                    <th
                                                        class="px-2 py-2 bg-teal-600 text-teal-50 border-x border-teal-500">
                                                        Temp(°C)</th>
                                                    @if ($bak['is_bak_6'])
                                                        <th
                                                            class="px-2 py-2 bg-violet-600 text-violet-50 border-x border-violet-500">
                                                            Type</th>
                                                        <th
                                                            class="px-2 py-2 bg-violet-600 text-violet-50 border-x border-violet-500">
                                                            Supplier</th>
                                                        <th
                                                            class="px-2 py-2 bg-violet-600 text-violet-50 border-x border-violet-500">
                                                            Warna</th>
                                                        <th
                                                            class="px-2 py-2 bg-violet-600 text-violet-50 border-x border-violet-500">
                                                            Kons(%)</th>
                                                        <th
                                                            class="px-2 py-2 bg-violet-600 text-violet-50 border-x border-violet-500">
                                                            pH</th>
                                                        <th
                                                            class="px-2 py-2 bg-violet-600 text-violet-50 border-x border-violet-500">
                                                            Temp(°C)</th>
                                                    @endif
                                                </tr>

                                                {{-- Row 3: Standard Values --}}
                                                <tr
                                                    class="text-center font-semibold text-[10px] border-b-2 border-amber-300">
                                                    <td
                                                        class="px-2 py-2 bg-amber-50 text-amber-800 font-extrabold
                                                               sticky left-0 z-20 border-r border-amber-200
                                                               shadow-[2px_0_6px_-1px_rgba(0,0,0,0.1)]">
                                                        STD
                                                    </td>
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
                                                </tr>
                                            </thead>

                                            {{-- Data Rows --}}
                                            <tbody class="divide-y divide-slate-100 bg-white">
                                                @forelse ($dataHarian as $cek)
                                                    <tr
                                                        class="hover:bg-slate-50 transition-colors duration-100
                                                               text-center divide-x divide-slate-100 text-slate-700">

                                                        <td
                                                            class="px-2 py-2.5 font-bold text-slate-600
                                                                   sticky left-0 bg-white z-10
                                                                   border-r border-slate-200
                                                                   shadow-[2px_0_4px_-2px_rgba(0,0,0,0.08)]">
                                                            {{ \Carbon\Carbon::parse($cek->tanggal_cek)->format('d-m-Y') }}
                                                        </td>

                                                        {{-- Drawing --}}
                                                        <td class="px-2 py-2 text-slate-600">
                                                            {{ $cek->draw_type ?? '—' }}</td>
                                                        <td class="px-2 py-2 text-slate-600">
                                                            {{ $cek->draw_supplier ?? '—' }}</td>
                                                        <td class="px-2 py-2 text-slate-600">
                                                            {{ $cek->draw_warna ?? '—' }}</td>
                                                        <td class="px-2 py-2 font-bold text-blue-700 bg-blue-50/50">
                                                            {{ $cek->draw_konsentrasi ?? '—' }}</td>
                                                        <td class="px-2 py-2 text-slate-600">
                                                            {{ $cek->draw_ph ?? '—' }}</td>
                                                        <td class="px-2 py-2 text-slate-600">
                                                            {{ $cek->draw_temp ?? '—' }}</td>

                                                        {{-- Annealing 1 --}}
                                                        <td class="px-2 py-2 text-slate-600">
                                                            {{ $cek->ann_type ?? '—' }}</td>
                                                        <td class="px-2 py-2 text-slate-600">
                                                            {{ $cek->ann_supplier ?? '—' }}</td>
                                                        <td class="px-2 py-2 text-slate-600">
                                                            {{ $cek->ann_warna ?? '—' }}</td>
                                                        <td class="px-2 py-2 font-bold text-teal-700 bg-teal-50/50">
                                                            {{ $cek->ann_konsentrasi ?? '—' }}</td>
                                                        <td class="px-2 py-2 text-slate-600">{{ $cek->ann_ph ?? '—' }}
                                                        </td>
                                                        <td class="px-2 py-2 text-slate-600">
                                                            {{ $cek->ann_temp ?? '—' }}</td>

                                                        {{-- Annealing 2 --}}
                                                        @if ($bak['is_bak_6'])
                                                            <td class="px-2 py-2 text-slate-600 bg-violet-50/20">
                                                                {{ $cek->ann_type_2 ?? '—' }}</td>
                                                            <td class="px-2 py-2 text-slate-600 bg-violet-50/20">
                                                                {{ $cek->ann_supplier_2 ?? '—' }}</td>
                                                            <td class="px-2 py-2 text-slate-600 bg-violet-50/20">
                                                                {{ $cek->ann_warna_2 ?? '—' }}</td>
                                                            <td
                                                                class="px-2 py-2 font-bold text-violet-700 bg-violet-50/50">
                                                                {{ $cek->ann_konsentrasi_2 ?? '—' }}</td>
                                                            <td class="px-2 py-2 text-slate-600 bg-violet-50/20">
                                                                {{ $cek->ann_ph_2 ?? '—' }}</td>
                                                            <td class="px-2 py-2 text-slate-600 bg-violet-50/20">
                                                                {{ $cek->ann_temp_2 ?? '—' }}</td>
                                                        @endif

                                                        <td class="px-2 py-2 font-medium text-slate-700">
                                                            {{ $cek->pemeriksa->name ?? ($cek->diperiksa_oleh ?? '—') }}
                                                        </td>
                                                        <td
                                                            class="px-2 py-2 text-left whitespace-normal leading-relaxed text-slate-500 text-[10px]">
                                                            {{ $cek->keterangan ?? '—' }}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="{{ $bak['is_bak_6'] ? 21 : 15 }}"
                                                            class="py-16 text-center">
                                                            <div class="inline-flex flex-col items-center gap-3">
                                                                <div
                                                                    class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center">
                                                                    <svg class="w-7 h-7 text-slate-300" fill="none"
                                                                        stroke="currentColor" viewBox="0 0 24 24">
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
                <div class="bg-white rounded-xl border border-slate-200 p-16 text-center shadow-sm">
                    <div
                        class="empty-float w-20 h-20 bg-gradient-to-br from-blue-50 to-slate-100 rounded-2xl
                                flex items-center justify-center mx-auto mb-5 shadow-sm">
                        <svg class="w-9 h-9 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-600">Pilih Parameter Laporan</h3>
                    <p class="text-sm text-slate-400 mt-1.5">
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
        /* ── Sembunyikan semua konten Alpine sampai siap ── */
        [x-cloak] {
            display: none !important;
        }

        /* ── Empty state float ───────────────────────── */
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

        /* ── Scrollbar ───────────────────────────────── */
        .compound-scrollbar::-webkit-scrollbar {
            height: 8px;
            width: 8px;
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
    </style>
</x-app-layout>
