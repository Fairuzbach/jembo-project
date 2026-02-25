<x-app-layout>
    <div class="py-12" x-data="{ activeTab: 1 }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Alert --}}
            @if (session('error'))
                <div
                    class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg relative flex items-center gap-3 shadow-sm">
                    <span class="block sm:inline text-sm font-bold">{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-8 border-indigo-600">

                {{-- HEADER --}}
                <div
                    class="p-6 bg-white border-b border-slate-200 flex justify-between items-center sticky top-0 z-20 shadow-sm">
                    <div>
                        <h2 class="text-2xl font-extrabold text-slate-800">Edit / Detail Matriks Compound</h2>
                        <div class="flex items-center gap-2 mt-1">
                            <span
                                class="bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider">{{ $plant->name }}</span>
                            <span class="text-sm text-slate-500 font-bold">•
                                {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</span>
                        </div>
                    </div>
                    <a href="{{ route('eng.index') }}"
                        class="text-slate-500 hover:text-slate-800 flex items-center gap-2 text-sm font-medium bg-slate-50 px-4 py-2 rounded border border-slate-200 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Kembali
                    </a>
                </div>

                <form action="{{ route('eng.compound.update', [$plant->id, $tanggal]) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="plant" value="{{ $plantName }}">
                    {{-- Hidden input agar tanggal tidak hilang saat update Plant A --}}
                    @if ($plantName === 'Plant A')
                        <input type="hidden" name="plant_a_tanggal" value="{{ $tanggal }}">
                    @endif

                    <div class="p-4 sm:p-6 bg-slate-50">

                        {{-- ========================================== --}}
                        {{-- LOGIKA UI: PLANT A                         --}}
                        {{-- ========================================== --}}
                        @if ($plantName === 'Plant A')
                            @php
                                $baksMap = [
                                    1 => ['id_mesin' => 1, 'nama' => 'BAK 1 (HD 10 C)'],
                                    2 => ['id_mesin' => 3, 'nama' => 'BAK 2 (MD 1)'],
                                    3 => ['id_mesin' => 52, 'nama' => 'BAK 3 (QDMD Deyang)'],
                                    4 => ['id_mesin' => 53, 'nama' => 'BAK 4 (Multi 2 Samp)'],
                                    5 => ['id_mesin' => 54, 'nama' => 'BAK 5 (Multi 1 Samp)'],
                                    6 => ['id_mesin' => 2, 'nama' => 'BAK 6 (Twin RBD Cu)'],
                                ];
                            @endphp

                            {{-- Tab Navigation --}}
                            <div
                                class="flex overflow-x-auto whitespace-nowrap bg-white border border-slate-200 p-2 gap-2 rounded-xl mb-6 shadow-sm">
                                @foreach ($baksMap as $key => $bak)
                                    @php $hasData = isset($checks[$bak['id_mesin']]); @endphp
                                    <button type="button" @click="activeTab = {{ $key }}"
                                        :class="activeTab === {{ $key }} ? 'bg-indigo-600 text-white shadow-md' :
                                            'bg-slate-50 text-slate-600 hover:bg-slate-100'"
                                        class="px-4 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2">
                                        <span
                                            class="w-5 h-5 rounded-full flex items-center justify-center border text-[10px] {{ $hasData ? 'bg-emerald-500 border-emerald-400 text-white' : 'bg-slate-200 text-slate-500' }}">
                                            {!! $hasData ? '✓' : $key !!}
                                        </span>
                                        {{ $bak['nama'] }}
                                    </button>
                                @endforeach
                            </div>

                            {{-- Tab Content --}}
                            @foreach ($baksMap as $key => $bak)
                                @php
                                    $data = $checks[$bak['id_mesin']] ?? null;
                                    $stdMesin = $stdPlantA['bak_' . $key] ?? collect();
                                    $stdDraw = $stdMesin->where('proses', 'drawing')->first();
                                    $stdAnn = $stdMesin->where('proses', 'annealing')->first();
                                @endphp

                                <div x-show="activeTab === {{ $key }}" x-transition style="display: none;">
                                    <div
                                        class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden mb-4">
                                        <div
                                            class="bg-slate-50 px-4 py-2 border-b border-slate-200 flex justify-between items-center">
                                            <span class="text-xs font-bold text-slate-700 uppercase">Pengecekan
                                                {{ $bak['nama'] }}</span>
                                            <span class="text-[10px] text-slate-500 italic font-medium">Geser tabel ke
                                                samping →</span>
                                        </div>

                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm text-left border-collapse">
                                                <thead>
                                                    <tr class="bg-slate-50/50">
                                                        <th
                                                            class="p-3 text-[10px] font-extrabold text-slate-500 uppercase border-b sticky left-0 bg-slate-50 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                                            Parameter</th>
                                                        <th
                                                            class="p-3 text-[10px] font-extrabold text-blue-600 uppercase border-b text-center min-w-[140px]">
                                                            Drawing</th>
                                                        <th
                                                            class="p-3 text-[10px] font-extrabold text-emerald-600 uppercase border-b text-center min-w-[140px]">
                                                            Annealing</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    {{-- Type Item --}}
                                                    <tr>
                                                        <td
                                                            class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                            Type Item</td>
                                                        <td class="p-2 align-top">
                                                            <input type="text"
                                                                name="plant_a[bak_{{ $key }}][draw_type]"
                                                                value="{{ $data->draw_type ?? '' }}"
                                                                class="w-full border-slate-200 rounded text-xs p-1.5 focus:ring-blue-500 text-center bg-slate-50/50"
                                                                placeholder="...">
                                                            <span
                                                                class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                <span
                                                                    class="font-bold text-slate-600">{{ $stdDraw->std_tipe ?? '-' }}</span></span>
                                                        </td>
                                                        <td class="p-2 align-top">
                                                            <input type="text"
                                                                name="plant_a[bak_{{ $key }}][ann_type]"
                                                                value="{{ $data->ann_type ?? '' }}"
                                                                class="w-full border-slate-200 rounded text-xs p-1.5 focus:ring-emerald-500 text-center bg-slate-50/50"
                                                                placeholder="...">
                                                            <span
                                                                class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                <span
                                                                    class="font-bold text-slate-600">{{ $stdAnn->std_tipe ?? '-' }}</span></span>
                                                        </td>
                                                    </tr>

                                                    {{-- Supplier --}}
                                                    <tr>
                                                        <td
                                                            class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                            Supplier</td>
                                                        <td class="p-2 align-top">
                                                            <input type="text"
                                                                name="plant_a[bak_{{ $key }}][draw_supplier]"
                                                                value="{{ $data->draw_supplier ?? '' }}"
                                                                class="w-full border-slate-200 rounded text-xs p-1.5 focus:ring-blue-500 text-center"
                                                                placeholder="...">
                                                            <span
                                                                class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                <span
                                                                    class="font-bold text-slate-600">{{ $stdDraw->std_supplier ?? '-' }}</span></span>
                                                        </td>
                                                        <td class="p-2 align-top">
                                                            <input type="text"
                                                                name="plant_a[bak_{{ $key }}][ann_supplier]"
                                                                value="{{ $data->ann_supplier ?? '' }}"
                                                                class="w-full border-slate-200 rounded text-xs p-1.5 focus:ring-emerald-500 text-center"
                                                                placeholder="...">
                                                            <span
                                                                class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                <span
                                                                    class="font-bold text-slate-600">{{ $stdAnn->std_supplier ?? '-' }}</span></span>
                                                        </td>
                                                    </tr>

                                                    {{-- Warna --}}
                                                    <tr>
                                                        <td
                                                            class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                            Warna</td>
                                                        <td class="p-2 align-top">
                                                            <input type="text"
                                                                name="plant_a[bak_{{ $key }}][draw_warna]"
                                                                value="{{ $data->draw_warna ?? '' }}"
                                                                class="w-full border-slate-200 rounded text-xs p-1.5 focus:ring-blue-500 text-center"
                                                                placeholder="...">
                                                            <span
                                                                class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                <span
                                                                    class="font-bold text-slate-600">{{ $stdDraw->std_warna ?? '-' }}</span></span>
                                                        </td>
                                                        <td class="p-2 align-top">
                                                            <input type="text"
                                                                name="plant_a[bak_{{ $key }}][ann_warna]"
                                                                value="{{ $data->ann_warna ?? '' }}"
                                                                class="w-full border-slate-200 rounded text-xs p-1.5 focus:ring-emerald-500 text-center"
                                                                placeholder="...">
                                                            <span
                                                                class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                <span
                                                                    class="font-bold text-slate-600">{{ $stdAnn->std_warna ?? '-' }}</span></span>
                                                        </td>
                                                    </tr>

                                                    {{-- Konsentrasi --}}
                                                    <tr>
                                                        <td
                                                            class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                            Konsentrasi</td>
                                                        <td class="p-2 align-top">
                                                            <div class="flex items-center">
                                                                <input type="number" step="0.1"
                                                                    name="plant_a[bak_{{ $key }}][draw_konsentrasi]"
                                                                    value="{{ isset($data->draw_konsentrasi) ? str_replace('%', '', $data->draw_konsentrasi) : '' }}"
                                                                    class="w-full border-slate-200 rounded-l text-xs p-1.5 text-center font-bold text-blue-600"
                                                                    placeholder="0.0">
                                                                <span
                                                                    class="bg-slate-100 border border-l-0 border-slate-200 px-2 py-1.5 text-[10px] font-bold text-slate-500 rounded-r">%</span>
                                                            </div>
                                                            <span
                                                                class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                <span
                                                                    class="font-bold text-slate-600">{{ $stdDraw->std_konsentrasi ?? '-' }}</span></span>
                                                        </td>
                                                        <td class="p-2 align-top">
                                                            <div class="flex items-center">
                                                                <input type="number" step="0.01"
                                                                    name="plant_a[bak_{{ $key }}][ann_konsentrasi]"
                                                                    value="{{ isset($data->ann_konsentrasi) ? str_replace('%', '', $data->ann_konsentrasi) : '' }}"
                                                                    class="w-full border-slate-200 rounded-l text-xs p-1.5 text-center font-bold text-blue-600"
                                                                    placeholder="0.0">
                                                                <span
                                                                    class="bg-slate-100 border border-l-0 border-slate-200 px-2 py-1.5 text-[10px] font-bold text-slate-500 rounded-r">%</span>
                                                            </div>
                                                            <span
                                                                class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                <span
                                                                    class="font-bold text-slate-600">{{ $stdAnn->std_konsentrasi ?? '-' }}</span></span>
                                                        </td>
                                                    </tr>

                                                    {{-- pH Level --}}
                                                    <tr>
                                                        <td
                                                            class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                            pH Level</td>
                                                        <td class="p-2 align-top">
                                                            <input type="number" step="0.1"
                                                                name="plant_a[bak_{{ $key }}][draw_ph]"
                                                                value="{{ $data->draw_ph ?? '' }}"
                                                                class="w-full border-slate-200 rounded text-xs p-1.5 text-center font-bold text-emerald-700"
                                                                placeholder="0.0">
                                                            <span
                                                                class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                <span
                                                                    class="font-bold text-slate-600">{{ $stdDraw->std_ph ?? '-' }}</span></span>
                                                        </td>
                                                        <td class="p-2 align-top">
                                                            <input type="number" step="0.1"
                                                                name="plant_a[bak_{{ $key }}][ann_ph]"
                                                                value="{{ $data->ann_ph ?? '' }}"
                                                                class="w-full border-slate-200 rounded text-xs p-1.5 text-center font-bold text-emerald-700"
                                                                placeholder="0.0">
                                                            <span
                                                                class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                <span
                                                                    class="font-bold text-slate-600">{{ $stdAnn->std_ph ?? '-' }}</span></span>
                                                        </td>
                                                    </tr>

                                                    {{-- Temperatur --}}
                                                    <tr>
                                                        <td
                                                            class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                            Temperatur</td>
                                                        <td class="p-2 align-top">
                                                            <div class="flex items-center">
                                                                <input type="number" step="1"
                                                                    name="plant_a[bak_{{ $key }}][draw_temp]"
                                                                    value="{{ isset($data->draw_temp) ? str_replace(['°C', 'C'], '', $data->draw_temp) : '' }}"
                                                                    class="w-full border-slate-200 rounded-l text-xs p-1.5 text-center"
                                                                    placeholder="0">
                                                                <span
                                                                    class="bg-slate-100 border border-l-0 border-slate-200 px-1 py-1.5 text-[10px] font-bold text-slate-500 rounded-r">°C</span>
                                                            </div>
                                                            <span
                                                                class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                <span
                                                                    class="font-bold text-slate-600">{{ $stdDraw->std_temp ?? '-' }}</span></span>
                                                        </td>
                                                        <td class="p-2 align-top">
                                                            <div class="flex items-center">
                                                                <input type="number" step="1"
                                                                    name="plant_a[bak_{{ $key }}][ann_temp]"
                                                                    value="{{ isset($data->ann_temp) ? str_replace(['°C', 'C'], '', $data->ann_temp) : '' }}"
                                                                    class="w-full border-slate-200 rounded-l text-xs p-1.5 text-center"
                                                                    placeholder="0">
                                                                <span
                                                                    class="bg-slate-100 border border-l-0 border-slate-200 px-1 py-1.5 text-[10px] font-bold text-slate-500 rounded-r">°C</span>
                                                            </div>
                                                            <span
                                                                class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                <span
                                                                    class="font-bold text-slate-600">{{ $stdAnn->std_temp ?? '-' }}</span></span>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    {{-- Info Hapus Tab --}}
                                    <p class="text-[10px] text-slate-400 italic mb-4">*Jika Anda ingin menghapus data
                                        Bak ini, cukup kosongkan seluruh isian di tabel atas dan klik Simpan.</p>
                                </div>
                            @endforeach

                            {{-- ========================================== --}}
                            {{-- LOGIKA UI: AUTOWIRE                        --}}
                            {{-- ========================================== --}}
                        @elseif ($plantName === 'Autowire')
                            @php
                                // Untuk Edit Autowire, karena difilter per tanggal, biasanya hanya ada 1 record
                                $dataAuto = $checks->first();
                                $stdMesinAuto = $stdAutowire['cek_1'] ?? collect(); // Ambil std default cek 1
                                $stdDrawAuto = $stdMesinAuto->where('proses', 'drawing')->first();
                                $stdAnnAuto = $stdMesinAuto->where('proses', 'annealing')->first();
                            @endphp

                            <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden mb-4">
                                <div
                                    class="bg-slate-50 px-4 py-2 border-b border-slate-200 flex justify-between items-center">
                                    <span class="text-xs font-bold text-slate-700 uppercase">Pengecekan Mesin Autowire
                                        (Cek 1)</span>
                                    <span class="text-[10px] text-slate-500 italic font-medium">Geser tabel ke samping
                                        →</span>
                                </div>
                                <div class="p-4 bg-white border-b border-slate-100">
                                    <label class="font-bold text-xs text-slate-700 uppercase">Tanggal Cek:</label>
                                    <input type="date" name="autowire[cek_1][tanggal]"
                                        value="{{ $tanggal }}"
                                        class="ml-2 rounded border-slate-300 text-sm py-1 focus:ring-blue-500 bg-slate-50"
                                        readonly>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm text-left border-collapse">
                                        <thead>
                                            <tr class="bg-slate-50/50">
                                                <th
                                                    class="p-3 text-[10px] font-extrabold text-slate-500 uppercase border-b sticky left-0 bg-slate-50 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                                    Parameter</th>
                                                <th
                                                    class="p-3 text-[10px] font-extrabold text-blue-600 uppercase border-b text-center min-w-[140px]">
                                                    Drawing</th>
                                                <th
                                                    class="p-3 text-[10px] font-extrabold text-emerald-600 uppercase border-b text-center min-w-[140px]">
                                                    Annealing</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            {{-- Type Item --}}
                                            <tr>
                                                <td
                                                    class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                    Type Item</td>
                                                <td class="p-2 align-top">
                                                    <input type="text" name="autowire[cek_1][draw_type]"
                                                        value="{{ $dataAuto->draw_type ?? '' }}"
                                                        class="w-full border-slate-200 rounded text-xs p-1.5 focus:ring-blue-500 text-center bg-slate-50/50"
                                                        placeholder="...">
                                                    <span
                                                        class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                        <span
                                                            class="font-bold text-slate-600">{{ $stdDrawAuto->std_tipe ?? '-' }}</span></span>
                                                </td>
                                                <td class="p-2 align-top">
                                                    <input type="text" name="autowire[cek_1][ann_type]"
                                                        value="{{ $dataAuto->ann_type ?? '' }}"
                                                        class="w-full border-slate-200 rounded text-xs p-1.5 focus:ring-emerald-500 text-center bg-slate-50/50"
                                                        placeholder="...">
                                                    <span
                                                        class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                        <span
                                                            class="font-bold text-slate-600">{{ $stdAnnAuto->std_tipe ?? '-' }}</span></span>
                                                </td>
                                            </tr>

                                            {{-- Supplier --}}
                                            <tr>
                                                <td
                                                    class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                    Supplier</td>
                                                <td class="p-2 align-top">
                                                    <input type="text" name="autowire[cek_1][draw_supplier]"
                                                        value="{{ $dataAuto->draw_supplier ?? '' }}"
                                                        class="w-full border-slate-200 rounded text-xs p-1.5 focus:ring-blue-500 text-center"
                                                        placeholder="...">
                                                    <span
                                                        class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                        <span
                                                            class="font-bold text-slate-600">{{ $stdDrawAuto->std_supplier ?? '-' }}</span></span>
                                                </td>
                                                <td class="p-2 align-top">
                                                    <input type="text" name="autowire[cek_1][ann_supplier]"
                                                        value="{{ $dataAuto->ann_supplier ?? '' }}"
                                                        class="w-full border-slate-200 rounded text-xs p-1.5 focus:ring-emerald-500 text-center"
                                                        placeholder="...">
                                                    <span
                                                        class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                        <span
                                                            class="font-bold text-slate-600">{{ $stdAnnAuto->std_supplier ?? '-' }}</span></span>
                                                </td>
                                            </tr>

                                            {{-- Warna --}}
                                            <tr>
                                                <td
                                                    class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                    Warna</td>
                                                <td class="p-2 align-top">
                                                    <input type="text" name="autowire[cek_1][draw_warna]"
                                                        value="{{ $dataAuto->draw_warna ?? '' }}"
                                                        class="w-full border-slate-200 rounded text-xs p-1.5 focus:ring-blue-500 text-center"
                                                        placeholder="...">
                                                    <span
                                                        class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                        <span
                                                            class="font-bold text-slate-600">{{ $stdDrawAuto->std_warna ?? '-' }}</span></span>
                                                </td>
                                                <td class="p-2 align-top">
                                                    <input type="text" name="autowire[cek_1][ann_warna]"
                                                        value="{{ $dataAuto->ann_warna ?? '' }}"
                                                        class="w-full border-slate-200 rounded text-xs p-1.5 focus:ring-emerald-500 text-center"
                                                        placeholder="...">
                                                    <span
                                                        class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                        <span
                                                            class="font-bold text-slate-600">{{ $stdAnnAuto->std_warna ?? '-' }}</span></span>
                                                </td>
                                            </tr>

                                            {{-- Konsentrasi --}}
                                            <tr>
                                                <td
                                                    class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                    Konsentrasi</td>
                                                <td class="p-2 align-top">
                                                    <div class="flex items-center">
                                                        <input type="number" step="0.1"
                                                            name="autowire[cek_1][draw_konsentrasi]"
                                                            value="{{ isset($dataAuto->draw_konsentrasi) ? str_replace('%', '', $dataAuto->draw_konsentrasi) : '' }}"
                                                            class="w-full border-slate-200 rounded-l text-xs p-1.5 text-center font-bold text-blue-600"
                                                            placeholder="0.0">
                                                        <span
                                                            class="bg-slate-100 border border-l-0 border-slate-200 px-2 py-1.5 text-[10px] font-bold text-slate-500 rounded-r">%</span>
                                                    </div>
                                                    <span
                                                        class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                        <span
                                                            class="font-bold text-slate-600">{{ $stdDrawAuto->std_konsentrasi ?? '-' }}</span></span>
                                                </td>
                                                <td class="p-2 align-top">
                                                    <div class="flex items-center">
                                                        <input type="number" step="0.01"
                                                            name="autowire[cek_1][ann_konsentrasi]"
                                                            value="{{ isset($dataAuto->ann_konsentrasi) ? str_replace('%', '', $dataAuto->ann_konsentrasi) : '' }}"
                                                            class="w-full border-slate-200 rounded-l text-xs p-1.5 text-center font-bold text-blue-600"
                                                            placeholder="0.0">
                                                        <span
                                                            class="bg-slate-100 border border-l-0 border-slate-200 px-2 py-1.5 text-[10px] font-bold text-slate-500 rounded-r">%</span>
                                                    </div>
                                                    <span
                                                        class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                        <span
                                                            class="font-bold text-slate-600">{{ $stdAnnAuto->std_konsentrasi ?? '-' }}</span></span>
                                                </td>
                                            </tr>

                                            {{-- pH Level --}}
                                            <tr>
                                                <td
                                                    class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                    pH Level</td>
                                                <td class="p-2 align-top">
                                                    <input type="number" step="0.1"
                                                        name="autowire[cek_1][draw_ph]"
                                                        value="{{ $dataAuto->draw_ph ?? '' }}"
                                                        class="w-full border-slate-200 rounded text-xs p-1.5 text-center font-bold text-emerald-700"
                                                        placeholder="0.0">
                                                    <span
                                                        class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                        <span
                                                            class="font-bold text-slate-600">{{ $stdDrawAuto->std_ph ?? '-' }}</span></span>
                                                </td>
                                                <td class="p-2 align-top">
                                                    <input type="number" step="0.1"
                                                        name="autowire[cek_1][ann_ph]"
                                                        value="{{ $dataAuto->ann_ph ?? '' }}"
                                                        class="w-full border-slate-200 rounded text-xs p-1.5 text-center font-bold text-emerald-700"
                                                        placeholder="0.0">
                                                    <span
                                                        class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                        <span
                                                            class="font-bold text-slate-600">{{ $stdAnnAuto->std_ph ?? '-' }}</span></span>
                                                </td>
                                            </tr>

                                            {{-- Temperatur --}}
                                            <tr>
                                                <td
                                                    class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                    Temperatur</td>
                                                <td class="p-2 align-top">
                                                    <div class="flex items-center">
                                                        <input type="number" step="1"
                                                            name="autowire[cek_1][draw_temp]"
                                                            value="{{ isset($dataAuto->draw_temp) ? str_replace(['°C', 'C'], '', $dataAuto->draw_temp) : '' }}"
                                                            class="w-full border-slate-200 rounded-l text-xs p-1.5 text-center"
                                                            placeholder="0">
                                                        <span
                                                            class="bg-slate-100 border border-l-0 border-slate-200 px-1 py-1.5 text-[10px] font-bold text-slate-500 rounded-r">°C</span>
                                                    </div>
                                                    <span
                                                        class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                        <span
                                                            class="font-bold text-slate-600">{{ $stdDrawAuto->std_temp ?? '-' }}</span></span>
                                                </td>
                                                <td class="p-2 align-top">
                                                    <div class="flex items-center">
                                                        <input type="number" step="1"
                                                            name="autowire[cek_1][ann_temp]"
                                                            value="{{ isset($dataAuto->ann_temp) ? str_replace(['°C', 'C'], '', $dataAuto->ann_temp) : '' }}"
                                                            class="w-full border-slate-200 rounded-l text-xs p-1.5 text-center"
                                                            placeholder="0">
                                                        <span
                                                            class="bg-slate-100 border border-l-0 border-slate-200 px-1 py-1.5 text-[10px] font-bold text-slate-500 rounded-r">°C</span>
                                                    </div>
                                                    <span
                                                        class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                        <span
                                                            class="font-bold text-slate-600">{{ $stdAnnAuto->std_temp ?? '-' }}</span></span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif


                        {{-- FOOTER INFO (Catatan & Tanda Tangan) --}}
                        <div
                            class="mt-10 grid grid-cols-1 md:grid-cols-2 gap-8 items-end border-t border-slate-200 pt-8">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Keterangan / Tindakan
                                    Perbaikan</label>
                                <textarea name="keterangan" rows="4" placeholder="Kosongkan jika tidak ada catatan..."
                                    class="w-full rounded-lg border-slate-300 shadow-sm focus:ring-indigo-500 text-sm">{{ $checks->first()->keterangan ?? '' }}</textarea>
                            </div>
                            <div class="flex flex-col items-end">
                                <div class="w-64 border-2 border-slate-800 bg-white shadow-sm">
                                    <div
                                        class="p-2 border-b-2 border-slate-800 bg-slate-50 text-[10px] font-bold text-slate-500 uppercase">
                                        Diperiksa Oleh:
                                    </div>
                                    <div class="p-3 text-sm font-black text-center uppercase truncate text-slate-800">
                                        {{ $checks->first()->pemeriksa->name ?? auth()->user()->name }}
                                    </div>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-2 font-bold">Terakhir diperbarui:
                                    <span
                                        class="font-normal">{{ $checks->first()->updated_at ?? 'Belum pernah diupdate' }}</span>
                                </p>
                            </div>
                        </div>

                    </div>

                    {{-- SUBMIT BAR --}}
                    <div
                        class="p-5 sm:px-6 bg-white border-t border-slate-200 flex justify-end gap-3 sticky bottom-0 z-30 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                        <button type="submit"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-2.5 rounded-xl font-bold text-sm shadow-md transition-all active:scale-95 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            Simpan Perubahan Matriks
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
