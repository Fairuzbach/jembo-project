<template x-teleport="body">
    <div x-data="{
        activePlantATab: 1,
        activeAutoTab: 1,
    
        operatorNik: '',
        operatorName: '........................',
        isSearching: false,
    
        searchOperator() {
            let nikInput = this.operatorNik.trim();
            if (nikInput.length === 0) {
                this.operatorName = '........................';
                return;
            }
            this.isSearching = true;
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

        {{-- Backdrop --}}
        <div x-show="showCompoundModal" x-transition.opacity
            class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" @click="showCompoundModal = false"></div>

        {{-- Modal Container — bottom sheet di mobile, centered di sm+ --}}
        <div class="flex min-h-full items-end sm:items-center justify-center p-0 sm:p-4 text-center">
            <div x-show="showCompoundModal" x-transition
                class="relative transform overflow-hidden rounded-t-2xl sm:rounded-xl bg-white text-left shadow-2xl transition-all w-full sm:my-8 sm:max-w-4xl border-t-4 sm:border-t-8 border-blue-700 flex flex-col max-h-[95dvh] sm:max-h-[90vh]">

                {{-- HEADER — Sticky --}}
                <div
                    class="bg-white px-4 py-3 border-b border-slate-200 flex justify-between items-center shrink-0 sticky top-0 z-10">
                    <div class="flex-1 min-w-0">
                        <h2 class="text-base sm:text-xl font-extrabold text-slate-800 truncate">Form Pengecekan Compound
                        </h2>
                        <h3 class="text-[10px] sm:text-sm font-bold text-blue-600 uppercase">Engineering Department</h3>
                    </div>
                    <button type="button" @click="showCompoundModal = false"
                        class="ml-3 shrink-0 text-slate-400 hover:text-red-500 bg-slate-100 rounded-full p-2 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                {{-- SCROLLABLE BODY --}}
                <div class="overflow-y-auto grow">
                    <form id="formCompound" action="{{ route('eng.storeCompound') }}" method="POST">
                        @csrf

                        @php
                            if (!function_exists('generateDropdownOptions')) {
                                function generateDropdownOptions($stdString, $type)
                                {
                                    if (empty($stdString)) {
                                        return [];
                                    }
                                    preg_match_all('/[0-9]+(?:\.[0-9]+)?/', $stdString, $matches);
                                    if (empty($matches[0])) {
                                        return [];
                                    }
                                    $nums = array_map('floatval', $matches[0]);
                                    $stdMin = count($nums) >= 2 ? min($nums) : $nums[0];
                                    $stdMax = count($nums) >= 2 ? max($nums) : $nums[0];
                                    $minLimit = $stdMin;
                                    $maxLimit = $stdMax;
                                    $step = 1;
                                    if ($type === 'draw_kons' || str_contains($type, 'ph')) {
                                        $minLimit = $stdMin - 3;
                                        $maxLimit = $stdMax + 3;
                                        $step = 0.5;
                                    } elseif ($type === 'ann_kons') {
                                        $minLimit = $stdMin - 0.5;
                                        $maxLimit = $stdMax + 0.5;
                                        $step = 0.5;
                                    }
                                    if ($minLimit < 0) {
                                        $minLimit = 0;
                                    }
                                    $options = [];
                                    for ($i = $minLimit; $i <= $maxLimit + 0.01; $i += $step) {
                                        $options[] = floatval(number_format($i, 1, '.', ''));
                                    }
                                    return array_unique($options);
                                }
                            }
                        @endphp

                        <div class="px-3 sm:px-6 py-4 bg-slate-50">

                            {{-- PILIH PLANT --}}
                            <div class="mb-4">
                                <label class="block text-sm font-bold text-slate-700 mb-1">Pilih Area / Plant <span
                                        class="text-red-500">*</span></label>
                                <select name="plant" x-model="compoundForm.plant"
                                    class="w-full sm:w-1/2 rounded border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 font-bold bg-white"
                                    required>
                                    <option value="">-- Pilih Area --</option>
                                    <option value="Plant A">Plant A</option>
                                    <option value="Autowire">Plant A - Autowire (Mesin Drawing Multi 3 HONTA)</option>
                                </select>
                            </div>

                            {{-- ========================================================== --}}
                            {{-- UI PLANT A (TABS PER BAK)                                  --}}
                            {{-- ========================================================== --}}
                            <div x-show="compoundForm.plant === 'Plant A'" style="display: none;"
                                class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-4">

                                {{-- Input Tanggal --}}
                                <div
                                    class="px-4 py-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center gap-2">
                                    <label
                                        class="font-extrabold text-[10px] text-slate-700 uppercase tracking-wider shrink-0">
                                        Tanggal Cek <span class="text-red-500">*</span>:
                                    </label>
                                    <div>
                                        <input type="date" name="plant_a_tanggal" id="plant_a_tanggal"
                                            value="{{ $tanggal ?? date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required
                                            class="w-full sm:w-auto rounded border-slate-300 text-sm py-2 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm bg-white cursor-pointer">
                                        <small class="block text-[9px] text-slate-400 mt-1 italic">* Maksimal tanggal
                                            hari ini</small>
                                    </div>
                                </div>

                                @php
                                    $plantABaks = [
                                        1 => 'BAK 1 (HD 10 C)',
                                        2 => 'BAK 2 (MD 1)',
                                        3 => 'BAK 3 (QDMD Deyang)',
                                        4 => 'BAK 4 (Multi 2 Samp)',
                                        5 => 'BAK 5 (Multi 1 Samp)',
                                        6 => 'BAK 6 (Twin RBD Cu)',
                                    ];
                                @endphp

                                {{-- Navigasi Tab BAK — scrollable pada mobile --}}
                                <div
                                    class="flex overflow-x-auto bg-slate-100 border-b border-slate-200 p-2 gap-1.5 snap-x scrollbar-hide">
                                    @foreach ($plantABaks as $key => $namaBak)
                                        <button type="button" @click="activePlantATab = {{ $key }}"
                                            :class="activePlantATab === {{ $key }} ?
                                                'bg-blue-600 text-white shadow-md' :
                                                'bg-white text-slate-600 border border-slate-300 hover:bg-slate-50'"
                                            class="snap-start flex-none px-3 py-2 rounded-lg text-xs font-bold transition-all duration-200 min-h-[38px] whitespace-nowrap">
                                            {{-- Label singkat di mobile, lengkap di desktop --}}
                                            <span class="sm:hidden">BAK {{ $key }}</span>
                                            <span class="hidden sm:inline">{{ $namaBak }}</span>
                                        </button>
                                    @endforeach
                                </div>

                                {{-- Konten Tab Plant A --}}
                                <div class="p-2 sm:p-4">
                                    @foreach ($plantABaks as $key => $namaBak)
                                        @php
                                            $stdMesin = $stdPlantA['bak_' . $key] ?? collect();
                                            $stdDraw = $stdMesin->first(
                                                fn($item) => strtolower($item->proses) === 'drawing',
                                            );
                                            $stdAnn = $stdMesin->first(
                                                fn($item) => strtolower($item->proses) === 'annealing',
                                            );
                                        @endphp

                                        <div x-show="activePlantATab === {{ $key }}" style="display: none;"
                                            x-transition.opacity>

                                            {{-- Header BAK dengan nama lengkap (visible di mobile juga) --}}
                                            <div class="flex items-center justify-between mb-3">
                                                <h4 class="text-sm font-bold text-slate-800">{{ $namaBak }}</h4>
                                                <span class="text-[10px] text-slate-400 italic sm:hidden">← geser
                                                    tabel</span>
                                            </div>

                                            <div
                                                class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
                                                <div class="overflow-x-auto -webkit-overflow-scrolling-touch">
                                                    <table class="w-full text-sm text-left border-collapse">
                                                        <thead>
                                                            <tr class="bg-slate-50/50">
                                                                <th
                                                                    class="p-3 text-[10px] font-extrabold text-slate-500 uppercase border-b sticky left-0 bg-slate-50 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] min-w-[100px]">
                                                                    Parameter</th>
                                                                <th
                                                                    class="p-3 text-[10px] font-extrabold text-blue-600 uppercase border-b text-center min-w-[130px]">
                                                                    Drawing</th>
                                                                <th class="p-3 text-[10px] font-extrabold text-emerald-600 uppercase border-b text-center {{ $key == 6 ? 'min-w-[260px]' : 'min-w-[130px]' }}"
                                                                    colspan="{{ $key == 6 ? 2 : 1 }}">
                                                                    Annealing {{ $key == 6 ? '(Twin RBD CU)' : '' }}
                                                                </th>
                                                            </tr>
                                                            @if ($key == 6)
                                                                <tr
                                                                    class="bg-slate-100/50 text-[9px] uppercase font-bold text-slate-500">
                                                                    <th class="border-b"></th>
                                                                    <th class="border-b"></th>
                                                                    <th
                                                                        class="p-1 border-b text-center border-r border-slate-200 bg-emerald-50/50 min-w-[130px]">
                                                                        Bak Ann 1</th>
                                                                    <th
                                                                        class="p-1 border-b text-center bg-indigo-50/50 min-w-[130px]">
                                                                        Bak Ann 2</th>
                                                                </tr>
                                                            @endif
                                                        </thead>
                                                        <tbody class="divide-y divide-slate-200">

                                                            {{-- Type Item --}}
                                                            <tr>
                                                                <td
                                                                    class="p-3 text-xs font-bold text-slate-800 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                                    Type Item</td>
                                                                <td class="p-2 align-top">
                                                                    <select
                                                                        name="plant_a[bak_{{ $key }}][draw_type]"
                                                                        class="w-full border-slate-300 rounded text-sm p-2 text-center bg-slate-50 font-medium focus:ring-2 focus:ring-blue-500 cursor-pointer">
                                                                        <option value="">-- Pilih --</option>
                                                                        @if (!empty($stdDraw->std_tipe))
                                                                            <option value="{{ $stdDraw->std_tipe }}">
                                                                                {{ $stdDraw->std_tipe }}</option>
                                                                        @endif
                                                                    </select>
                                                                    <span
                                                                        class="block text-[11px] text-slate-500 text-center mt-1">Std:
                                                                        <b>{{ $stdDraw->std_tipe ?? '-' }}</b></span>
                                                                </td>
                                                                <td
                                                                    class="p-2 align-top {{ $key == 6 ? 'border-r border-slate-300' : '' }}">
                                                                    <select
                                                                        name="plant_a[bak_{{ $key }}][ann_type]"
                                                                        class="w-full border-slate-300 rounded text-sm p-2 text-center bg-slate-50 font-medium focus:ring-2 focus:ring-emerald-500 cursor-pointer">
                                                                        <option value="">-- Pilih --</option>
                                                                        @if (!empty($stdAnn->std_tipe))
                                                                            <option value="{{ $stdAnn->std_tipe }}">
                                                                                {{ $stdAnn->std_tipe }}</option>
                                                                        @endif
                                                                    </select>
                                                                    <span
                                                                        class="block text-[11px] text-slate-500 text-center mt-1">Std:
                                                                        <b>{{ $stdAnn->std_tipe ?? '-' }}</b></span>
                                                                </td>
                                                                @if ($key == 6)
                                                                    <td class="p-2 align-top bg-indigo-50/40">
                                                                        <select
                                                                            name="plant_a[bak_{{ $key }}][ann_type_2]"
                                                                            class="w-full border-indigo-300 rounded text-sm p-2 text-center bg-white shadow-sm font-medium focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                                                                            <option value="">-- Pilih --</option>
                                                                            @if (!empty($stdAnn->std_tipe))
                                                                                <option
                                                                                    value="{{ $stdAnn->std_tipe }}">
                                                                                    {{ $stdAnn->std_tipe }}</option>
                                                                            @endif
                                                                        </select>
                                                                        <span
                                                                            class="block text-[11px] text-indigo-500 text-center mt-1">Std:
                                                                            <b>{{ $stdAnn->std_tipe ?? '-' }}</b></span>
                                                                    </td>
                                                                @endif
                                                            </tr>

                                                            {{-- Supplier --}}
                                                            <tr>
                                                                <td
                                                                    class="p-3 text-xs font-bold text-slate-800 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                                    Supplier</td>
                                                                <td class="p-2 align-top">
                                                                    <select
                                                                        name="plant_a[bak_{{ $key }}][draw_supplier]"
                                                                        class="w-full border-slate-300 rounded text-sm p-2 text-center focus:ring-2 focus:ring-blue-500 cursor-pointer">
                                                                        <option value="">-- Pilih --</option>
                                                                        @if (!empty($stdDraw->std_supplier))
                                                                            <option
                                                                                value="{{ $stdDraw->std_supplier }}">
                                                                                {{ $stdDraw->std_supplier }}</option>
                                                                        @endif
                                                                    </select>
                                                                    <span
                                                                        class="block text-[11px] text-slate-500 text-center mt-1">Std:
                                                                        <b>{{ $stdDraw->std_supplier ?? '-' }}</b></span>
                                                                </td>
                                                                <td
                                                                    class="p-2 align-top {{ $key == 6 ? 'border-r border-slate-300' : '' }}">
                                                                    <select
                                                                        name="plant_a[bak_{{ $key }}][ann_supplier]"
                                                                        class="w-full border-slate-300 rounded text-sm p-2 text-center focus:ring-2 focus:ring-emerald-500 cursor-pointer">
                                                                        <option value="">-- Pilih --</option>
                                                                        @if (!empty($stdAnn->std_supplier))
                                                                            <option
                                                                                value="{{ $stdAnn->std_supplier }}">
                                                                                {{ $stdAnn->std_supplier }}</option>
                                                                        @endif
                                                                    </select>
                                                                    <span
                                                                        class="block text-[11px] text-slate-500 text-center mt-1">Std:
                                                                        <b>{{ $stdAnn->std_supplier ?? '-' }}</b></span>
                                                                </td>
                                                                @if ($key == 6)
                                                                    <td class="p-2 align-top bg-indigo-50/40">
                                                                        <select
                                                                            name="plant_a[bak_{{ $key }}][ann_supplier_2]"
                                                                            class="w-full border-indigo-300 rounded text-sm p-2 text-center bg-white shadow-sm focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                                                                            <option value="">-- Pilih --</option>
                                                                            @if (!empty($stdAnn->std_supplier))
                                                                                <option
                                                                                    value="{{ $stdAnn->std_supplier }}">
                                                                                    {{ $stdAnn->std_supplier }}
                                                                                </option>
                                                                            @endif
                                                                        </select>
                                                                        <span
                                                                            class="block text-[11px] text-indigo-500 text-center mt-1">Std:
                                                                            <b>{{ $stdAnn->std_supplier ?? '-' }}</b></span>
                                                                    </td>
                                                                @endif
                                                            </tr>

                                                            {{-- Warna --}}
                                                            <tr>
                                                                <td
                                                                    class="p-3 text-xs font-bold text-slate-800 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                                    Warna</td>
                                                                <td class="p-2 align-top">
                                                                    <select
                                                                        name="plant_a[bak_{{ $key }}][draw_warna]"
                                                                        class="check-oos w-full border-slate-300 rounded text-sm p-2 text-center font-medium focus:ring-2 focus:ring-blue-500 cursor-pointer"
                                                                        data-std="{{ $stdDraw->std_warna ?? '' }}">
                                                                        <option value="">- Pilih Warna -</option>
                                                                        <option value="Hijau Putih">Hijau Putih
                                                                        </option>
                                                                        <option value="Putih">Putih</option>
                                                                        <option value="Cokelat Kekuningan">Cokelat
                                                                            Kekuningan</option>
                                                                    </select>
                                                                    <span
                                                                        class="block text-[11px] text-slate-500 text-center mt-1">Std:
                                                                        <b>{{ $stdDraw->std_warna ?? '-' }}</b></span>
                                                                </td>
                                                                <td
                                                                    class="p-2 align-top {{ $key == 6 ? 'border-r border-slate-300' : '' }}">
                                                                    <select
                                                                        name="plant_a[bak_{{ $key }}][ann_warna]"
                                                                        class="check-oos w-full border-slate-300 rounded text-sm p-2 text-center font-medium focus:ring-2 focus:ring-emerald-500 cursor-pointer"
                                                                        data-std="{{ $stdAnn->std_warna ?? '' }}">
                                                                        <option value="">- Pilih Warna -</option>
                                                                        <option value="Hijau Putih">Hijau Putih
                                                                        </option>
                                                                        <option value="Putih">Putih</option>
                                                                        <option value="Cokelat Kekuningan">Cokelat
                                                                            Kekuningan</option>
                                                                    </select>
                                                                    <span
                                                                        class="block text-[11px] text-slate-500 text-center mt-1">Std:
                                                                        <b>{{ $stdAnn->std_warna ?? '-' }}</b></span>
                                                                </td>
                                                                @if ($key == 6)
                                                                    <td class="p-2 align-top bg-indigo-50/40">
                                                                        <select
                                                                            name="plant_a[bak_{{ $key }}][ann_warna_2]"
                                                                            class="check-oos w-full border-indigo-300 rounded text-sm p-2 text-center font-medium bg-white shadow-sm focus:ring-2 focus:ring-indigo-500 cursor-pointer"
                                                                            data-std="{{ $stdAnn->std_warna ?? '' }}">
                                                                            <option value="">- Pilih Warna -
                                                                            </option>
                                                                            <option value="Hijau Putih">Hijau Putih
                                                                            </option>
                                                                            <option value="Putih">Putih</option>
                                                                            <option value="Cokelat Kekuningan">Cokelat
                                                                                Kekuningan</option>
                                                                        </select>
                                                                        <span
                                                                            class="block text-[11px] text-indigo-500 text-center mt-1">Std:
                                                                            <b>{{ $stdAnn->std_warna ?? '-' }}</b></span>
                                                                    </td>
                                                                @endif
                                                            </tr>

                                                            {{-- Konsentrasi --}}
                                                            <tr>
                                                                <td
                                                                    class="p-3 text-xs font-bold text-slate-800 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                                    Konsentrasi</td>
                                                                <td class="p-2 align-top">
                                                                    <select
                                                                        name="plant_a[bak_{{ $key }}][draw_konsentrasi]"
                                                                        class="check-oos w-full border-slate-300 rounded text-sm p-2 text-center font-bold text-blue-700 focus:ring-2 focus:ring-blue-500 cursor-pointer"
                                                                        data-std="{{ $stdDraw->std_konsentrasi }}">
                                                                        <option value="">- Pilih -</option>
                                                                        @foreach (generateDropdownOptions($stdDraw->std_konsentrasi, 'draw_kons') as $opt)
                                                                            <option value="{{ $opt }}">
                                                                                {{ $opt }} %</option>
                                                                        @endforeach
                                                                    </select>
                                                                    <span
                                                                        class="block text-[11px] text-slate-500 text-center mt-1">Std:
                                                                        <b>{{ $stdDraw->std_konsentrasi ?? '-' }}</b></span>
                                                                </td>
                                                                <td
                                                                    class="p-2 align-top {{ $key == 6 ? 'border-r border-slate-300' : '' }}">
                                                                    <select
                                                                        name="plant_a[bak_{{ $key }}][ann_konsentrasi]"
                                                                        class="check-oos w-full border-slate-300 rounded text-sm p-2 text-center font-bold text-emerald-700 focus:ring-2 focus:ring-emerald-500 cursor-pointer"
                                                                        data-std="{{ $stdAnn->std_konsentrasi }}">
                                                                        <option value="">- Pilih -</option>
                                                                        @foreach (generateDropdownOptions($stdAnn->std_konsentrasi, 'ann_kons') as $opt)
                                                                            <option value="{{ $opt }}">
                                                                                {{ $opt }} %</option>
                                                                        @endforeach
                                                                    </select>
                                                                    <span
                                                                        class="block text-[11px] text-slate-500 text-center mt-1">Std:
                                                                        <b>{{ $stdAnn->std_konsentrasi ?? '-' }}</b></span>
                                                                </td>
                                                                @if ($key == 6)
                                                                    <td class="p-2 align-top bg-indigo-50/40">
                                                                        <select
                                                                            name="plant_a[bak_{{ $key }}][ann_konsentrasi_2]"
                                                                            class="check-oos w-full border-indigo-300 rounded text-sm p-2 text-center font-bold text-indigo-700 bg-white focus:ring-2 focus:ring-indigo-500 cursor-pointer"
                                                                            data-std="{{ $stdAnn->std_konsentrasi }}">
                                                                            <option value="">- Pilih -</option>
                                                                            @foreach (generateDropdownOptions($stdAnn->std_konsentrasi, 'ann_kons') as $opt)
                                                                                <option value="{{ $opt }}">
                                                                                    {{ $opt }} %</option>
                                                                            @endforeach
                                                                        </select>
                                                                        <span
                                                                            class="block text-[11px] text-indigo-500 text-center mt-1">Std:
                                                                            <b>{{ $stdAnn->std_konsentrasi ?? '-' }}</b></span>
                                                                    </td>
                                                                @endif
                                                            </tr>

                                                            {{-- pH Level --}}
                                                            <tr>
                                                                <td
                                                                    class="p-3 text-xs font-bold text-slate-800 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                                    pH Level</td>
                                                                <td class="p-2 align-top">
                                                                    <select
                                                                        name="plant_a[bak_{{ $key }}][draw_ph]"
                                                                        class="check-oos w-full border-slate-300 rounded text-sm p-2 text-center font-bold text-emerald-800 focus:ring-2 focus:ring-emerald-500 cursor-pointer"
                                                                        data-std="{{ $stdDraw->std_ph }}">
                                                                        <option value="">- Pilih -</option>
                                                                        @foreach (generateDropdownOptions($stdDraw->std_ph, 'ph') as $opt)
                                                                            <option value="{{ $opt }}">
                                                                                {{ $opt }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                    <span
                                                                        class="block text-[11px] text-slate-500 text-center mt-1">Std:
                                                                        <b>{{ $stdDraw->std_ph ?? '-' }}</b></span>
                                                                </td>
                                                                <td
                                                                    class="p-2 align-top {{ $key == 6 ? 'border-r border-slate-300' : '' }}">
                                                                    <select
                                                                        name="plant_a[bak_{{ $key }}][ann_ph]"
                                                                        class="check-oos w-full border-slate-300 rounded text-sm p-2 text-center font-bold text-emerald-800 focus:ring-2 focus:ring-emerald-500 cursor-pointer"
                                                                        data-std="{{ $stdAnn->std_ph }}">
                                                                        <option value="">- Pilih -</option>
                                                                        @foreach (generateDropdownOptions($stdAnn->std_ph, 'ph') as $opt)
                                                                            <option value="{{ $opt }}">
                                                                                {{ $opt }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                    <span
                                                                        class="block text-[11px] text-slate-500 text-center mt-1">Std:
                                                                        <b>{{ $stdAnn->std_ph ?? '-' }}</b></span>
                                                                </td>
                                                                @if ($key == 6)
                                                                    <td class="p-2 align-top bg-indigo-50/40">
                                                                        <select
                                                                            name="plant_a[bak_{{ $key }}][ann_ph_2]"
                                                                            class="check-oos w-full border-indigo-300 rounded text-sm p-2 text-center font-bold text-indigo-800 bg-white shadow-sm focus:ring-2 focus:ring-indigo-500 cursor-pointer"
                                                                            data-std="{{ $stdAnn->std_ph }}">
                                                                            <option value="">- Pilih -</option>
                                                                            @foreach (generateDropdownOptions($stdAnn->std_ph, 'ph') as $opt)
                                                                                <option value="{{ $opt }}">
                                                                                    {{ $opt }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                        <span
                                                                            class="block text-[11px] text-indigo-500 text-center mt-1">Std:
                                                                            <b>{{ $stdAnn->std_ph ?? '-' }}</b></span>
                                                                    </td>
                                                                @endif
                                                            </tr>

                                                            {{-- Temperatur --}}
                                                            <tr>
                                                                <td
                                                                    class="p-3 text-xs font-bold text-slate-800 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                                    Temperatur</td>
                                                                <td class="p-2 align-top">
                                                                    <div class="flex items-center">
                                                                        <input type="number"
                                                                            name="plant_a[bak_{{ $key }}][draw_temp]"
                                                                            class="check-oos w-full border-slate-300 rounded-l text-sm p-2 text-center font-bold focus:ring-2 focus:ring-blue-500"
                                                                            data-std="{{ $stdDraw->std_temp }}">
                                                                        <span
                                                                            class="bg-slate-200 border border-l-0 border-slate-300 px-2.5 py-2 text-xs font-bold text-slate-600 rounded-r">°C</span>
                                                                    </div>
                                                                    <span
                                                                        class="block text-[11px] text-slate-500 text-center mt-1">Std:
                                                                        <b>{{ $stdDraw->std_temp ?? '-' }}</b></span>
                                                                </td>
                                                                <td
                                                                    class="p-2 align-top {{ $key == 6 ? 'border-r border-slate-300' : '' }}">
                                                                    <div class="flex items-center">
                                                                        <input type="number"
                                                                            name="plant_a[bak_{{ $key }}][ann_temp]"
                                                                            class="check-oos w-full border-slate-300 rounded-l text-sm p-2 text-center font-bold focus:ring-2 focus:ring-emerald-500"
                                                                            data-std="{{ $stdAnn->std_temp }}">
                                                                        <span
                                                                            class="bg-slate-200 border border-l-0 border-slate-300 px-2.5 py-2 text-xs font-bold text-slate-600 rounded-r">°C</span>
                                                                    </div>
                                                                    <span
                                                                        class="block text-[11px] text-slate-500 text-center mt-1">Std:
                                                                        <b>{{ $stdAnn->std_temp ?? '-' }}</b></span>
                                                                </td>
                                                                @if ($key == 6)
                                                                    <td class="p-2 align-top bg-indigo-50/40">
                                                                        <div class="flex items-center">
                                                                            <input type="number"
                                                                                name="plant_a[bak_{{ $key }}][ann_temp_2]"
                                                                                class="check-oos w-full border-indigo-300 rounded-l text-sm p-2 text-center font-bold bg-white focus:ring-2 focus:ring-indigo-500"
                                                                                data-std="{{ $stdAnn->std_temp }}">
                                                                            <span
                                                                                class="bg-indigo-100 border border-l-0 border-indigo-300 px-2.5 py-2 text-xs font-bold text-indigo-600 rounded-r">°C</span>
                                                                        </div>
                                                                        <span
                                                                            class="block text-[11px] text-indigo-500 text-center mt-1">Std:
                                                                            <b>{{ $stdAnn->std_temp ?? '-' }}</b></span>
                                                                    </td>
                                                                @endif
                                                            </tr>

                                                            {{-- Hourmeter --}}
                                                            <tr>
                                                                <td
                                                                    class="p-3 text-xs font-bold text-slate-800 sticky left-0 bg-white z-10 align-top pt-4">
                                                                    Hourmeter Mesin</td>
                                                                <td class="p-2 align-top bg-slate-50/50"
                                                                    colspan="{{ $key == 6 ? 3 : 2 }}">
                                                                    <div class="flex items-center w-full sm:w-2/5">
                                                                        <input type="number" step="any"
                                                                            name="plant_a[bak_{{ $key }}][hourmeter]"
                                                                            class="w-full border-slate-300 rounded-l text-sm p-2 text-right font-bold focus:ring-2 focus:ring-indigo-500"
                                                                            placeholder="0">
                                                                        <span
                                                                            class="bg-slate-200 border border-l-0 border-slate-300 px-2.5 py-2 text-xs font-bold text-slate-600 rounded-r whitespace-nowrap">Jam</span>
                                                                    </div>
                                                                    <span
                                                                        class="block text-[10px] text-slate-400 mt-1 italic">*Wajib
                                                                        diisi dengan angka hourmeter aktual mesin,
                                                                        gunakan titik (.) untuk 2 angka dibelakang
                                                                        koma.</span>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            {{-- Navigasi Tombol Prev/Next BAK --}}
                                            <div class="mt-3 flex justify-between items-center">
                                                <button type="button" @click="activePlantATab = {{ $key - 1 }}"
                                                    x-show="{{ $key }} > 1"
                                                    class="text-xs bg-white border border-slate-300 text-slate-700 px-4 py-2.5 rounded-lg font-bold shadow-sm hover:bg-slate-50 transition flex items-center gap-1">
                                                    ← Sebelumnya
                                                </button>
                                                <div x-show="{{ $key }} == 1"></div>
                                                <button type="button" @click="activePlantATab = {{ $key + 1 }}"
                                                    x-show="{{ $key }} < 6"
                                                    class="text-xs bg-slate-800 text-white px-5 py-2.5 rounded-lg font-bold shadow flex items-center gap-1 hover:bg-slate-700 transition">
                                                    Lanjut BAK {{ $key + 1 }} →
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- ========================================================== --}}
                            {{-- UI AUTOWIRE                                                  --}}
                            {{-- ========================================================== --}}
                            <div x-show="compoundForm.plant === 'Autowire'" style="display: none;"
                                class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-4">

                                <div
                                    class="p-3 bg-blue-50 border-b border-slate-200 flex justify-between items-center">
                                    <h4 class="font-bold text-sm text-slate-800">Mesin Drawing Multi 3 HONTA</h4>
                                    <span class="text-[10px] text-blue-600 font-bold italic sm:hidden">← geser
                                        tabel</span>
                                </div>

                                <div class="px-4 py-3 border-b border-slate-100">
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                                        <label
                                            class="font-extrabold text-[10px] text-slate-700 uppercase tracking-wider shrink-0">Tanggal
                                            Cek <span class="text-red-500">*</span>:</label>
                                        <div>
                                            <input type="date" name="autowire_tanggal" id="autowire_tanggal"
                                                value="{{ $tanggal ?? date('Y-m-d') }}" max="{{ date('Y-m-d') }}"
                                                required
                                                class="w-full sm:w-auto rounded border-slate-300 text-sm py-2 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm bg-white cursor-pointer">
                                            <small class="block text-[9px] text-slate-400 mt-1 italic">* Maksimal
                                                tanggal hari ini</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-3 sm:p-4">
                                    @php
                                        $stdMesinAuto = $stdAutowire['cek_1'] ?? collect();
                                        $stdDrawAuto = $stdMesinAuto->where('proses', 'drawing')->first();
                                        $stdAnnAuto = $stdMesinAuto->where('proses', 'annealing')->first();
                                    @endphp
                                    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
                                        <div class="overflow-x-auto -webkit-overflow-scrolling-touch">
                                            <table class="w-full text-sm text-left border-collapse">
                                                <thead>
                                                    <tr class="bg-slate-50">
                                                        <th
                                                            class="p-3 text-[10px] font-extrabold text-slate-500 uppercase border-b sticky left-0 bg-slate-50 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] min-w-[100px]">
                                                            Parameter</th>
                                                        <th
                                                            class="p-3 text-[10px] font-extrabold text-blue-600 uppercase border-b text-center min-w-[140px]">
                                                            Drawing Compound</th>
                                                        <th
                                                            class="p-3 text-[10px] font-extrabold text-emerald-600 uppercase border-b text-center min-w-[140px]">
                                                            Annealing Compound</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">

                                                    {{-- Type Item --}}
                                                    <tr>
                                                        <td
                                                            class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] align-top pt-4">
                                                            Type Item</td>
                                                        <td class="p-2 align-top">
                                                            <select name="autowire[draw_type]"
                                                                class="w-full border-slate-200 rounded text-xs p-1.5 text-center focus:ring-blue-500 bg-slate-50/50 cursor-pointer">
                                                                <option value="">-- Pilih --</option>
                                                                @if (!empty($stdDrawAuto->std_tipe))
                                                                    <option value="{{ $stdDrawAuto->std_tipe }}">
                                                                        {{ $stdDrawAuto->std_tipe }}</option>
                                                                @endif
                                                            </select>
                                                            <span
                                                                class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                <span
                                                                    class="font-bold text-slate-600">{{ $stdDrawAuto->std_tipe ?? '-' }}</span></span>
                                                        </td>
                                                        <td class="p-2 align-top">
                                                            <select name="autowire[ann_type]"
                                                                class="w-full border-slate-200 rounded text-xs p-1.5 text-center focus:ring-emerald-500 bg-slate-50/50 cursor-pointer">
                                                                <option value="">-- Pilih --</option>
                                                                @if (!empty($stdAnnAuto->std_tipe))
                                                                    <option value="{{ $stdAnnAuto->std_tipe }}">
                                                                        {{ $stdAnnAuto->std_tipe }}</option>
                                                                @endif
                                                            </select>
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
                                                        <td class="p-2 align-top text-center">
                                                            <select name="autowire[draw_supplier]"
                                                                class="w-full border-slate-200 rounded text-xs p-1.5 text-center cursor-pointer focus:ring-blue-500">
                                                                <option value="">-- Pilih --</option>
                                                                @if (!empty($stdDrawAuto->std_supplier))
                                                                    <option value="{{ $stdDrawAuto->std_supplier }}">
                                                                        {{ $stdDrawAuto->std_supplier }}</option>
                                                                @endif
                                                            </select>
                                                            <span
                                                                class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                <span
                                                                    class="font-bold text-slate-600">{{ $stdDrawAuto->std_supplier ?? '-' }}</span></span>
                                                        </td>
                                                        <td class="p-2 align-top text-center">
                                                            <select name="autowire[ann_supplier]"
                                                                class="w-full border-slate-200 rounded text-xs p-1.5 text-center cursor-pointer focus:ring-emerald-500">
                                                                <option value="">-- Pilih --</option>
                                                                @if (!empty($stdAnnAuto->std_supplier))
                                                                    <option value="{{ $stdAnnAuto->std_supplier }}">
                                                                        {{ $stdAnnAuto->std_supplier }}</option>
                                                                @endif
                                                            </select>
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
                                                            <select name="autowire[draw_warna]"
                                                                class="check-oos w-full border-slate-200 rounded text-xs p-1.5 text-center focus:ring-blue-500 font-bold text-blue-600 cursor-pointer"
                                                                data-std="{{ $stdDrawAuto->std_warna ?? '' }}">
                                                                <option value="">- Pilih Warna -</option>
                                                                <option value="Hijau Putih">Hijau Putih</option>
                                                                <option value="Putih">Putih</option>
                                                                <option value="Cokelat Kekuningan">Cokelat Kekuningan
                                                                </option>
                                                            </select>
                                                            <span
                                                                class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                <span
                                                                    class="font-bold text-slate-600">{{ $stdDrawAuto->std_warna ?? '-' }}</span></span>
                                                        </td>
                                                        <td class="p-2 align-top">
                                                            <select name="autowire[ann_warna]"
                                                                class="check-oos w-full border-slate-200 rounded text-xs p-1.5 text-center focus:ring-emerald-500 font-bold text-emerald-600 cursor-pointer"
                                                                data-std="{{ $stdAnnAuto->std_warna ?? '' }}">
                                                                <option value="">- Pilih Warna -</option>
                                                                <option value="Hijau Putih">Hijau Putih</option>
                                                                <option value="Putih">Putih</option>
                                                                <option value="Cokelat Kekuningan">Cokelat Kekuningan
                                                                </option>
                                                            </select>
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
                                                            <select name="autowire[draw_konsentrasi]"
                                                                class="check-oos w-full border-slate-200 rounded text-xs p-1.5 text-center focus:ring-blue-500 font-bold text-blue-600 cursor-pointer"
                                                                data-std="{{ $stdDrawAuto->std_konsentrasi }}">
                                                                <option value="">- Pilih -</option>
                                                                @foreach (generateDropdownOptions($stdDrawAuto->std_konsentrasi, 'draw_kons') as $opt)
                                                                    <option value="{{ $opt }}">
                                                                        {{ $opt }} %</option>
                                                                @endforeach
                                                            </select>
                                                            <span
                                                                class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                <span
                                                                    class="font-bold text-slate-600">{{ $stdDrawAuto->std_konsentrasi ?? '-' }}</span></span>
                                                        </td>
                                                        <td class="p-2 align-top">
                                                            <select name="autowire[ann_konsentrasi]"
                                                                class="check-oos w-full border-slate-200 rounded text-xs p-1.5 text-center focus:ring-emerald-500 font-bold text-emerald-600 cursor-pointer"
                                                                data-std="{{ $stdAnnAuto->std_konsentrasi }}">
                                                                <option value="">- Pilih -</option>
                                                                @foreach (generateDropdownOptions($stdAnnAuto->std_konsentrasi, 'ann_kons') as $opt)
                                                                    <option value="{{ $opt }}">
                                                                        {{ $opt }} %</option>
                                                                @endforeach
                                                            </select>
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
                                                        <td class="p-2 align-top text-center">
                                                            <select name="autowire[draw_ph]"
                                                                class="check-oos w-full border-slate-200 rounded text-xs p-1.5 text-center focus:ring-emerald-500 font-bold text-blue-700 cursor-pointer"
                                                                data-std="{{ $stdDrawAuto->std_ph }}">
                                                                <option value="">- Pilih -</option>
                                                                @foreach (generateDropdownOptions($stdDrawAuto->std_ph, 'ph') as $opt)
                                                                    <option value="{{ $opt }}">
                                                                        {{ $opt }}</option>
                                                                @endforeach
                                                            </select>
                                                            <span
                                                                class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                <span
                                                                    class="font-bold text-slate-600">{{ $stdDrawAuto->std_ph ?? '-' }}</span></span>
                                                        </td>
                                                        <td class="p-2 align-top text-center">
                                                            <select name="autowire[ann_ph]"
                                                                class="check-oos w-full border-slate-200 rounded text-xs p-1.5 text-center focus:ring-emerald-500 font-bold text-emerald-700 cursor-pointer"
                                                                data-std="{{ $stdAnnAuto->std_ph }}">
                                                                <option value="">- Pilih -</option>
                                                                @foreach (generateDropdownOptions($stdAnnAuto->std_ph, 'ph') as $opt)
                                                                    <option value="{{ $opt }}">
                                                                        {{ $opt }}</option>
                                                                @endforeach
                                                            </select>
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
                                                        <td class="p-2 align-top text-center">
                                                            <div class="flex items-center">
                                                                <input type="number" name="autowire[draw_temp]"
                                                                    class="check-oos w-full border-slate-200 rounded-l text-xs p-1.5 text-center focus:ring-blue-500"
                                                                    data-std="{{ $stdDrawAuto->std_temp }}">
                                                                <span
                                                                    class="bg-slate-50 border border-l-0 border-slate-200 px-2 py-1.5 text-[10px] font-bold text-slate-400 rounded-r">°C</span>
                                                            </div>
                                                            <span
                                                                class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                <span
                                                                    class="font-bold text-slate-600">{{ $stdDrawAuto->std_temp ?? '-' }}</span></span>
                                                        </td>
                                                        <td class="p-2 align-top text-center">
                                                            <div class="flex items-center">
                                                                <input type="number" name="autowire[ann_temp]"
                                                                    class="check-oos w-full border-slate-200 rounded-l text-xs p-1.5 text-center focus:ring-emerald-500"
                                                                    data-std="{{ $stdAnnAuto->std_temp }}">
                                                                <span
                                                                    class="bg-slate-50 border border-l-0 border-slate-200 px-2 py-1.5 text-[10px] font-bold text-slate-400 rounded-r">°C</span>
                                                            </div>
                                                            <span
                                                                class="block text-[10px] text-slate-400 text-center mt-1 leading-none">Std:
                                                                <span
                                                                    class="font-bold text-slate-600">{{ $stdAnnAuto->std_temp ?? '-' }}</span></span>
                                                        </td>
                                                    </tr>

                                                    {{-- Hourmeter --}}
                                                    <tr>
                                                        <td
                                                            class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 align-top pt-4">
                                                            Hourmeter Mesin</td>
                                                        <td class="p-2 align-top bg-slate-50/50" colspan="2">
                                                            <div class="flex items-center w-full sm:w-2/5">
                                                                <input type="number" step="any"
                                                                    name="autowire[hourmeter]"
                                                                    class="w-full border-slate-200 rounded-l text-xs p-1.5 text-right font-bold focus:ring-indigo-500"
                                                                    placeholder="0">
                                                                <span
                                                                    class="bg-slate-100 border border-l-0 border-slate-200 px-2 py-1.5 text-[10px] font-bold text-slate-500 rounded-r">Jam</span>
                                                            </div>
                                                            <span
                                                                class="block text-[9px] text-slate-400 mt-1 italic">*Wajib
                                                                diisi dengan angka hourmeter aktual mesin, gunakan titik
                                                                (.) untuk 2 angka di belakang koma.</span>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- KETERANGAN, CATATAN & TANDA TANGAN --}}
                            <div x-show="compoundForm.plant" style="display: none;"
                                class="mt-4 space-y-4 md:space-y-0 md:grid md:grid-cols-3 md:gap-4 text-left items-start">

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1">Keterangan /
                                        Notes</label>
                                    <textarea name="keterangan" rows="4"
                                        class="w-full rounded border-slate-300 shadow-sm focus:border-blue-500 text-sm py-2"></textarea>
                                </div>

                                <div
                                    class="text-[11px] text-slate-600 bg-slate-100 p-3 rounded border border-slate-200">
                                    <span class="font-bold text-slate-800">Catatan Standar:</span>
                                    <ul class="list-disc pl-4 mt-1 space-y-1">
                                        <li>Pengukuran dilakukan setiap <strong>1 Minggu 2 Kali (Senin & Rabu)</strong>.
                                        </li>
                                        <li>Bila terjadi hal meragukan (compound berbau, warna berubah), segera info ke
                                            Engineering.</li>
                                        <li>Bila konsentrasi berkurang tambah compound, bila tinggi tambah air.</li>
                                    </ul>
                                </div>

                                <div>
                                    <div class="w-full border-2 border-slate-800 bg-white shadow-sm">
                                        {{-- Bagian Operator --}}
                                        <div class="flex border-b-2 border-slate-800">
                                            <div
                                                class="w-2/5 p-2 font-bold border-r-2 border-slate-800 bg-rose-50 text-[10px] uppercase flex flex-col justify-center text-left">
                                                <span class="text-rose-700">Diperiksa Oleh (Wajib NIK):</span>
                                                <input type="text" x-model="operatorNik"
                                                    @keyup.debounce.500ms="searchOperator()"
                                                    placeholder="Ketik NIK..."
                                                    class="mt-1 text-[10px] p-1.5 border-rose-300 rounded focus:ring-rose-500 font-bold uppercase w-full">
                                            </div>
                                            <div
                                                class="w-3/5 p-3 text-sm font-black text-center uppercase truncate text-blue-700 bg-blue-50/50 flex items-center justify-center min-h-[60px]">
                                                <span x-show="isSearching"
                                                    class="text-slate-400 animate-pulse text-[10px]">Mencari...</span>
                                                <span x-show="!isSearching" x-text="operatorName"
                                                    :class="operatorName === 'DATA TIDAK DITEMUKAN' ?
                                                        'text-red-500 font-bold text-[10px]' : ''"></span>
                                                <input type="hidden" name="nama_pemeriksa" :value="operatorName">
                                            </div>
                                        </div>
                                        {{-- Bagian Foreman --}}
                                        <div class="flex">
                                            <div
                                                class="w-2/5 p-2 border-r-2 border-slate-800 bg-slate-50 text-[10px] font-bold uppercase text-left">
                                                Diketahui Oleh:</div>
                                            <div
                                                class="w-3/5 p-2 text-xs font-bold text-center uppercase truncate text-slate-800 flex items-center justify-center">
                                                {{ auth()->user()->name }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        {{-- FOOTER SUBMIT — Sticky --}}
                        <div
                            class="bg-white px-4 py-3 flex justify-end gap-2 border-t border-slate-200 sticky bottom-0">
                            <button type="button" @click="showCompoundModal = false"
                                class="px-4 py-2.5 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 bg-white hover:bg-slate-50 transition-colors">
                                Batal
                            </button>

                            <button type="button" @click.prevent="submitCompoundForm($event, 'formCompound')"
                                :disabled="operatorName === 'DATA TIDAK DITEMUKAN' || operatorName === 'ERROR KONEKSI' ||
                                    operatorName === '........................'"
                                :class="(operatorName === 'DATA TIDAK DITEMUKAN' || operatorName === 'ERROR KONEKSI' ||
                                    operatorName === '........................') ?
                                'bg-slate-300 cursor-not-allowed text-slate-500' :
                                'bg-blue-600 hover:bg-blue-700 text-white'"
                                class="px-5 sm:px-6 py-2.5 rounded-lg text-sm font-bold shadow transition-all active:scale-95">
                                Simpan Data
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
    function parseStdJS(stdString) {
        if (!stdString) return null;
        let matches = stdString.match(/[0-9]+(?:\.[0-9]+)?/g);
        if (!matches) return null;
        let nums = matches.map(Number);
        if (nums.length >= 2) return {
            min: Math.min(...nums),
            max: Math.max(...nums)
        };
        return {
            min: nums[0],
            max: null
        };
    }

    function checkRealtimeOos(el) {
        let rawVal = el.value;
        if (rawVal === '') {
            el.classList.remove('border-rose-500', 'bg-rose-50', 'text-rose-700', 'ring-rose-500');
            return false;
        }
        let isOos = false;
        let stdStr = el.getAttribute('data-std');
        let name = el.getAttribute('name') || '';

        if (name.includes('warna')) {
            if (stdStr && rawVal !== stdStr) {
                isOos = true;
            }
        } else {
            let valStr = rawVal.replace(/[^0-9.-]/g, '');
            let val = parseFloat(valStr);
            let std = parseStdJS(stdStr);
            if (!isNaN(val) && std) {
                if ((std.min !== null && val < std.min) || (std.max !== null && val > std.max)) {
                    isOos = true;
                }
            }
        }

        if (isOos) {
            el.classList.add('border-rose-500', 'bg-rose-50', 'text-rose-700', 'ring-rose-500');
        } else {
            el.classList.remove('border-rose-500', 'bg-rose-50', 'text-rose-700', 'ring-rose-500');
        }
        return isOos;
    }

    document.body.addEventListener('input', function(event) {
        if (event.target && event.target.classList.contains('check-oos')) {
            checkRealtimeOos(event.target);
        }
    });

    function submitCompoundForm(event, formId) {
        event.preventDefault();
        let form = document.getElementById(formId);
        let inputs = form.querySelectorAll('.check-oos');
        let hasOos = false;

        inputs.forEach(el => {
            if (checkRealtimeOos(el)) hasOos = true;
        });

        if (hasOos) {
            Swal.fire({
                title: 'Konfirmasi Data Aktual',
                html: `<b>Ada beberapa data yang tidak sesuai standard, apakah ini data aktual?</b><br><br>Jika ya, sistem mewajibkan Anda untuk memberikan keterangan/alasan:`,
                icon: 'warning',
                input: 'textarea',
                inputPlaceholder: 'Contoh: Mesin trouble / Sedang proses kuras / Menunggu material...',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Ini Aktual (Simpan)',
                cancelButtonText: 'Batal (Periksa Ulang)',
                allowOutsideClick: false,
                inputValidator: (value) => {
                    if (!value || value.trim() === '') {
                        return 'Keterangan WAJIB diisi jika ada data aktual yang keluar standar!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    let ketInput = form.querySelector('input[name="keterangan"], textarea[name="keterangan"]');
                    if (ketInput) {
                        ketInput.value = ketInput.value +
                        ` \n[Catatan Aktualisasi OOS: ${result.value.trim()}]`;
                    }
                    form.submit();
                }
            });
        } else {
            form.submit();
        }
    }
</script>
