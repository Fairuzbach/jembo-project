<template x-teleport="body">
    <div x-show="showCompoundModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog"
        aria-modal="true">
        <div x-show="showCompoundModal" x-transition.opacity
            class="fixed inset-0 bg-slate-800 bg-opacity-75 transition-opacity" @click="showCompoundModal = false"></div>

        <div class="flex min-h-full items-center justify-center p-3 text-center sm:p-0">
            <div x-show="showCompoundModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-2xl transition-all sm:my-8 w-full sm:max-w-5xl border-t-8 border-red-700">

                {{-- HEADER --}}
                <div
                    class="bg-white px-4 sm:px-6 py-3 sm:py-4 border-b border-slate-200 flex justify-between items-center">
                    <div>
                        <h2 class="text-lg sm:text-xl font-extrabold text-slate-800 leading-tight">Compound Parameter
                            Checking</h2>
                        <h3 class="text-[10px] sm:text-sm font-bold text-red-600 uppercase tracking-wider">Input Data
                            Pengecekan</h3>
                    </div>
                    <button @click="showCompoundModal = false"
                        class="text-slate-400 hover:text-red-500 bg-slate-100 hover:bg-red-50 rounded-full p-2 transition flex-shrink-0">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                {{-- FORM START --}}
                <form action="{{ route('eng.storeCompound') }}" method="POST">
                    @csrf

                    <div class="px-2 sm:px-6 py-4 bg-slate-50">

                        {{-- Baris Identitas (Responsif Padat di HP) --}}
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 sm:gap-4 mb-5 px-2 sm:px-0">
                            {{-- INPUT PLANT --}}
                            <div class="col-span-2 md:col-span-1">
                                <label class="block text-[11px] sm:text-sm font-bold text-slate-700 mb-1">Plant Area
                                    <span class="text-red-500">*</span></label>
                                <select name="plant" x-model="compoundForm.plant" @change="onCompoundPlantChange()"
                                    class="w-full rounded border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-xs sm:text-sm py-1.5 sm:py-2"
                                    required>
                                    <option value="">-- Pilih Plant --</option>
                                    <template x-for="plant in allPlants" :key="plant.id">
                                        <option :value="plant.name" x-text="plant.name"></option>
                                    </template>
                                </select>
                            </div>

                            {{-- INPUT NAMA MESIN --}}
                            <div class="col-span-2 md:col-span-1">
                                <label
                                    class="block text-[11px] sm:text-sm font-bold text-slate-700 mb-1 flex justify-between">
                                    <span>Mesin <span class="text-red-500">*</span></span>
                                    <span x-show="isManualInput && compoundForm.plant"
                                        class="text-[10px] text-red-500 font-normal">Ketik Manual</span>
                                </label>
                                <select x-show="!isManualInput" x-model="compoundForm.machine_name" name="machine_name"
                                    class="w-full rounded border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-xs sm:text-sm py-1.5 sm:py-2"
                                    :disabled="isManualInput" :required="!isManualInput">
                                    <option value="">-- Pilih Mesin --</option>
                                    <template x-for="mesin in machineOptions" :key="mesin.id">
                                        <option :value="mesin.name" x-text="mesin.name"></option>
                                    </template>
                                </select>
                                <input x-show="isManualInput" type="text" x-model="compoundForm.machine_name"
                                    name="machine_name" placeholder="Nama mesin..."
                                    class="w-full rounded border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-xs sm:text-sm py-1.5 sm:py-2"
                                    :disabled="!isManualInput" :required="isManualInput">
                            </div>

                            {{-- TANGGAL CEK --}}
                            <div class="col-span-1">
                                <label class="block text-[11px] sm:text-sm font-bold text-slate-700 mb-1">Tanggal <span
                                        class="text-red-500">*</span></label>
                                <input type="date" name="tanggal_cek" x-model="compoundForm.tanggal_cek"
                                    class="w-full rounded border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-[10px] sm:text-sm py-1.5 sm:py-2"
                                    required>
                            </div>

                            {{-- PETUGAS --}}
                            <div class="col-span-1">
                                <label
                                    class="block text-[11px] sm:text-sm font-bold text-slate-700 mb-1">Petugas</label>
                                <input type="text" value="{{ auth()->user()->name }}" readonly
                                    class="w-full rounded border-slate-300 bg-slate-200 text-slate-600 shadow-sm cursor-not-allowed text-xs sm:text-sm py-1.5 sm:py-2 truncate">
                            </div>
                        </div>

                        {{-- BAGIAN INTI: SWIPE HORIZONTAL DI HP, GRID DI PC --}}
                        {{-- Penggunaan 'flex flex-nowrap overflow-x-auto snap-x' memungkinkan geser kanan-kiri di HP --}}
                        <div
                            class="flex flex-nowrap md:grid md:grid-cols-2 gap-4 overflow-x-auto snap-x snap-mandatory pb-4 px-2 sm:px-0 scroll-smooth">

                            {{-- KOLOM KIRI: Pengecekan Compound Drawing --}}
                            {{-- w-[90%] membuat sedikit bagian kartu kedua mengintip, agar user tau bisa digeser --}}
                            <div
                                class="w-[90%] md:w-full flex-none snap-center bg-white border border-slate-300 rounded-lg shadow-sm overflow-hidden">
                                <div class="bg-slate-200 border-b border-slate-300 px-3 py-2">
                                    <h4 class="font-bold text-slate-800 text-center text-xs sm:text-base">Compound
                                        Drawing</h4>
                                </div>
                                <div class="p-3 space-y-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-5/12"><label class="block text-xs font-bold text-slate-700">Type /
                                                Item</label><span
                                                class="text-[10px] text-slate-500 block leading-none">Std:
                                                WT-2050D</span></div>
                                        <input type="text" name="drawing_type" x-model="compoundForm.drawing_type"
                                            placeholder="Aktual"
                                            class="w-7/12 rounded border-slate-300 text-xs sm:text-sm focus:ring-red-500 focus:border-red-500 py-1.5">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-5/12"><label
                                                class="block text-xs font-bold text-slate-700">Supplier</label><span
                                                class="text-[10px] text-slate-500 block leading-none">Std: HOUSIN</span>
                                        </div>
                                        <input type="text" name="drawing_supplier"
                                            x-model="compoundForm.drawing_supplier" placeholder="Aktual"
                                            class="w-7/12 rounded border-slate-300 text-xs sm:text-sm focus:ring-red-500 focus:border-red-500 py-1.5">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-5/12"><label
                                                class="block text-xs font-bold text-slate-700">Warna</label><span
                                                class="text-[10px] text-slate-500 block leading-none">Std: Hijau
                                                Putih</span></div>
                                        <input type="text" name="drawing_warna" x-model="compoundForm.drawing_warna"
                                            placeholder="Aktual"
                                            class="w-7/12 rounded border-slate-300 text-xs sm:text-sm focus:ring-red-500 focus:border-red-500 py-1.5">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-5/12"><label
                                                class="block text-xs font-bold text-slate-700">Konsentrasi
                                                (%)</label><span
                                                class="text-[10px] text-slate-500 block leading-none">Std: 6% -
                                                8%</span></div>
                                        <input type="text" name="drawing_konsentrasi"
                                            x-model="compoundForm.drawing_konsentrasi" placeholder="Aktual"
                                            class="w-7/12 rounded border-slate-300 text-xs sm:text-sm focus:ring-red-500 focus:border-red-500 py-1.5">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-5/12"><label
                                                class="block text-xs font-bold text-slate-700">pH</label><span
                                                class="text-[10px] text-slate-500 block leading-none">Std: 8 - 9</span>
                                        </div>
                                        <input type="text" name="drawing_ph" x-model="compoundForm.drawing_ph"
                                            placeholder="Aktual"
                                            class="w-7/12 rounded border-slate-300 text-xs sm:text-sm focus:ring-red-500 focus:border-red-500 py-1.5">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-5/12"><label class="block text-xs font-bold text-slate-700">Temp
                                                (°C)</label><span
                                                class="text-[10px] text-slate-500 block leading-none">Std: 35°C -
                                                40°C</span></div>
                                        <input type="text" name="drawing_temp" x-model="compoundForm.drawing_temp"
                                            placeholder="Aktual"
                                            class="w-7/12 rounded border-slate-300 text-xs sm:text-sm focus:ring-red-500 focus:border-red-500 py-1.5">
                                    </div>
                                </div>
                            </div>

                            {{-- KOLOM KANAN: Pengecekan Compound Annealing --}}
                            <div
                                class="w-[90%] md:w-full flex-none snap-center bg-white border border-slate-300 rounded-lg shadow-sm overflow-hidden">
                                <div class="bg-slate-200 border-b border-slate-300 px-3 py-2">
                                    <h4 class="font-bold text-slate-800 text-center text-xs sm:text-base">Compound
                                        Annealing</h4>
                                </div>
                                <div class="p-3 space-y-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-5/12"><label class="block text-xs font-bold text-slate-700">Type
                                                / Item</label><span
                                                class="text-[10px] text-slate-500 block leading-none">Std: B-600</span>
                                        </div>
                                        <input type="text" name="annealing_type"
                                            x-model="compoundForm.annealing_type" placeholder="Aktual"
                                            class="w-7/12 rounded border-slate-300 text-xs sm:text-sm focus:ring-red-500 focus:border-red-500 py-1.5">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-5/12"><label
                                                class="block text-xs font-bold text-slate-700">Supplier</label><span
                                                class="text-[10px] text-slate-500 block leading-none">Std:
                                                HOUSIN</span></div>
                                        <input type="text" name="annealing_supplier"
                                            x-model="compoundForm.annealing_supplier" placeholder="Aktual"
                                            class="w-7/12 rounded border-slate-300 text-xs sm:text-sm focus:ring-red-500 focus:border-red-500 py-1.5">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-5/12"><label
                                                class="block text-xs font-bold text-slate-700">Warna</label><span
                                                class="text-[10px] text-slate-500 block leading-none">Std: Putih</span>
                                        </div>
                                        <input type="text" name="annealing_warna"
                                            x-model="compoundForm.annealing_warna" placeholder="Aktual"
                                            class="w-7/12 rounded border-slate-300 text-xs sm:text-sm focus:ring-red-500 focus:border-red-500 py-1.5">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-5/12"><label
                                                class="block text-xs font-bold text-slate-700">Konsentrasi
                                                (%)</label><span
                                                class="text-[10px] text-slate-500 block leading-none">Std: 0.5% -
                                                1%</span></div>
                                        <input type="text" name="annealing_konsentrasi"
                                            x-model="compoundForm.annealing_konsentrasi" placeholder="Aktual"
                                            class="w-7/12 rounded border-slate-300 text-xs sm:text-sm focus:ring-red-500 focus:border-red-500 py-1.5">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-5/12"><label
                                                class="block text-xs font-bold text-slate-700">pH</label><span
                                                class="text-[10px] text-slate-500 block leading-none">Std: 6.5 -
                                                7.5</span></div>
                                        <input type="text" name="annealing_ph" x-model="compoundForm.annealing_ph"
                                            placeholder="Aktual"
                                            class="w-7/12 rounded border-slate-300 text-xs sm:text-sm focus:ring-red-500 focus:border-red-500 py-1.5">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-5/12"><label class="block text-xs font-bold text-slate-700">Temp
                                                (°C)</label><span
                                                class="text-[10px] text-slate-500 block leading-none">Std: 35°C -
                                                40°C</span></div>
                                        <input type="text" name="annealing_temp"
                                            x-model="compoundForm.annealing_temp" placeholder="Aktual"
                                            class="w-7/12 rounded border-slate-300 text-xs sm:text-sm focus:ring-red-500 focus:border-red-500 py-1.5">
                                    </div>
                                </div>
                            </div>

                            {{-- Spacer kecil di akhir agar card Annealing tidak menempel tembok saat digeser ke ujung --}}
                            <div class="w-[5%] md:hidden flex-none"></div>

                        </div>

                        {{-- Keterangan / Catatan Bawah --}}
                        <div class="mt-2 px-2 sm:px-0">
                            <label class="block text-xs sm:text-sm font-bold text-slate-700 mb-1">Keterangan /
                                Catatan</label>
                            <textarea name="keterangan" x-model="compoundForm.keterangan" rows="2"
                                placeholder="Bila terjadi hal yang meragukan..."
                                class="w-full rounded border-slate-300 text-xs sm:text-sm focus:ring-red-500 focus:border-red-500 py-1.5"></textarea>
                        </div>

                    </div>

                    {{-- FOOTER SUBMIT --}}
                    <div
                        class="bg-slate-100 px-4 sm:px-6 py-3 flex flex-col sm:flex-row-reverse gap-2 border-t border-slate-200">
                        <button type="submit"
                            class="w-full sm:w-auto inline-flex justify-center rounded-md bg-red-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            Simpan Data
                        </button>
                        <button type="button" @click="showCompoundModal = false"
                            class="w-full sm:w-auto inline-flex justify-center rounded-md border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none transition">
                            Batal
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</template>
