<template x-teleport="body">
    <div x-data="{
        {{-- Logika Tab --}}
        activePlantATab: 1,
            activeAutoTab: 1,
    
            {{-- Logika Pencarian Operator --}}
        operatorNik: '',
            operatorName: '........................',
            isSearching: false,
    
            searchOperator() {
                let nikInput = this.operatorNik.trim();
    
                if (nikInput.length === 0) {
                    this.operatorName = '........................';
                    return;
                }
    
                // Auto-pad NIK menjadi 4 digit (misal: 931 -> 0931)
                if (nikInput.length > 0 && nikInput.length < 4) {
                    nikInput = nikInput.padStart(4, '0');
                }
    
                this.isSearching = true;
    
                // SESUAIKAN JALUR: Tambahkan /eng sebelum /operator
                const targetUrl = `${window.location.origin}/eng/operator/search`;
    
                fetch(`${targetUrl}?nik=${nikInput}`)
                    .then(res => {
                        if (!res.ok) throw new Error('404 - Jalur /eng/operator/search tidak ditemukan');
                        return res.json();
                    })
                    .then(data => {
                        this.isSearching = false;
                        if (data.success) {
                            this.operatorName = data.name;
                        } else {
                            this.operatorName = 'DATA TIDAK DITEMUKAN';
                        }
                    })
                    .catch(err => {
                        this.isSearching = false;
                        console.error('Fetch Error:', err);
                        this.operatorName = 'ERROR KONEKSI';
                    });
            }
    }" x-show="showCompoundModal" style="display: none;"
        class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
        <div x-show="showCompoundModal" x-transition.opacity
            class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" @click="showCompoundModal = false"></div>

        <div class="flex min-h-full items-center justify-center p-2 text-center sm:p-0">
            <div x-show="showCompoundModal" x-transition
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-2xl transition-all sm:my-8 w-full sm:max-w-4xl border-t-8 border-blue-700">

                {{-- HEADER --}}
                <div
                    class="bg-white px-4 py-3 border-b border-slate-200 flex justify-between items-center sticky top-0 z-10">
                    <div>
                        <h2 class="text-lg sm:text-xl font-extrabold text-slate-800">Form Pengecekan Compound</h2>
                        <h3 class="text-[10px] sm:text-sm font-bold text-blue-600 uppercase">Engineering Department</h3>
                    </div>
                    <button type="button" @click="showCompoundModal = false"
                        class="text-slate-400 hover:text-red-500 bg-slate-100 rounded-full p-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form action="{{ route('eng.storeCompound') }}" method="POST">
                    @csrf

                    <div class="px-3 sm:px-6 py-4 bg-slate-50">
                        {{-- PILIH PLANT --}}
                        <div class="mb-4 w-full md:w-1/2">
                            <label class="block text-sm font-bold text-slate-700 mb-1">Pilih Area / Plant <span
                                    class="text-red-500">*</span></label>
                            <select name="plant" x-model="compoundForm.plant"
                                class="w-full rounded border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 font-bold bg-white"
                                required>
                                <option value="">-- Pilih Area --</option>
                                <option value="Plant A">Plant A </option>
                                <option value="Autowire">Plant A - Autowire (Mesin Drawing Multi 3 HONTA)</option>
                            </select>
                        </div>


                        {{-- ========================================================== --}}
                        {{-- UI MOBILE-FRIENDLY: PLANT A (TABS PER BAK)                 --}}
                        {{-- ========================================================== --}}
                        <div x-show="compoundForm.plant === 'Plant A'" style="display: none;"
                            class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-4">

                            {{-- Input Tanggal --}}
                            <div
                                class="p-3 bg-blue-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center gap-2">
                                <label class="font-bold text-sm text-slate-700">Tanggal Pengecekan Plant A:</label>
                                <input type="date" name="plant_a_tanggal"
                                    class="rounded border-slate-300 text-sm py-1.5 w-full sm:w-auto"
                                    :required="compoundForm.plant === 'Plant A'">
                            </div>

                            @php
                                // Array ini sekarang hanya menyimpan Nama Bak saja
                                $plantABaks = [
                                    1 => 'BAK 1 (HD 10 C)',
                                    2 => 'BAK 2 (MD 1)',
                                    3 => 'BAK 3 (QDMD Deyang)',
                                    4 => 'BAK 4 (Multi 2 Samp)',
                                    5 => 'BAK 5 (Multi 1 Samp)',
                                    6 => 'BAK 6 (Twin RBD Cu)',
                                ];
                            @endphp

                            {{-- Navigasi Tab (Bisa di-swipe horizontal di HP) --}}
                            <div
                                class="flex overflow-x-auto whitespace-nowrap bg-slate-100 border-b border-slate-200 p-2 gap-2 snap-x">
                                @foreach ($plantABaks as $key => $namaBak)
                                    <button type="button" @click="activePlantATab = {{ $key }}"
                                        :class="activePlantATab === {{ $key }} ? 'bg-blue-600 text-white shadow-md' :
                                            'bg-white text-slate-600 border border-slate-300 hover:bg-slate-50'"
                                        class="snap-start flex-none px-4 py-2 rounded-lg text-xs font-bold transition-all duration-200">
                                        {{ $namaBak }}
                                    </button>
                                @endforeach
                            </div>

                            {{-- Konten Tab Plant A --}}
                            <div class="p-2 sm:p-5">
                                @foreach ($plantABaks as $key => $namaBak)
                                    @php
                                        // AMBIL DATA STANDAR DARI DATABASE
                                        $stdMesin = $stdPlantA['bak_' . $key] ?? collect();
                                        $stdDraw = $stdMesin->where('proses', 'drawing')->first();
                                        $stdAnn = $stdMesin->where('proses', 'annealing')->first();
                                    @endphp

                                    <div x-show="activePlantATab === {{ $key }}" style="display: none;"
                                        x-transition.opacity>
                                        <div
                                            class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
                                            <div
                                                class="bg-slate-50 px-4 py-2 border-b border-slate-200 flex justify-between items-center">
                                                <span class="text-xs font-bold text-slate-700 uppercase">Aktivitas
                                                    Pengecekan BAK {{ $key }}</span>
                                                <span
                                                    class="text-[10px] text-slate-500 italic font-medium anim-pulse">Geser
                                                    tabel ke samping →</span>
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
                                                        {{-- Baris Type --}}
                                                        <tr>
                                                            <td
                                                                class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                                Type Item</td>
                                                            <td class="p-2 align-top">
                                                                <input type="text"
                                                                    name="plant_a[bak_{{ $key }}][draw_type]"
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
                                                                    class="w-full border-slate-200 rounded text-xs p-1.5 focus:ring-emerald-500 text-center bg-slate-50/50"
                                                                    placeholder="...">
                                                                <span
                                                                    class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                    <span
                                                                        class="font-bold text-slate-600">{{ $stdAnn->std_tipe ?? '-' }}</span></span>
                                                            </td>
                                                        </tr>

                                                        {{-- Baris Supplier --}}
                                                        <tr>
                                                            <td
                                                                class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                                Supplier</td>
                                                            <td class="p-2 align-top">
                                                                <input type="text"
                                                                    name="plant_a[bak_{{ $key }}][draw_supplier]"
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
                                                                    class="w-full border-slate-200 rounded text-xs p-1.5 focus:ring-emerald-500 text-center"
                                                                    placeholder="...">
                                                                <span
                                                                    class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                    <span
                                                                        class="font-bold text-slate-600">{{ $stdAnn->std_supplier ?? '-' }}</span></span>
                                                            </td>
                                                        </tr>

                                                        {{-- Baris Warna --}}
                                                        <tr>
                                                            <td
                                                                class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                                Warna</td>
                                                            <td class="p-2 align-top">
                                                                <input type="text"
                                                                    name="plant_a[bak_{{ $key }}][draw_warna]"
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
                                                                    class="w-full border-slate-200 rounded text-xs p-1.5 focus:ring-emerald-500 text-center"
                                                                    placeholder="...">
                                                                <span
                                                                    class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                    <span
                                                                        class="font-bold text-slate-600">{{ $stdAnn->std_warna ?? '-' }}</span></span>
                                                            </td>
                                                        </tr>

                                                        {{-- Baris Konsentrasi --}}
                                                        <tr>
                                                            <td
                                                                class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                                Konsentrasi</td>
                                                            <td class="p-2 align-top">
                                                                <div class="flex items-center">
                                                                    <input type="number" step="0.1"
                                                                        name="plant_a[bak_{{ $key }}][draw_konsentrasi]"
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

                                                        {{-- Baris pH --}}
                                                        <tr>
                                                            <td
                                                                class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                                pH Level</td>
                                                            <td class="p-2 align-top">
                                                                <input type="number" step="0.1"
                                                                    name="plant_a[bak_{{ $key }}][draw_ph]"
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
                                                                    class="w-full border-slate-200 rounded text-xs p-1.5 text-center font-bold text-emerald-700"
                                                                    placeholder="0.0">
                                                                <span
                                                                    class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                    <span
                                                                        class="font-bold text-slate-600">{{ $stdAnn->std_ph ?? '-' }}</span></span>
                                                            </td>
                                                        </tr>

                                                        {{-- Baris Temperatur --}}
                                                        <tr>
                                                            <td
                                                                class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                                Temperatur</td>
                                                            <td class="p-2 align-top">
                                                                <div class="flex items-center">
                                                                    <input type="number" step="1"
                                                                        name="plant_a[bak_{{ $key }}][draw_temp]"
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

                                        {{-- Navigasi Tombol --}}
                                        <div class="mt-4 flex justify-between items-center px-1">
                                            <button type="button" @click="activePlantATab = {{ $key - 1 }}"
                                                x-show="{{ $key }} > 1"
                                                class="text-[10px] bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded font-bold shadow-sm hover:bg-slate-50 transition">
                                                ← Sebelumnya
                                            </button>
                                            <div x-show="{{ $key }} == 1"></div>
                                            <button type="button" @click="activePlantATab = {{ $key + 1 }}"
                                                x-show="{{ $key }} < 6"
                                                class="text-[10px] bg-slate-800 text-white px-5 py-2 rounded font-bold shadow flex items-center gap-2 hover:bg-slate-700 transition">
                                                Lanjut BAK {{ $key + 1 }} →
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        {{-- UI MOBILE-FRIENDLY: AUTOWIRE (TABS PER TANGGAL CEK)        --}}
                        <div x-show="compoundForm.plant === 'Autowire'" style="display: none;"
                            class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-4">

                            <div class="p-3 bg-blue-50 border-b border-slate-200 flex justify-between items-center">
                                <h4 class="font-bold text-sm text-slate-800">Mesin Drawing Multi 3 HONTA (Pengecekan
                                    Mingguan)</h4>
                                <span class="text-[10px] text-blue-600 font-bold italic animate-pulse">Geser tabel ke
                                    samping →</span>
                            </div>

                            {{-- Navigasi Tab Autowire --}}
                            <div
                                class="flex overflow-x-auto whitespace-nowrap bg-slate-100 border-b border-slate-200 p-2 gap-2 snap-x">
                                @for ($i = 1; $i <= 4; $i++)
                                    <button type="button" @click="activeAutoTab = {{ $i }}"
                                        :class="activeAutoTab === {{ $i }} ? 'bg-blue-600 text-white shadow-md' :
                                            'bg-white text-slate-600 border border-slate-300 hover:bg-slate-50'"
                                        class="snap-start flex-none px-4 py-2 rounded-lg text-xs font-bold transition-all duration-200">
                                        Pengecekan {{ $i }}
                                    </button>
                                @endfor
                            </div>

                            {{-- Konten Tab Autowire --}}
                            <div class="p-4 sm:p-5">
                                @for ($i = 1; $i <= 4; $i++)
                                    @php
                                        // AMBIL DATA STANDAR AUTOWIRE DARI DATABASE
                                        $stdMesinAuto = $stdAutowire['cek_' . $i] ?? collect();
                                        $stdDrawAuto = $stdMesinAuto->where('proses', 'drawing')->first();
                                        $stdAnnAuto = $stdMesinAuto->where('proses', 'annealing')->first();
                                    @endphp

                                    <div x-show="activeAutoTab === {{ $i }}" style="display: none;"
                                        x-transition.opacity>
                                        <div class="mb-4 flex items-center gap-3">
                                            <label
                                                class="font-bold text-xs text-slate-700 uppercase tracking-wider">Tanggal
                                                Cek {{ $i }}:</label>
                                            <input type="date" name="autowire[cek_{{ $i }}][tanggal]"
                                                class="rounded border-slate-300 text-sm py-1.5 w-full md:w-1/3 focus:ring-blue-500">
                                        </div>

                                        {{-- Tabel Scroll Horizontal Autowire --}}
                                        <div
                                            class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-sm text-left border-collapse">
                                                    <thead>
                                                        <tr class="bg-slate-50">
                                                            <th
                                                                class="p-3 text-[10px] font-extrabold text-slate-500 uppercase border-b sticky left-0 bg-slate-50 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                                                Parameter</th>
                                                            <th
                                                                class="p-3 text-[10px] font-extrabold text-blue-600 uppercase border-b text-center min-w-[150px]">
                                                                Drawing Compound</th>
                                                            <th
                                                                class="p-3 text-[10px] font-extrabold text-emerald-600 uppercase border-b text-center min-w-[150px]">
                                                                Annealing Compound</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        {{-- Baris Type --}}
                                                        <tr>
                                                            <td
                                                                class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                                Type Item</td>
                                                            <td class="p-2 align-top">
                                                                <input type="text"
                                                                    name="autowire[cek_{{ $i }}][draw_type]"
                                                                    class="w-full border-slate-200 rounded text-xs p-1.5 text-center focus:ring-blue-500 bg-slate-50/50"
                                                                    placeholder="...">
                                                                <span
                                                                    class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                    <span
                                                                        class="font-bold text-slate-600">{{ $stdDrawAuto->std_tipe ?? '-' }}</span></span>
                                                            </td>
                                                            <td class="p-2 align-top">
                                                                <input type="text"
                                                                    name="autowire[cek_{{ $i }}][ann_type]"
                                                                    class="w-full border-slate-200 rounded text-xs p-1.5 text-center focus:ring-emerald-500 bg-slate-50/50"
                                                                    placeholder="...">
                                                                <span
                                                                    class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                    <span
                                                                        class="font-bold text-slate-600">{{ $stdAnnAuto->std_tipe ?? '-' }}</span></span>
                                                            </td>
                                                        </tr>

                                                        {{-- Baris Supplier --}}
                                                        <tr>
                                                            <td
                                                                class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                                Supplier</td>
                                                            <td class="p-2 align-top">
                                                                <input type="text"
                                                                    name="autowire[cek_{{ $i }}][draw_supplier]"
                                                                    class="w-full border-slate-200 rounded text-xs p-1.5 text-center focus:ring-blue-500"
                                                                    placeholder="...">
                                                                <span
                                                                    class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                    <span
                                                                        class="font-bold text-slate-600">{{ $stdDrawAuto->std_supplier ?? '-' }}</span></span>
                                                            </td>
                                                            <td class="p-2 align-top">
                                                                <input type="text"
                                                                    name="autowire[cek_{{ $i }}][ann_supplier]"
                                                                    class="w-full border-slate-200 rounded text-xs p-1.5 text-center focus:ring-emerald-500"
                                                                    placeholder="...">
                                                                <span
                                                                    class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                    <span
                                                                        class="font-bold text-slate-600">{{ $stdAnnAuto->std_supplier ?? '-' }}</span></span>
                                                            </td>
                                                        </tr>

                                                        {{-- Baris Warna --}}
                                                        <tr>
                                                            <td
                                                                class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                                Warna</td>
                                                            <td class="p-2 align-top">
                                                                <input type="text"
                                                                    name="autowire[cek_{{ $i }}][draw_warna]"
                                                                    class="w-full border-slate-200 rounded text-xs p-1.5 text-center focus:ring-blue-500"
                                                                    placeholder="...">
                                                                <span
                                                                    class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                    <span
                                                                        class="font-bold text-slate-600">{{ $stdDrawAuto->std_warna ?? '-' }}</span></span>
                                                            </td>
                                                            <td class="p-2 align-top">
                                                                <input type="text"
                                                                    name="autowire[cek_{{ $i }}][ann_warna]"
                                                                    class="w-full border-slate-200 rounded text-xs p-1.5 text-center focus:ring-emerald-500"
                                                                    placeholder="...">
                                                                <span
                                                                    class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                    <span
                                                                        class="font-bold text-slate-600">{{ $stdAnnAuto->std_warna ?? '-' }}</span></span>
                                                            </td>
                                                        </tr>

                                                        {{-- Baris Konsentrasi --}}
                                                        <tr>
                                                            <td
                                                                class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                                Konsentrasi</td>
                                                            <td class="p-2 align-top">
                                                                <div class="flex items-center">
                                                                    <input type="number" step="0.1"
                                                                        name="autowire[cek_{{ $i }}][draw_konsentrasi]"
                                                                        class="w-full border-slate-200 rounded-l text-xs p-1.5 text-center focus:ring-blue-500 font-bold text-blue-600"
                                                                        placeholder="0.0">
                                                                    <span
                                                                        class="bg-slate-50 border border-l-0 border-slate-200 px-2 py-1.5 text-[10px] font-bold text-slate-400 rounded-r">%</span>
                                                                </div>
                                                                <span
                                                                    class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                    <span
                                                                        class="font-bold text-slate-600">{{ $stdDrawAuto->std_konsentrasi ?? '-' }}</span></span>
                                                            </td>
                                                            <td class="p-2 align-top">
                                                                <div class="flex items-center">
                                                                    <input type="number" step="0.01"
                                                                        name="autowire[cek_{{ $i }}][ann_konsentrasi]"
                                                                        class="w-full border-slate-200 rounded-l text-xs p-1.5 text-center focus:ring-emerald-500 font-bold text-blue-600"
                                                                        placeholder="0.0">
                                                                    <span
                                                                        class="bg-slate-50 border border-l-0 border-slate-200 px-2 py-1.5 text-[10px] font-bold text-slate-400 rounded-r">%</span>
                                                                </div>
                                                                <span
                                                                    class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                    <span
                                                                        class="font-bold text-slate-600">{{ $stdAnnAuto->std_konsentrasi ?? '-' }}</span></span>
                                                            </td>
                                                        </tr>

                                                        {{-- Baris pH --}}
                                                        <tr>
                                                            <td
                                                                class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                                pH Level</td>
                                                            <td class="p-2 align-top">
                                                                <input type="number" step="0.1"
                                                                    name="autowire[cek_{{ $i }}][draw_ph]"
                                                                    class="w-full border-slate-200 rounded text-xs p-1.5 text-center font-bold text-blue-600 focus:ring-blue-500"
                                                                    placeholder="0.0">
                                                                <span
                                                                    class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                    <span
                                                                        class="font-bold text-slate-600">{{ $stdDrawAuto->std_ph ?? '-' }}</span></span>
                                                            </td>
                                                            <td class="p-2 align-top">
                                                                <input type="number" step="0.1"
                                                                    name="autowire[cek_{{ $i }}][ann_ph]"
                                                                    class="w-full border-slate-200 rounded text-xs p-1.5 text-center font-bold text-emerald-600 focus:ring-emerald-500"
                                                                    placeholder="0.0">
                                                                <span
                                                                    class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                    <span
                                                                        class="font-bold text-slate-600">{{ $stdAnnAuto->std_ph ?? '-' }}</span></span>
                                                            </td>
                                                        </tr>

                                                        {{-- Baris Temperatur --}}
                                                        <tr>
                                                            <td
                                                                class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                                Temperatur</td>
                                                            <td class="p-2 align-top">
                                                                <div class="flex items-center">
                                                                    <input type="number" step="1"
                                                                        name="autowire[cek_{{ $i }}][draw_temp]"
                                                                        class="w-full border-slate-200 rounded-l text-xs p-1.5 text-center focus:ring-blue-500"
                                                                        placeholder="0">
                                                                    <span
                                                                        class="bg-slate-50 border border-l-0 border-slate-200 px-1 py-1.5 text-[10px] font-bold text-slate-400 rounded-r">°C</span>
                                                                </div>
                                                                <span
                                                                    class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                    <span
                                                                        class="font-bold text-slate-600">{{ $stdDrawAuto->std_temp ?? '-' }}</span></span>
                                                            </td>
                                                            <td class="p-2 align-top">
                                                                <div class="flex items-center">
                                                                    <input type="number" step="1"
                                                                        name="autowire[cek_{{ $i }}][ann_temp]"
                                                                        class="w-full border-slate-200 rounded-l text-xs p-1.5 text-center focus:ring-emerald-500"
                                                                        placeholder="0">
                                                                    <span
                                                                        class="bg-slate-50 border border-l-0 border-slate-200 px-1 py-1.5 text-[10px] font-bold text-slate-400 rounded-r">°C</span>
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

                                        {{-- Navigasi Tombol --}}
                                        <div class="mt-4 flex justify-between items-center">
                                            @if ($i > 1)
                                                <button type="button" @click="activeAutoTab = {{ $i - 1 }}"
                                                    class="text-[10px] bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded font-bold shadow-sm hover:bg-slate-50 transition">
                                                    ← Sebelumnya
                                                </button>
                                            @else
                                                <div></div>
                                            @endif

                                            @if ($i < 4)
                                                <button type="button" @click="activeAutoTab = {{ $i + 1 }}"
                                                    class="text-[10px] bg-slate-800 text-white px-5 py-2 rounded font-bold shadow flex items-center gap-2 hover:bg-slate-700 transition">
                                                    Lanjut ke Pengecekan {{ $i + 1 }} →
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>

                        {{-- KETERANGAN, CATATAN & TANDA TANGAN --}}
                        <div x-show="compoundForm.plant" style="display: none;"
                            class="mt-4 flex flex-col md:flex-row gap-4 text-left items-end">
                            <div class="w-full md:w-1/3">
                                <label class="block text-sm font-bold text-slate-700 mb-1">Keterangan / Notes</label>
                                <textarea name="keterangan" rows="4"
                                    class="w-full rounded border-slate-300 shadow-sm focus:border-blue-500 text-sm py-2"></textarea>
                            </div>

                            <div
                                class="w-full md:w-1/3 text-[11px] text-slate-600 bg-slate-100 p-3 rounded border border-slate-200 h-full">
                                <span class="font-bold text-slate-800">Catatan Standar:</span>
                                <ul class="list-disc pl-4 mt-1 space-y-1">
                                    <li>Pengukuran dilakukan setiap minggu pada Shift 1.</li>
                                    <li>Bila terjadi hal meragukan (compound berbau, warna berubah), segera info ke
                                        Engineering.</li>
                                    <li>Bila konsentrasi berkurang tambah compound, bila tinggi tambah air.</li>
                                </ul>
                            </div>

                            <div class="w-full md:w-1/2 flex justify-end">
                                <div class="w-full max-w-sm border-2 border-slate-800 bg-white shadow-sm">
                                    {{-- Bagian Operator --}}
                                    <div class="flex border-b-2 border-slate-800">
                                        <div
                                            class="w-2/5 p-2 font-bold border-r-2 border-slate-800 bg-slate-50 text-[10px] uppercase flex flex-col justify-center text-left">
                                            Diperiksa Oleh:
                                            <input type="text" x-model="operatorNik"
                                                @keyup.debounce.500ms="searchOperator()" placeholder="Ketik NIK..."
                                                class="mt-1 text-[10px] p-1 border-slate-300 rounded focus:ring-indigo-500 font-bold uppercase w-full">
                                        </div>
                                        <div
                                            class="w-3/5 p-3 text-sm font-black text-center uppercase truncate text-blue-700 bg-blue-50/50 flex items-center justify-center">
                                            <span x-show="isSearching"
                                                class="text-slate-400 animate-pulse text-[10px]">Mencari...</span>
                                            <span x-show="!isSearching" x-text="operatorName"
                                                :class="operatorName === 'DATA TIDAK DITEMUKAN' ?
                                                    'text-red-500 font-bold text-[10px]' : ''"></span>

                                            {{-- Hidden input untuk mengirim nama ke server --}}
                                            <input type="hidden" name="nama_pemeriksa" :value="operatorName">
                                        </div>
                                    </div>

                                    {{-- Bagian Foreman (Login User) --}}
                                    <div class="flex">
                                        <div
                                            class="w-2/5 p-2 border-r-2 border-slate-800 bg-slate-50 text-[10px] font-bold uppercase text-left">
                                            Diketahui Oleh:
                                        </div>
                                        <div
                                            class="w-3/5 p-2 text-xs font-bold text-center uppercase truncate text-slate-800 flex items-center justify-center">
                                            {{ auth()->user()->name }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- FOOTER SUBMIT --}}
                    <div class="bg-slate-100 px-4 py-3 flex justify-end gap-2 border-t border-slate-200">
                        <button type="button" @click="showCompoundModal = false"
                            class="px-4 py-2 border border-slate-300 rounded text-sm font-medium text-slate-700 bg-white hover:bg-slate-50">Batal</button>
                        <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded text-sm font-bold hover:bg-blue-700 shadow">Simpan
                            Data</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</template>
