{{-- Letakkan modal di luar alur DOM utama menggunakan x-teleport --}}
<template x-teleport="body">
    {{-- PASTIKAN variabel trigger modal (misal: showEditModal) sudah didefinisikan di parent komponen/halaman Anda --}}
    <div x-show="showEditModal" style="display: none;" x-data="{
        activeTab: 1,
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
                    if (!res.ok) throw new Error('Not found');
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
                    this.operatorName = 'ERROR KONEKSI';
                });
        }
    }" class="fixed inset-0 z-[60] overflow-y-auto"
        role="dialog" aria-modal="true">

        {{-- Modal Backdrop --}}
        <div x-show="showEditModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity" @click="showEditModal = false">
        </div>

        {{-- Modal Container — full screen di mobile, centered di sm+ --}}
        <div class="flex min-h-full items-end sm:items-center justify-center p-0 sm:p-4 text-center">
            <div x-show="showEditModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-t-2xl sm:rounded-xl bg-white text-left shadow-2xl transition-all w-full sm:my-8 sm:max-w-6xl border-t-4 sm:border-t-8 border-indigo-600 flex flex-col max-h-[95dvh] sm:max-h-[90vh]">

                {{-- Alert Error --}}
                @if (session('error'))
                    <div
                        class="m-3 sm:m-4 mb-0 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg relative flex items-center gap-3 shadow-sm">
                        <span class="block sm:inline text-sm font-bold">{{ session('error') }}</span>
                    </div>
                @endif

                {{-- MODAL HEADER --}}
                <div
                    class="px-4 sm:px-6 py-3 sm:py-4 bg-white border-b border-slate-200 flex justify-between items-center shrink-0">
                    <div class="flex-1 min-w-0">
                        <h2 class="text-lg sm:text-xl font-extrabold text-slate-800 truncate">Edit Matriks Compound</h2>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span
                                class="bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">{{ $plant->name ?? 'Plant' }}</span>
                            <span class="text-xs text-slate-500 font-bold">•
                                {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</span>
                        </div>
                    </div>
                    <button type="button" @click="showEditModal = false"
                        class="ml-3 shrink-0 text-slate-400 hover:text-rose-500 bg-slate-100 hover:bg-rose-50 rounded-full p-2 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                {{-- MODAL BODY (Scrollable) --}}
                <div class="px-3 sm:px-6 py-4 sm:py-6 bg-slate-50 overflow-y-auto grow">
                    <form id="formEditCompound" action="{{ route('eng.compound.update', [$plant->id ?? 0, $tanggal]) }}"
                        method="POST">
                        @csrf
                        @method('PUT')

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

                        <input type="hidden" name="plant" value="{{ $plantName }}">
                        @if ($plantName === 'Plant A')
                            <input type="hidden" name="plant_a_tanggal" value="{{ $tanggal }}">
                        @endif

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

                            {{-- Tab Navigation BAK — Mobile scrollable pills --}}
                            <div
                                class="flex overflow-x-auto bg-white border border-slate-200 p-1.5 gap-1.5 rounded-xl mb-4 shadow-sm snap-x scrollbar-hide">
                                @foreach ($baksMap as $key => $bak)
                                    @php $hasData = isset($checks[$bak['id_mesin']]); @endphp
                                    <button type="button" @click="activeTab = {{ $key }}"
                                        :class="activeTab === {{ $key }} ? 'bg-indigo-600 text-white shadow-md' :
                                            'bg-slate-50 text-slate-600 hover:bg-slate-100'"
                                        class="snap-start flex-none px-3 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 min-h-[36px]">
                                        <span
                                            class="w-5 h-5 rounded-full flex items-center justify-center border text-[10px] shrink-0 {{ $hasData ? 'bg-emerald-500 border-emerald-400 text-white' : 'bg-slate-200 text-slate-500' }}">
                                            {!! $hasData ? '✓' : $key !!}
                                        </span>
                                        <span class="whitespace-nowrap hidden sm:inline">{{ $bak['nama'] }}</span>
                                        <span class="whitespace-nowrap sm:hidden">BAK {{ $key }}</span>
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
                                            class="bg-slate-50 px-3 sm:px-4 py-2 border-b border-slate-200 flex justify-between items-center">
                                            <span class="text-xs font-bold text-slate-700 uppercase">Pengecekan
                                                {{ $bak['nama'] }}</span>
                                            <span
                                                class="text-[10px] text-slate-500 italic font-medium hidden sm:block">Geser
                                                tabel ke
                                                samping →</span>
                                        </div>

                                        <div class="overflow-x-auto -webkit-overflow-scrolling-touch">
                                            <table class="w-full text-sm text-left border-collapse">
                                                <thead>
                                                    <tr class="bg-slate-50/50">
                                                        <th
                                                            class="p-3 text-[11px] font-extrabold text-slate-500 uppercase border-b sticky left-0 bg-slate-50 z-10 min-w-[110px]">
                                                            Parameter</th>
                                                        <th
                                                            class="p-3 text-[11px] font-extrabold text-blue-600 uppercase border-b text-center min-w-[140px]">
                                                            Drawing</th>
                                                        <th class="p-3 text-[11px] font-extrabold text-emerald-600 uppercase border-b text-center {{ $key == 6 ? 'min-w-[280px]' : 'min-w-[140px]' }}"
                                                            colspan="{{ $key == 6 ? 2 : 1 }}">
                                                            Annealing {{ $key == 6 ? '(Twin RBD CU)' : '' }}
                                                        </th>
                                                    </tr>
                                                    @if ($key == 6)
                                                        <tr
                                                            class="bg-slate-100 text-[10px] uppercase font-black text-slate-500">
                                                            <th class="border-b"></th>
                                                            <th class="border-b"></th>
                                                            <th
                                                                class="p-2 border-b text-center border-r border-slate-300 bg-emerald-50/50 text-emerald-700 min-w-[140px]">
                                                                Bak A</th>
                                                            <th
                                                                class="p-2 border-b text-center bg-indigo-50/50 text-indigo-700 min-w-[140px]">
                                                                Bak B</th>
                                                        </tr>
                                                    @endif
                                                </thead>
                                                @php
                                                    $machineId = $machineMap['bak_' . $key] ?? 0;
                                                    $dataExisting = $checks[$machineId] ?? null;
                                                @endphp
                                                <tbody class="divide-y divide-slate-200">
                                                    {{-- BARIS TYPE ITEM --}}
                                                    <tr>
                                                        <td
                                                            class="p-3 text-xs font-extrabold text-slate-800 sticky left-0 bg-white z-10 align-top pt-4">
                                                            Type Item</td>
                                                        <td class="p-2 align-top">
                                                            <select name="plant_a[bak_{{ $key }}][draw_type]"
                                                                class="w-full border-slate-300 rounded text-sm p-2 text-center bg-slate-50 font-medium focus:ring-2 focus:ring-blue-500 cursor-pointer">
                                                                <option value="">-- Pilih --</option>
                                                                @if (!empty($stdDraw->std_tipe))
                                                                    <option value="{{ $stdDraw->std_tipe }}"
                                                                        {{ ($dataExisting->draw_type ?? '') == $stdDraw->std_tipe ? 'selected' : '' }}>
                                                                        {{ $stdDraw->std_tipe }}</option>
                                                                @endif
                                                                @if (!empty($dataExisting->draw_type) && $dataExisting->draw_type != ($stdDraw->std_tipe ?? ''))
                                                                    <option value="{{ $dataExisting->draw_type }}"
                                                                        selected>{{ $dataExisting->draw_type }}
                                                                    </option>
                                                                @endif
                                                            </select>
                                                            <span
                                                                class="block text-[11px] text-slate-500 text-center mt-1">Std:
                                                                <b>{{ $stdDraw->std_tipe ?? '-' }}</b></span>
                                                        </td>
                                                        <td
                                                            class="p-2 align-top {{ $key == 6 ? 'border-r border-slate-300' : '' }}">
                                                            <select name="plant_a[bak_{{ $key }}][ann_type]"
                                                                class="w-full border-slate-300 rounded text-sm p-2 text-center bg-slate-50 font-medium focus:ring-2 focus:ring-emerald-500 cursor-pointer">
                                                                <option value="">-- Pilih --</option>
                                                                @if (!empty($stdAnn->std_tipe))
                                                                    <option value="{{ $stdAnn->std_tipe }}"
                                                                        {{ ($dataExisting->ann_type ?? '') == $stdAnn->std_tipe ? 'selected' : '' }}>
                                                                        {{ $stdAnn->std_tipe }}</option>
                                                                @endif
                                                                @if (!empty($dataExisting->ann_type) && $dataExisting->ann_type != ($stdAnn->std_tipe ?? ''))
                                                                    <option value="{{ $dataExisting->ann_type }}"
                                                                        selected>{{ $dataExisting->ann_type }}</option>
                                                                @endif
                                                            </select>
                                                            <span
                                                                class="block text-[11px] text-slate-500 text-center mt-1">Std:
                                                                <b>{{ $stdAnn->std_tipe ?? '-' }}</b></span>
                                                        </td>
                                                        @if ($key == 6)
                                                            <td class="p-2 align-top bg-indigo-50/30">
                                                                <select
                                                                    name="plant_a[bak_{{ $key }}][ann_type_2]"
                                                                    class="w-full border-indigo-300 rounded text-sm p-2 text-center bg-white font-medium focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                                                                    <option value="">-- Pilih --</option>
                                                                    @if (!empty($stdAnn->std_tipe))
                                                                        <option value="{{ $stdAnn->std_tipe }}"
                                                                            {{ ($dataExisting->ann_type_2 ?? '') == $stdAnn->std_tipe ? 'selected' : '' }}>
                                                                            {{ $stdAnn->std_tipe }}</option>
                                                                    @endif
                                                                    @if (!empty($dataExisting->ann_type_2) && $dataExisting->ann_type_2 != ($stdAnn->std_tipe ?? ''))
                                                                        <option value="{{ $dataExisting->ann_type_2 }}"
                                                                            selected>{{ $dataExisting->ann_type_2 }}
                                                                        </option>
                                                                    @endif
                                                                </select>
                                                                <span
                                                                    class="block text-[11px] text-indigo-500 text-center mt-1">Std:
                                                                    <b>{{ $stdAnn->std_tipe ?? '-' }}</b></span>
                                                            </td>
                                                        @endif
                                                    </tr>

                                                    {{-- BARIS SUPPLIER --}}
                                                    <tr>
                                                        <td
                                                            class="p-3 text-xs font-extrabold text-slate-800 sticky left-0 bg-white z-10 align-top pt-4">
                                                            Supplier</td>
                                                        <td class="p-2 align-top">
                                                            <select
                                                                name="plant_a[bak_{{ $key }}][draw_supplier]"
                                                                class="w-full border-slate-300 rounded text-sm p-2 text-center focus:ring-2 focus:ring-blue-500 cursor-pointer">
                                                                <option value="">-- Pilih --</option>
                                                                @if (!empty($stdDraw->std_supplier))
                                                                    <option value="{{ $stdDraw->std_supplier }}"
                                                                        {{ ($dataExisting->draw_supplier ?? '') == $stdDraw->std_supplier ? 'selected' : '' }}>
                                                                        {{ $stdDraw->std_supplier }}</option>
                                                                @endif
                                                                @if (!empty($dataExisting->draw_supplier) && $dataExisting->draw_supplier != ($stdDraw->std_supplier ?? ''))
                                                                    <option value="{{ $dataExisting->draw_supplier }}"
                                                                        selected>{{ $dataExisting->draw_supplier }}
                                                                    </option>
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
                                                                    <option value="{{ $stdAnn->std_supplier }}"
                                                                        {{ ($dataExisting->ann_supplier ?? '') == $stdAnn->std_supplier ? 'selected' : '' }}>
                                                                        {{ $stdAnn->std_supplier }}</option>
                                                                @endif
                                                                @if (!empty($dataExisting->ann_supplier) && $dataExisting->ann_supplier != ($stdAnn->std_supplier ?? ''))
                                                                    <option value="{{ $dataExisting->ann_supplier }}"
                                                                        selected>{{ $dataExisting->ann_supplier }}
                                                                    </option>
                                                                @endif
                                                            </select>
                                                            <span
                                                                class="block text-[11px] text-slate-500 text-center mt-1">Std:
                                                                <b>{{ $stdAnn->std_supplier ?? '-' }}</b></span>
                                                        </td>
                                                        @if ($key == 6)
                                                            <td class="p-2 align-top bg-indigo-50/30">
                                                                <select
                                                                    name="plant_a[bak_{{ $key }}][ann_supplier_2]"
                                                                    class="w-full border-indigo-300 rounded text-sm p-2 text-center bg-white focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                                                                    <option value="">-- Pilih --</option>
                                                                    @if (!empty($stdAnn->std_supplier))
                                                                        <option value="{{ $stdAnn->std_supplier }}"
                                                                            {{ ($dataExisting->ann_supplier_2 ?? '') == $stdAnn->std_supplier ? 'selected' : '' }}>
                                                                            {{ $stdAnn->std_supplier }}</option>
                                                                    @endif
                                                                    @if (!empty($dataExisting->ann_supplier_2) && $dataExisting->ann_supplier_2 != ($stdAnn->std_supplier ?? ''))
                                                                        <option
                                                                            value="{{ $dataExisting->ann_supplier_2 }}"
                                                                            selected>
                                                                            {{ $dataExisting->ann_supplier_2 }}
                                                                        </option>
                                                                    @endif
                                                                </select>
                                                                <span
                                                                    class="block text-[11px] text-indigo-500 text-center mt-1">Std:
                                                                    <b>{{ $stdAnn->std_supplier ?? '-' }}</b></span>
                                                            </td>
                                                        @endif
                                                    </tr>

                                                    {{-- BARIS WARNA --}}
                                                    <tr>
                                                        <td
                                                            class="p-3 text-xs font-extrabold text-slate-800 sticky left-0 bg-white z-10 align-top pt-4">
                                                            Warna</td>
                                                        <td class="p-2 align-top">
                                                            @php $val = $dataExisting->draw_warna ?? ''; @endphp
                                                            <select
                                                                name="plant_a[bak_{{ $key }}][draw_warna]"
                                                                class="check-oos w-full border-slate-300 rounded text-sm p-2 text-center"
                                                                data-std="{{ $stdDraw->std_warna ?? '' }}">
                                                                <option value="">- Pilih Warna -</option>
                                                                <option value="Hijau Putih"
                                                                    {{ $val == 'Hijau Putih' ? 'selected' : '' }}>Hijau
                                                                    Putih</option>
                                                                <option value="Putih"
                                                                    {{ $val == 'Putih' ? 'selected' : '' }}>Putih
                                                                </option>
                                                                <option value="Cokelat Kekuningan"
                                                                    {{ $val == 'Cokelat Kekuningan' ? 'selected' : '' }}>
                                                                    Cokelat Kekuningan</option>
                                                                @if (!empty($val) && !in_array($val, ['Hijau Putih', 'Putih', 'Cokelat Kekuningan']))
                                                                    <option value="{{ $val }}" selected>
                                                                        {{ $val }}</option>
                                                                @endif
                                                            </select>
                                                            <span
                                                                class="block text-[11px] text-slate-500 text-center mt-1">Std:
                                                                <b>{{ $stdDraw->std_warna ?? '-' }}</b></span>
                                                        </td>
                                                        <td
                                                            class="p-2 align-top {{ $key == 6 ? 'border-r border-slate-300' : '' }}">
                                                            @php $val = $dataExisting->ann_warna ?? ''; @endphp
                                                            <select name="plant_a[bak_{{ $key }}][ann_warna]"
                                                                class="check-oos w-full border-slate-300 rounded text-sm p-2 text-center"
                                                                data-std="{{ $stdAnn->std_warna ?? '' }}">
                                                                <option value="">- Pilih Warna -</option>
                                                                <option value="Hijau Putih"
                                                                    {{ $val == 'Hijau Putih' ? 'selected' : '' }}>Hijau
                                                                    Putih</option>
                                                                <option value="Putih"
                                                                    {{ $val == 'Putih' ? 'selected' : '' }}>Putih
                                                                </option>
                                                                <option value="Cokelat Kekuningan"
                                                                    {{ $val == 'Cokelat Kekuningan' ? 'selected' : '' }}>
                                                                    Cokelat Kekuningan</option>
                                                                @if (!empty($val) && !in_array($val, ['Hijau Putih', 'Putih', 'Cokelat Kekuningan']))
                                                                    <option value="{{ $val }}" selected>
                                                                        {{ $val }}</option>
                                                                @endif
                                                            </select>
                                                            <span
                                                                class="block text-[11px] text-slate-500 text-center mt-1">Std:
                                                                <b>{{ $stdAnn->std_warna ?? '-' }}</b></span>
                                                        </td>
                                                        @if ($key == 6)
                                                            <td class="p-2 align-top bg-indigo-50/30">
                                                                @php $val = $dataExisting->ann_warna_2 ?? ''; @endphp
                                                                <select
                                                                    name="plant_a[bak_{{ $key }}][ann_warna_2]"
                                                                    class="check-oos w-full border-indigo-300 rounded text-sm p-2 text-center bg-white"
                                                                    data-std="{{ $stdAnn->std_warna ?? '' }}">
                                                                    <option value="">- Pilih Warna -</option>
                                                                    <option value="Hijau Putih"
                                                                        {{ $val == 'Hijau Putih' ? 'selected' : '' }}>
                                                                        Hijau Putih</option>
                                                                    <option value="Putih"
                                                                        {{ $val == 'Putih' ? 'selected' : '' }}>Putih
                                                                    </option>
                                                                    <option value="Cokelat Kekuningan"
                                                                        {{ $val == 'Cokelat Kekuningan' ? 'selected' : '' }}>
                                                                        Cokelat Kekuningan</option>
                                                                    @if (!empty($val) && !in_array($val, ['Hijau Putih', 'Putih', 'Cokelat Kekuningan']))
                                                                        <option value="{{ $val }}" selected>
                                                                            {{ $val }}</option>
                                                                    @endif
                                                                </select>
                                                                <span
                                                                    class="block text-[11px] text-indigo-500 text-center mt-1">Std:
                                                                    <b>{{ $stdAnn->std_warna ?? '-' }}</b></span>
                                                            </td>
                                                        @endif
                                                    </tr>

                                                    {{-- BARIS KONSENTRASI --}}
                                                    <tr>
                                                        <td
                                                            class="p-3 text-xs font-extrabold text-slate-800 sticky left-0 bg-white z-10 align-top pt-4">
                                                            Konsentrasi</td>
                                                        <td class="p-2 align-top">
                                                            @php
                                                                $val = isset($dataExisting->draw_konsentrasi)
                                                                    ? str_replace(
                                                                        '%',
                                                                        '',
                                                                        $dataExisting->draw_konsentrasi,
                                                                    )
                                                                    : '';
                                                                $opts = generateDropdownOptions(
                                                                    $stdDraw->std_konsentrasi,
                                                                    'draw_kons',
                                                                );
                                                            @endphp
                                                            <select
                                                                name="plant_a[bak_{{ $key }}][draw_konsentrasi]"
                                                                class="check-oos w-full border-slate-300 rounded text-sm p-2 text-center font-bold text-blue-700 cursor-pointer focus:ring-2 focus:ring-blue-500"
                                                                data-std="{{ $stdDraw->std_konsentrasi ?? '' }}">
                                                                <option value="">- Pilih -</option>
                                                                @foreach ($opts as $opt)
                                                                    <option value="{{ $opt }}"
                                                                        {{ (string) $val === (string) $opt ? 'selected' : '' }}>
                                                                        {{ $opt }} %</option>
                                                                @endforeach
                                                                @if ($val !== '' && !in_array((float) $val, $opts))
                                                                    <option value="{{ $val }}" selected>
                                                                        {{ $val }} %</option>
                                                                @endif
                                                            </select>
                                                            <span
                                                                class="block text-[11px] text-slate-500 text-center mt-1">Std:
                                                                <b>{{ $stdDraw->std_konsentrasi ?? '-' }}</b></span>
                                                        </td>
                                                        <td
                                                            class="p-2 align-top {{ $key == 6 ? 'border-r border-slate-300' : '' }}">
                                                            @php
                                                                $val = isset($dataExisting->ann_konsentrasi)
                                                                    ? str_replace(
                                                                        '%',
                                                                        '',
                                                                        $dataExisting->ann_konsentrasi,
                                                                    )
                                                                    : '';
                                                                $opts = generateDropdownOptions(
                                                                    $stdAnn->std_konsentrasi,
                                                                    'ann_kons',
                                                                );
                                                            @endphp
                                                            <select
                                                                name="plant_a[bak_{{ $key }}][ann_konsentrasi]"
                                                                class="check-oos w-full border-slate-300 rounded text-sm p-2 text-center font-bold text-emerald-700 cursor-pointer focus:ring-2 focus:ring-emerald-500"
                                                                data-std="{{ $stdAnn->std_konsentrasi ?? '' }}">
                                                                <option value="">- Pilih -</option>
                                                                @foreach ($opts as $opt)
                                                                    <option value="{{ $opt }}"
                                                                        {{ (string) $val === (string) $opt ? 'selected' : '' }}>
                                                                        {{ $opt }} %</option>
                                                                @endforeach
                                                                @if ($val !== '' && !in_array((float) $val, $opts))
                                                                    <option value="{{ $val }}" selected>
                                                                        {{ $val }} %</option>
                                                                @endif
                                                            </select>
                                                            <span
                                                                class="block text-[11px] text-slate-500 text-center mt-1">Std:
                                                                <b>{{ $stdAnn->std_konsentrasi ?? '-' }}</b></span>
                                                        </td>
                                                        @if ($key == 6)
                                                            <td class="p-2 align-top bg-indigo-50/30">
                                                                @php $val = isset($dataExisting->ann_konsentrasi_2) ? str_replace('%', '', $dataExisting->ann_konsentrasi_2) : ''; @endphp
                                                                <select
                                                                    name="plant_a[bak_{{ $key }}][ann_konsentrasi_2]"
                                                                    class="check-oos w-full border-indigo-300 rounded text-sm p-2 text-center font-bold text-indigo-700 bg-white cursor-pointer focus:ring-2 focus:ring-indigo-500"
                                                                    data-std="{{ $stdAnn->std_konsentrasi ?? '' }}">
                                                                    <option value="">- Pilih -</option>
                                                                    @foreach ($opts as $opt)
                                                                        <option value="{{ $opt }}"
                                                                            {{ (string) $val === (string) $opt ? 'selected' : '' }}>
                                                                            {{ $opt }} %</option>
                                                                    @endforeach
                                                                    @if ($val !== '' && !in_array((float) $val, $opts))
                                                                        <option value="{{ $val }}" selected>
                                                                            {{ $val }} %</option>
                                                                    @endif
                                                                </select>
                                                                <span
                                                                    class="block text-[11px] text-indigo-500 text-center mt-1">Std:
                                                                    <b>{{ $stdAnn->std_konsentrasi ?? '-' }}</b></span>
                                                            </td>
                                                        @endif
                                                    </tr>

                                                    {{-- PH LEVEL --}}
                                                    <tr>
                                                        <td
                                                            class="p-3 text-xs font-extrabold text-slate-800 sticky left-0 bg-white z-10 align-top pt-4">
                                                            pH Level</td>
                                                        <td class="p-2 align-top">
                                                            @php
                                                                $val = $dataExisting->draw_ph ?? '';
                                                                $opts = generateDropdownOptions($stdDraw->std_ph, 'ph');
                                                            @endphp
                                                            <select name="plant_a[bak_{{ $key }}][draw_ph]"
                                                                class="check-oos w-full border-slate-300 rounded text-sm p-2 text-center font-bold text-emerald-800 cursor-pointer focus:ring-2 focus:ring-emerald-500"
                                                                data-std="{{ $stdDraw->std_ph ?? '' }}">
                                                                <option value="">- Pilih -</option>
                                                                @foreach ($opts as $opt)
                                                                    <option value="{{ $opt }}"
                                                                        {{ (string) $val === (string) $opt ? 'selected' : '' }}>
                                                                        {{ $opt }}</option>
                                                                @endforeach
                                                                @if ($val !== '' && !in_array((float) $val, $opts))
                                                                    <option value="{{ $val }}" selected>
                                                                        {{ $val }}</option>
                                                                @endif
                                                            </select>
                                                            <span
                                                                class="block text-[11px] text-slate-500 text-center mt-1">Std:
                                                                <b>{{ $stdDraw->std_ph ?? '-' }}</b></span>
                                                        </td>
                                                        <td
                                                            class="p-2 align-top {{ $key == 6 ? 'border-r border-slate-300' : '' }}">
                                                            @php
                                                                $val = $dataExisting->ann_ph ?? '';
                                                                $opts = generateDropdownOptions($stdAnn->std_ph, 'ph');
                                                            @endphp
                                                            <select name="plant_a[bak_{{ $key }}][ann_ph]"
                                                                class="check-oos w-full border-slate-300 rounded text-sm p-2 text-center font-bold text-emerald-800 cursor-pointer focus:ring-2 focus:ring-emerald-500"
                                                                data-std="{{ $stdAnn->std_ph ?? '' }}">
                                                                <option value="">- Pilih -</option>
                                                                @foreach ($opts as $opt)
                                                                    <option value="{{ $opt }}"
                                                                        {{ (string) $val === (string) $opt ? 'selected' : '' }}>
                                                                        {{ $opt }}</option>
                                                                @endforeach
                                                                @if ($val !== '' && !in_array((float) $val, $opts))
                                                                    <option value="{{ $val }}" selected>
                                                                        {{ $val }}</option>
                                                                @endif
                                                            </select>
                                                            <span
                                                                class="block text-[11px] text-slate-500 text-center mt-1">Std:
                                                                <b>{{ $stdAnn->std_ph ?? '-' }}</b></span>
                                                        </td>
                                                        @if ($key == 6)
                                                            <td class="p-2 align-top bg-indigo-50/30">
                                                                @php $val = $dataExisting->ann_ph_2 ?? ''; @endphp
                                                                <select
                                                                    name="plant_a[bak_{{ $key }}][ann_ph_2]"
                                                                    class="check-oos w-full border-indigo-300 rounded text-sm p-2 text-center font-bold text-indigo-800 bg-white cursor-pointer focus:ring-2 focus:ring-indigo-500"
                                                                    data-std="{{ $stdAnn->std_ph ?? '' }}">
                                                                    <option value="">- Pilih -</option>
                                                                    @foreach ($opts as $opt)
                                                                        <option value="{{ $opt }}"
                                                                            {{ (string) $val === (string) $opt ? 'selected' : '' }}>
                                                                            {{ $opt }}</option>
                                                                    @endforeach
                                                                    @if ($val !== '' && !in_array((float) $val, $opts))
                                                                        <option value="{{ $val }}" selected>
                                                                            {{ $val }}</option>
                                                                    @endif
                                                                </select>
                                                                <span
                                                                    class="block text-[11px] text-indigo-500 text-center mt-1">Std:
                                                                    <b>{{ $stdAnn->std_ph ?? '-' }}</b></span>
                                                            </td>
                                                        @endif
                                                    </tr>

                                                    {{-- TEMPERATUR --}}
                                                    <tr>
                                                        <td
                                                            class="p-3 text-xs font-extrabold text-slate-800 sticky left-0 bg-white z-10 align-top pt-4">
                                                            Temperatur</td>
                                                        <td class="p-2 align-top">
                                                            <div class="flex items-center">
                                                                <input type="number"
                                                                    name="plant_a[bak_{{ $key }}][draw_temp]"
                                                                    value="{{ isset($dataExisting->draw_temp) ? str_replace(['°C', 'C'], '', $dataExisting->draw_temp) : '' }}"
                                                                    class="w-full border-slate-300 rounded-l text-sm p-2 text-center font-bold">
                                                                <span
                                                                    class="bg-slate-200 border border-l-0 border-slate-300 px-2 py-2 text-xs font-bold text-slate-600 rounded-r">°C</span>
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
                                                                    value="{{ isset($dataExisting->ann_temp) ? str_replace(['°C', 'C'], '', $dataExisting->ann_temp) : '' }}"
                                                                    class="w-full border-slate-300 rounded-l text-sm p-2 text-center font-bold">
                                                                <span
                                                                    class="bg-slate-200 border border-l-0 border-slate-300 px-2 py-2 text-xs font-bold text-slate-600 rounded-r">°C</span>
                                                            </div>
                                                            <span
                                                                class="block text-[11px] text-slate-500 text-center mt-1">Std:
                                                                <b>{{ $stdAnn->std_temp ?? '-' }}</b></span>
                                                        </td>
                                                        @if ($key == 6)
                                                            <td class="p-2 align-top bg-indigo-50/30">
                                                                <div class="flex items-center">
                                                                    <input type="number"
                                                                        name="plant_a[bak_{{ $key }}][ann_temp_2]"
                                                                        value="{{ isset($dataExisting->ann_temp_2) ? str_replace(['°C', 'C'], '', $dataExisting->ann_temp_2) : '' }}"
                                                                        class="w-full border-indigo-300 rounded-l text-sm p-2 text-center font-bold bg-white">
                                                                    <span
                                                                        class="bg-indigo-100 border border-l-0 border-indigo-300 px-2 py-2 text-xs font-bold text-indigo-600 rounded-r">°C</span>
                                                                </div>
                                                                <span
                                                                    class="block text-[11px] text-indigo-500 text-center mt-1">Std:
                                                                    <b>{{ $stdAnn->std_temp ?? '-' }}</b></span>
                                                            </td>
                                                        @endif
                                                    </tr>

                                                    {{-- BARIS HOURMETER --}}
                                                    <tr>
                                                        <td
                                                            class="p-3 text-xs font-extrabold text-slate-800 sticky left-0 bg-white z-10 align-top pt-4">
                                                            Hourmeter Mesin</td>
                                                        <td class="p-3 align-top bg-slate-50/50"
                                                            colspan="{{ $key == 6 ? 3 : 2 }}">
                                                            <div class="flex items-center w-full sm:w-1/3">
                                                                <input type="number" step="any"
                                                                    name="plant_a[bak_{{ $key }}][hourmeter]"
                                                                    value="{{ isset($dataExisting->hourmeter) ? (float) str_replace(',', '.', $dataExisting->hourmeter) : '' }}"
                                                                    class="w-full border-slate-300 rounded-l text-sm p-2 text-right font-bold focus:ring-2 focus:ring-indigo-500"
                                                                    placeholder="0">
                                                                <span
                                                                    class="bg-slate-200 border border-l-0 border-slate-300 px-3 py-2 text-xs font-bold text-slate-600 rounded-r">Jam</span>
                                                            </div>
                                                            <span
                                                                class="block text-[10px] text-slate-400 mt-1 italic">*Wajib
                                                                diisi dengan angka hourmeter aktual mesin, gunakan titik
                                                                (.)
                                                                untuk 2 angka dibelakang koma.</span>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <p class="text-[10px] text-slate-400 italic mb-4">*Jika Anda ingin menghapus data
                                        Bak ini, cukup kosongkan seluruh isian di tabel atas dan klik Simpan.</p>
                                </div>
                            @endforeach
                        @elseif ($plantName === 'Autowire')
                            {{-- Autowire section — sama dengan versi asli, sudah cukup responsif --}}
                            @php
                                $dataAuto = $checks->first();
                                $stdMesinAuto = $stdAutowire['cek_1'] ?? collect();
                                $stdDrawAuto = $stdMesinAuto->where('proses', 'drawing')->first();
                                $stdAnnAuto = $stdMesinAuto->where('proses', 'annealing')->first();
                            @endphp

                            <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden mb-4">
                                <div
                                    class="bg-slate-50 px-4 py-2 border-b border-slate-200 flex justify-between items-center">
                                    <span class="text-xs font-bold text-slate-700 uppercase">Pengecekan Mesin Multi
                                        Drawing 3 Honta</span>
                                </div>
                                <div class="p-4 bg-white border-b border-slate-100 flex items-center gap-3">
                                    <label class="font-bold text-xs text-slate-700 uppercase shrink-0">Tanggal
                                        Cek:</label>
                                    <input type="date" name="autowire_tanggal"
                                        value="{{ $dataAuto->tanggal_cek ?? $tanggal }}"
                                        class="rounded border-slate-300 text-sm py-1.5 focus:ring-blue-500 shadow-sm bg-slate-50"
                                        readonly>
                                </div>
                                <div class="overflow-x-auto -webkit-overflow-scrolling-touch">
                                    <table class="w-full text-sm text-left border-collapse">
                                        <thead>
                                            <tr class="bg-slate-50/50">
                                                <th
                                                    class="p-3 text-[10px] font-extrabold text-slate-500 uppercase border-b sticky left-0 bg-slate-50 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] min-w-[100px]">
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
                                                <td class="p-2 align-top text-center">
                                                    <select name="autowire[draw_type]"
                                                        class="w-full border-slate-200 rounded text-xs p-1.5 text-center bg-slate-50/50 cursor-pointer focus:ring-blue-500">
                                                        <option value="">-- Pilih --</option>
                                                        @if (!empty($stdDrawAuto->std_tipe))
                                                            <option value="{{ $stdDrawAuto->std_tipe }}"
                                                                {{ ($dataAuto->draw_type ?? '') == $stdDrawAuto->std_tipe ? 'selected' : '' }}>
                                                                {{ $stdDrawAuto->std_tipe }}</option>
                                                        @endif
                                                        @if (!empty($dataAuto->draw_type) && $dataAuto->draw_type != ($stdDrawAuto->std_tipe ?? ''))
                                                            <option value="{{ $dataAuto->draw_type }}" selected>
                                                                {{ $dataAuto->draw_type }}</option>
                                                        @endif
                                                    </select>
                                                    <span
                                                        class="block text-[10px] text-slate-400 mt-1 leading-none">Std:
                                                        <span
                                                            class="font-bold text-slate-600">{{ $stdDrawAuto->std_tipe ?? '-' }}</span></span>
                                                </td>
                                                <td class="p-2 align-top text-center">
                                                    <select name="autowire[ann_type]"
                                                        class="w-full border-slate-200 rounded text-xs p-1.5 text-center bg-slate-50/50 cursor-pointer focus:ring-emerald-500">
                                                        <option value="">-- Pilih --</option>
                                                        @if (!empty($stdAnnAuto->std_tipe))
                                                            <option value="{{ $stdAnnAuto->std_tipe }}"
                                                                {{ ($dataAuto->ann_type ?? '') == $stdAnnAuto->std_tipe ? 'selected' : '' }}>
                                                                {{ $stdAnnAuto->std_tipe }}</option>
                                                        @endif
                                                        @if (!empty($dataAuto->ann_type) && $dataAuto->ann_type != ($stdAnnAuto->std_tipe ?? ''))
                                                            <option value="{{ $dataAuto->ann_type }}" selected>
                                                                {{ $dataAuto->ann_type }}</option>
                                                        @endif
                                                    </select>
                                                    <span
                                                        class="block text-[10px] text-slate-400 mt-1 leading-none">Std:
                                                        <span
                                                            class="font-bold text-slate-600">{{ $stdAnnAuto->std_tipe ?? '-' }}</span></span>
                                                </td>
                                            </tr>

                                            {{-- Supplier --}}
                                            <tr>
                                                <td
                                                    class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 align-top pt-4">
                                                    Supplier</td>
                                                <td class="p-2 align-top text-center">
                                                    <select name="autowire[draw_supplier]"
                                                        class="w-full border-slate-200 rounded text-xs p-1.5 text-center cursor-pointer focus:ring-blue-500">
                                                        <option value="">-- Pilih --</option>
                                                        @if (!empty($stdDrawAuto->std_supplier))
                                                            <option value="{{ $stdDrawAuto->std_supplier }}"
                                                                {{ ($dataAuto->draw_supplier ?? '') == $stdDrawAuto->std_supplier ? 'selected' : '' }}>
                                                                {{ $stdDrawAuto->std_supplier }}</option>
                                                        @endif
                                                        @if (!empty($dataAuto->draw_supplier) && $dataAuto->draw_supplier != ($stdDrawAuto->std_supplier ?? ''))
                                                            <option value="{{ $dataAuto->draw_supplier }}" selected>
                                                                {{ $dataAuto->draw_supplier }}</option>
                                                        @endif
                                                    </select>
                                                    <span
                                                        class="block text-[10px] text-slate-400 mt-1 leading-none">Std:
                                                        <span
                                                            class="font-bold text-slate-600">{{ $stdDrawAuto->std_supplier ?? '-' }}</span></span>
                                                </td>
                                                <td class="p-2 align-top text-center">
                                                    <select name="autowire[ann_supplier]"
                                                        class="w-full border-slate-200 rounded text-xs p-1.5 text-center cursor-pointer focus:ring-emerald-500">
                                                        <option value="">-- Pilih --</option>
                                                        @if (!empty($stdAnnAuto->std_supplier))
                                                            <option value="{{ $stdAnnAuto->std_supplier }}"
                                                                {{ ($dataAuto->ann_supplier ?? '') == $stdAnnAuto->std_supplier ? 'selected' : '' }}>
                                                                {{ $stdAnnAuto->std_supplier }}</option>
                                                        @endif
                                                        @if (!empty($dataAuto->ann_supplier) && $dataAuto->ann_supplier != ($stdAnnAuto->std_supplier ?? ''))
                                                            <option value="{{ $dataAuto->ann_supplier }}" selected>
                                                                {{ $dataAuto->ann_supplier }}</option>
                                                        @endif
                                                    </select>
                                                    <span
                                                        class="block text-[10px] text-slate-400 mt-1 leading-none">Std:
                                                        <span
                                                            class="font-bold text-slate-600">{{ $stdAnnAuto->std_supplier ?? '-' }}</span></span>
                                                </td>
                                            </tr>

                                            {{-- Warna --}}
                                            <tr>
                                                <td
                                                    class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 align-top pt-4">
                                                    Warna</td>
                                                <td class="p-2 align-top">
                                                    @php $val = $dataAuto->draw_warna ?? ''; @endphp
                                                    <select name="autowire[draw_warna]"
                                                        class="check-oos w-full border-slate-200 rounded text-xs p-1.5 text-center cursor-pointer focus:ring-blue-500"
                                                        data-std="{{ $stdDrawAuto->std_warna ?? '' }}">
                                                        <option value="">- Pilih Warna -</option>
                                                        <option value="Hijau Putih"
                                                            {{ $val == 'Hijau Putih' ? 'selected' : '' }}>Hijau Putih
                                                        </option>
                                                        <option value="Putih"
                                                            {{ $val == 'Putih' ? 'selected' : '' }}>Putih</option>
                                                        <option value="Cokelat Kekuningan"
                                                            {{ $val == 'Cokelat Kekuningan' ? 'selected' : '' }}>
                                                            Cokelat Kekuningan</option>
                                                        @if (!empty($val) && !in_array($val, ['Hijau Putih', 'Putih', 'Cokelat Kekuningan']))
                                                            <option value="{{ $val }}" selected>
                                                                {{ $val }}</option>
                                                        @endif
                                                    </select>
                                                    <span
                                                        class="block text-[10px] text-slate-400 mt-1 leading-none text-center">Std:
                                                        <span
                                                            class="font-bold text-slate-600 text-center">{{ $stdDrawAuto->std_warna ?? '-' }}</span></span>
                                                </td>
                                                <td class="p-2 align-top">
                                                    @php $val = $dataAuto->ann_warna ?? ''; @endphp
                                                    <select name="autowire[ann_warna]"
                                                        class="check-oos w-full border-slate-200 rounded text-xs p-1.5 text-center cursor-pointer focus:ring-emerald-500"
                                                        data-std="{{ $stdAnnAuto->std_warna ?? '' }}">
                                                        <option value="">- Pilih Warna -</option>
                                                        <option value="Hijau Putih"
                                                            {{ $val == 'Hijau Putih' ? 'selected' : '' }}>Hijau Putih
                                                        </option>
                                                        <option value="Putih"
                                                            {{ $val == 'Putih' ? 'selected' : '' }}>Putih</option>
                                                        <option value="Cokelat Kekuningan"
                                                            {{ $val == 'Cokelat Kekuningan' ? 'selected' : '' }}>
                                                            Cokelat Kekuningan</option>
                                                        @if (!empty($val) && !in_array($val, ['Hijau Putih', 'Putih', 'Cokelat Kekuningan']))
                                                            <option value="{{ $val }}" selected>
                                                                {{ $val }}</option>
                                                        @endif
                                                    </select>
                                                    <span
                                                        class="block text-[10px] text-slate-400 mt-1 leading-none text-center">Std:
                                                        <span
                                                            class="font-bold text-slate-600 text-center">{{ $stdAnnAuto->std_warna ?? '-' }}</span></span>
                                                </td>
                                            </tr>

                                            {{-- Konsentrasi --}}
                                            <tr>
                                                <td
                                                    class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 align-top pt-4">
                                                    Konsentrasi</td>
                                                <td class="p-2 align-top">
                                                    @php
                                                        $val = isset($dataAuto->draw_konsentrasi)
                                                            ? str_replace('%', '', $dataAuto->draw_konsentrasi)
                                                            : '';
                                                        $opts = generateDropdownOptions(
                                                            $stdDrawAuto->std_konsentrasi,
                                                            'draw_kons',
                                                        );
                                                    @endphp
                                                    <select name="autowire[draw_konsentrasi]"
                                                        class="check-oos w-full border-slate-200 rounded text-xs p-1.5 text-center font-bold text-blue-600 cursor-pointer focus:ring-blue-500"
                                                        data-std="{{ $stdDrawAuto->std_konsentrasi ?? '' }}">
                                                        <option value="">- Pilih -</option>
                                                        @foreach ($opts as $opt)
                                                            <option value="{{ $opt }}"
                                                                {{ (string) $val === (string) $opt ? 'selected' : '' }}>
                                                                {{ $opt }} %</option>
                                                        @endforeach
                                                        @if ($val !== '' && !in_array((float) $val, $opts))
                                                            <option value="{{ $val }}" selected>
                                                                {{ $val }} %</option>
                                                        @endif
                                                    </select>
                                                    <span
                                                        class="block text-[10px] text-slate-400 mt-1 leading-none text-center">Std:
                                                        <span
                                                            class="font-bold text-slate-600 text-center">{{ $stdDrawAuto->std_konsentrasi ?? '-' }}</span></span>
                                                </td>
                                                <td class="p-2 align-top">
                                                    @php
                                                        $val = isset($dataAuto->ann_konsentrasi)
                                                            ? str_replace('%', '', $dataAuto->ann_konsentrasi)
                                                            : '';
                                                        $opts = generateDropdownOptions(
                                                            $stdAnnAuto->std_konsentrasi,
                                                            'ann_kons',
                                                        );
                                                    @endphp
                                                    <select name="autowire[ann_konsentrasi]"
                                                        class="check-oos w-full border-slate-200 rounded text-xs p-1.5 text-center font-bold text-emerald-600 cursor-pointer focus:ring-emerald-500"
                                                        data-std="{{ $stdAnnAuto->std_konsentrasi ?? '' }}">
                                                        <option value="">- Pilih -</option>
                                                        @foreach ($opts as $opt)
                                                            <option value="{{ $opt }}"
                                                                {{ (string) $val === (string) $opt ? 'selected' : '' }}>
                                                                {{ $opt }} %</option>
                                                        @endforeach
                                                        @if ($val !== '' && !in_array((float) $val, $opts))
                                                            <option value="{{ $val }}" selected>
                                                                {{ $val }} %</option>
                                                        @endif
                                                    </select>
                                                    <span
                                                        class="block text-[10px] text-slate-400 mt-1 leading-none text-center">Std:
                                                        <span
                                                            class="font-bold text-slate-600 text-center">{{ $stdAnnAuto->std_konsentrasi ?? '-' }}</span></span>
                                                </td>
                                            </tr>

                                            {{-- pH Level --}}
                                            <tr>
                                                <td
                                                    class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 align-top pt-4">
                                                    pH Level</td>
                                                <td class="p-2 align-top text-center">
                                                    @php
                                                        $val = $dataAuto->draw_ph ?? '';
                                                        $opts = generateDropdownOptions($stdDrawAuto->std_ph, 'ph');
                                                    @endphp
                                                    <select name="autowire[draw_ph]"
                                                        class="check-oos w-full border-slate-200 rounded text-xs p-1.5 text-center font-bold text-blue-700 cursor-pointer focus:ring-blue-500"
                                                        data-std="{{ $stdDrawAuto->std_ph ?? '' }}">
                                                        <option value="">- Pilih -</option>
                                                        @foreach ($opts as $opt)
                                                            <option value="{{ $opt }}"
                                                                {{ (string) $val === (string) $opt ? 'selected' : '' }}>
                                                                {{ $opt }}</option>
                                                        @endforeach
                                                        @if ($val !== '' && !in_array((float) $val, $opts))
                                                            <option value="{{ $val }}" selected>
                                                                {{ $val }}</option>
                                                        @endif
                                                    </select>
                                                    <span
                                                        class="block text-[10px] text-slate-400 mt-1 leading-none">Std:
                                                        <span
                                                            class="font-bold text-slate-600">{{ $stdDrawAuto->std_ph ?? '-' }}</span></span>
                                                </td>
                                                <td class="p-2 align-top text-center">
                                                    @php
                                                        $val = $dataAuto->ann_ph ?? '';
                                                        $opts = generateDropdownOptions($stdAnnAuto->std_ph, 'ph');
                                                    @endphp
                                                    <select name="autowire[ann_ph]"
                                                        class="check-oos w-full border-slate-200 rounded text-xs p-1.5 text-center font-bold text-emerald-700 cursor-pointer focus:ring-emerald-500"
                                                        data-std="{{ $stdAnnAuto->std_ph ?? '' }}">
                                                        <option value="">- Pilih -</option>
                                                        @foreach ($opts as $opt)
                                                            <option value="{{ $opt }}"
                                                                {{ (string) $val === (string) $opt ? 'selected' : '' }}>
                                                                {{ $opt }}</option>
                                                        @endforeach
                                                        @if ($val !== '' && !in_array((float) $val, $opts))
                                                            <option value="{{ $val }}" selected>
                                                                {{ $val }}</option>
                                                        @endif
                                                    </select>
                                                    <span
                                                        class="block text-[10px] text-slate-400 mt-1 leading-none">Std:
                                                        <span
                                                            class="font-bold text-slate-600">{{ $stdAnnAuto->std_ph ?? '-' }}</span></span>
                                                </td>
                                            </tr>

                                            {{-- Temperatur --}}
                                            <tr>
                                                <td
                                                    class="p-3 text-xs font-bold text-slate-700 sticky left-0 bg-white z-10 align-top pt-4">
                                                    Temperatur</td>
                                                <td class="p-2 align-top text-center">
                                                    <div class="flex items-center">
                                                        <input type="number" name="autowire[draw_temp]"
                                                            value="{{ isset($dataAuto->draw_temp) ? str_replace(['°C', 'C'], '', $dataAuto->draw_temp) : '' }}"
                                                            class="w-full border-slate-200 rounded-l text-xs p-1.5 text-center">
                                                        <span
                                                            class="bg-slate-100 border border-l-0 border-slate-200 px-1 py-1.5 text-[10px] font-bold text-slate-500 rounded-r">°C</span>
                                                    </div>
                                                    <span
                                                        class="block text-[10px] text-slate-400 mt-1 leading-none">Std:
                                                        <span
                                                            class="font-bold text-slate-600">{{ $stdDrawAuto->std_temp ?? '-' }}</span></span>
                                                </td>
                                                <td class="p-2 align-top text-center">
                                                    <div class="flex items-center">
                                                        <input type="number" name="autowire[ann_temp]"
                                                            value="{{ isset($dataAuto->ann_temp) ? str_replace(['°C', 'C'], '', $dataAuto->ann_temp) : '' }}"
                                                            class="w-full border-slate-200 rounded-l text-xs p-1.5 text-center">
                                                        <span
                                                            class="bg-slate-100 border border-l-0 border-slate-200 px-1 py-1.5 text-[10px] font-bold text-slate-500 rounded-r">°C</span>
                                                    </div>
                                                    <span
                                                        class="block text-[10px] text-slate-400 mt-1 leading-none">Std:
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
                                                    <div class="flex items-center w-full sm:w-1/3">
                                                        <input type="number" step="any"
                                                            name="autowire[hourmeter]"
                                                            value="{{ isset($dataAuto->hourmeter) ? (float) str_replace(',', '.', $dataAuto->hourmeter) : '' }}"
                                                            class="w-full border-slate-200 rounded-l text-xs p-1.5 text-right font-bold focus:ring-indigo-500"
                                                            placeholder="0">
                                                        <span
                                                            class="bg-slate-100 border border-l-0 border-slate-200 px-2 py-1.5 text-[10px] font-bold text-slate-500 rounded-r">Jam</span>
                                                    </div>
                                                    <span class="block text-[9px] text-slate-400 mt-1 italic">*Wajib
                                                        diisi dengan angka hourmeter aktual mesin, gunakan titik (.)
                                                        untuk 2 angka dibelakang koma</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        {{-- FOOTER INFO (Catatan & Tanda Tangan) --}}
                        <div
                            class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6 items-end border-t border-slate-200 pt-5">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Keterangan / Tindakan
                                    Perbaikan</label>
                                <textarea name="keterangan" rows="4" placeholder="Kosongkan jika tidak ada catatan..."
                                    class="w-full rounded-lg border-slate-300 shadow-sm focus:ring-indigo-500 text-sm">{{ $checks->first()->keterangan ?? '' }}</textarea>
                            </div>

                            <div class="flex flex-col items-stretch sm:items-end w-full">
                                <div class="w-full sm:max-w-sm border-2 border-slate-800 bg-white shadow-sm">
                                    {{-- Bagian Operator --}}
                                    <div class="flex border-b-2 border-slate-800">
                                        <div
                                            class="w-2/5 p-2 font-bold border-r-2 border-slate-800 bg-rose-50 text-[10px] uppercase flex flex-col justify-center text-left">
                                            <span class="text-rose-700">Diedit Oleh (Wajib NIK):</span>
                                            <input type="text" x-model="operatorNik"
                                                @keyup.debounce.500ms="searchOperator()" placeholder="Ketik NIK..."
                                                class="mt-1.5 text-[10px] p-1.5 border-rose-300 rounded focus:ring-rose-500 font-bold uppercase w-full shadow-sm">
                                        </div>
                                        <div
                                            class="w-3/5 p-3 text-sm font-black text-center uppercase truncate text-indigo-700 bg-indigo-50/50 flex flex-col items-center justify-center">
                                            <span x-show="isSearching"
                                                class="text-slate-400 animate-pulse text-[10px]">Mencari...</span>
                                            <span x-show="!isSearching" x-text="operatorName"
                                                :class="operatorName === 'DATA TIDAK DITEMUKAN' ?
                                                    'text-red-500 font-bold text-[10px]' : ''"></span>
                                            <span
                                                class="text-[8px] text-slate-500 font-semibold mt-0.5 normal-case bg-white/60 px-2 py-0.5 rounded-full">
                                                Awal: {{ $checks->first()->diperiksa_oleh ?? 'Tidak diketahui' }}
                                            </span>
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
                                <p class="text-[10px] text-slate-400 mt-2 font-bold">Terakhir diperbarui:
                                    <span class="font-normal">
                                        {{ $checks->first()?->updated_at ? $checks->first()->updated_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : 'Belum pernah diupdate' }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        {{-- SUBMIT BAR —  Sticky bottom --}}
                        <div
                            class="mt-6 pt-4 border-t border-slate-200 flex justify-end gap-2 sticky bottom-0 bg-slate-50/95 backdrop-blur-sm -mx-3 sm:-mx-6 px-3 sm:px-6 pb-3">
                            <button type="button" @click="showEditModal = false"
                                class="px-4 sm:px-5 py-2.5 text-sm font-bold text-slate-600 bg-white border border-slate-300 rounded-xl hover:bg-slate-100 transition-all">
                                Batal
                            </button>
                            <button type="submit"
                                :disabled="operatorName === 'DATA TIDAK DITEMUKAN' || operatorName === 'ERROR KONEKSI' ||
                                    operatorName === '........................'"
                                :class="(operatorName === 'DATA TIDAK DITEMUKAN' || operatorName === 'ERROR KONEKSI' ||
                                    operatorName === '........................') ?
                                'bg-slate-300 cursor-not-allowed text-slate-500' :
                                'bg-indigo-600 hover:bg-indigo-700 text-white'"
                                class="px-5 sm:px-8 py-2.5 rounded-xl font-bold text-sm shadow-md transition-all active:scale-95 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                Simpan Perubahan
                            </button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>
</template>
