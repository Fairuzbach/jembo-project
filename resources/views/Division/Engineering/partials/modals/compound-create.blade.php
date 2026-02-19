<template x-teleport="body">
    <div x-show="showCompoundModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog"
        aria-modal="true">
        <div x-show="showCompoundModal" x-transition.opacity
            class="fixed inset-0 bg-slate-800 bg-opacity-75 transition-opacity" @click="showCompoundModal = false"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div x-show="showCompoundModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-4xl border-t-8 border-red-700">

                {{-- HEADER (Responsif: Menumpuk di HP, Sejajar di PC) --}}
                <div
                    class="bg-white px-4 sm:px-6 py-4 border-b border-slate-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <h2 class="text-lg sm:text-xl font-extrabold text-slate-800">Form Pengecekan Compound</h2>
                        <h3 class="text-xs sm:text-sm font-semibold text-red-600 mt-1 uppercase tracking-wider">Input
                            Data Pengecekan</h3>
                    </div>
                    <button @click="showCompoundModal = false"
                        class="absolute top-4 right-4 sm:relative sm:top-0 sm:right-0 text-slate-400 hover:text-red-500 bg-slate-100 hover:bg-red-50 rounded-full p-2 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                {{-- FORM START --}}
                <form action="{{ route('eng.storeCompound') }}" method="POST">
                    @csrf

                    <div class="px-4 sm:px-6 py-4 sm:py-6 bg-slate-50">

                        {{-- Baris Mesin, Tanggal & Petugas (Responsif: 1 Kolom HP, 3 Kolom PC) --}}
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                            {{-- INPUT NAMA MESIN --}}
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Plant Area <span
                                        class="text-red-500">*</span></label>
                                <select name="plant" x-model="compoundForm.plant" @change="onCompoundPlantChange()"
                                    class="w-full rounded-md border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500 font-semibold sm:text-sm"
                                    required>
                                    <option value="">-- Pilih Plant --</option>
                                    <template x-for="plant in allPlants" :key="plant.id">
                                        <option :value="plant.name" x-text="plant.name"></option>
                                    </template>
                                </select>
                            </div>

                            {{-- INPUT NAMA MESIN (Dropdown Dinamis) --}}
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">
                                    Nama Mesin <span class="text-red-500">*</span>
                                    <span x-show="isManualInput && compoundForm.plant"
                                        class="text-xs text-red-500 ml-1 font-normal">(Ketik Manual)</span>
                                </label>

                                {{-- Dropdown jika mesin tersedia --}}
                                <select x-show="!isManualInput" x-model="compoundForm.machine_name" name="machine_name"
                                    class="w-full rounded-md border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500 font-semibold sm:text-sm"
                                    :disabled="isManualInput" :required="!isManualInput">
                                    <option value="">-- Pilih Mesin --</option>
                                    <template x-for="mesin in machineOptions" :key="mesin.id">
                                        <option :value="mesin.name" x-text="mesin.name"></option>
                                    </template>
                                </select>

                                {{-- Input Text jika mesin tidak tersedia di database --}}
                                <input x-show="isManualInput" type="text" x-model="compoundForm.machine_name"
                                    name="machine_name" placeholder="Ketik nama mesin..."
                                    class="w-full rounded-md border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500 font-semibold sm:text-sm"
                                    :disabled="!isManualInput" :required="isManualInput">
                            </div>

                            {{-- INPUT TANGGAL CEK --}}
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Tanggal Cek (Actual) <span
                                        class="text-red-500">*</span></label>
                                <input type="date" name="tanggal_cek" x-model="compoundForm.tanggal_cek"
                                    class="w-full rounded-md border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500 font-semibold"
                                    required>
                            </div>

                            {{-- PETUGAS --}}
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Diperiksa Oleh</label>
                                <input type="text" value="{{ auth()->user()->name }}" readonly
                                    class="w-full rounded-md border-slate-300 bg-slate-200 text-slate-600 shadow-sm cursor-not-allowed font-semibold">
                            </div>
                        </div>

                        {{-- Layout Utama (Responsif: 1 Kolom HP, 2 Kolom PC) --}}
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                            {{-- KOLOM KIRI: Pengecekan Compound Drawing --}}
                            <div class="bg-white border border-slate-300 rounded-lg shadow-sm overflow-hidden">
                                <div class="bg-slate-200 border-b border-slate-300 px-4 py-2">
                                    <h4 class="font-bold text-slate-800 text-center text-sm sm:text-base">Pengecekan
                                        Compound Drawing</h4>
                                </div>
                                <div class="p-4 space-y-4">

                                    {{-- FIELD SET (Responsif: Atas-Bawah di HP, Kiri-Kanan di PC) --}}
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                                        <div class="w-full sm:w-1/3">
                                            <label
                                                class="block text-sm font-semibold text-slate-700 sm:text-slate-600">Type
                                                / Item</label>

                                        </div>
                                        <input type="text" name="drawing_type" x-model="compoundForm.drawing_type"
                                            placeholder="Aktual"
                                            class="w-full sm:w-2/3 rounded-md border-slate-300 text-sm focus:ring-red-500 focus:border-red-500">
                                    </div>

                                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                                        <div class="w-full sm:w-1/3">
                                            <label
                                                class="block text-sm font-semibold text-slate-700 sm:text-slate-600">Supplier</label>

                                        </div>
                                        <input type="text" name="drawing_supplier"
                                            x-model="compoundForm.drawing_supplier" placeholder="Aktual"
                                            class="w-full sm:w-2/3 rounded-md border-slate-300 text-sm focus:ring-red-500 focus:border-red-500">
                                    </div>

                                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                                        <div class="w-full sm:w-1/3">
                                            <label
                                                class="block text-sm font-semibold text-slate-700 sm:text-slate-600">Warna</label>

                                        </div>
                                        <input type="text" name="drawing_warna" x-model="compoundForm.drawing_warna"
                                            placeholder="Aktual"
                                            class="w-full sm:w-2/3 rounded-md border-slate-300 text-sm focus:ring-red-500 focus:border-red-500">
                                    </div>

                                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                                        <div class="w-full sm:w-1/3">
                                            <label
                                                class="block text-sm font-semibold text-slate-700 sm:text-slate-600">Konsentrasi
                                                (%)</label>

                                        </div>
                                        <input type="text" name="drawing_konsentrasi"
                                            x-model="compoundForm.drawing_konsentrasi" placeholder="Aktual"
                                            class="w-full sm:w-2/3 rounded-md border-slate-300 text-sm focus:ring-red-500 focus:border-red-500">
                                    </div>

                                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                                        <div class="w-full sm:w-1/3">
                                            <label
                                                class="block text-sm font-semibold text-slate-700 sm:text-slate-600">pH</label>

                                        </div>
                                        <input type="text" name="drawing_ph" x-model="compoundForm.drawing_ph"
                                            placeholder="Aktual"
                                            class="w-full sm:w-2/3 rounded-md border-slate-300 text-sm focus:ring-red-500 focus:border-red-500">
                                    </div>

                                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                                        <div class="w-full sm:w-1/3">
                                            <label
                                                class="block text-sm font-semibold text-slate-700 sm:text-slate-600">Temperatur
                                                (°C)</label>

                                        </div>
                                        <input type="text" name="drawing_temp" x-model="compoundForm.drawing_temp"
                                            placeholder="Aktual"
                                            class="w-full sm:w-2/3 rounded-md border-slate-300 text-sm focus:ring-red-500 focus:border-red-500">
                                    </div>

                                </div>
                            </div>

                            {{-- KOLOM KANAN: Pengecekan Compound Annealing --}}
                            <div class="bg-white border border-slate-300 rounded-lg shadow-sm overflow-hidden">
                                <div class="bg-slate-200 border-b border-slate-300 px-4 py-2">
                                    <h4 class="font-bold text-slate-800 text-center text-sm sm:text-base">Pengecekan
                                        Compound Annealing</h4>
                                </div>
                                <div class="p-4 space-y-4">

                                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                                        <div class="w-full sm:w-1/3">
                                            <label
                                                class="block text-sm font-semibold text-slate-700 sm:text-slate-600">Type
                                                / Item</label>

                                        </div>
                                        <input type="text" name="annealing_type"
                                            x-model="compoundForm.annealing_type" placeholder="Aktual"
                                            class="w-full sm:w-2/3 rounded-md border-slate-300 text-sm focus:ring-red-500 focus:border-red-500">
                                    </div>

                                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                                        <div class="w-full sm:w-1/3">
                                            <label
                                                class="block text-sm font-semibold text-slate-700 sm:text-slate-600">Supplier</label>

                                        </div>
                                        <input type="text" name="annealing_supplier"
                                            x-model="compoundForm.annealing_supplier" placeholder="Aktual"
                                            class="w-full sm:w-2/3 rounded-md border-slate-300 text-sm focus:ring-red-500 focus:border-red-500">
                                    </div>

                                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                                        <div class="w-full sm:w-1/3">
                                            <label
                                                class="block text-sm font-semibold text-slate-700 sm:text-slate-600">Warna</label>

                                        </div>
                                        <input type="text" name="annealing_warna"
                                            x-model="compoundForm.annealing_warna" placeholder="Aktual"
                                            class="w-full sm:w-2/3 rounded-md border-slate-300 text-sm focus:ring-red-500 focus:border-red-500">
                                    </div>

                                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                                        <div class="w-full sm:w-1/3">
                                            <label
                                                class="block text-sm font-semibold text-slate-700 sm:text-slate-600">Konsentrasi
                                                (%)</label>

                                        </div>
                                        <input type="text" name="annealing_konsentrasi"
                                            x-model="compoundForm.annealing_konsentrasi" placeholder="Aktual"
                                            class="w-full sm:w-2/3 rounded-md border-slate-300 text-sm focus:ring-red-500 focus:border-red-500">
                                    </div>

                                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                                        <div class="w-full sm:w-1/3">
                                            <label
                                                class="block text-sm font-semibold text-slate-700 sm:text-slate-600">pH</label>

                                        </div>
                                        <input type="text" name="annealing_ph" x-model="compoundForm.annealing_ph"
                                            placeholder="Aktual"
                                            class="w-full sm:w-2/3 rounded-md border-slate-300 text-sm focus:ring-red-500 focus:border-red-500">
                                    </div>

                                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                                        <div class="w-full sm:w-1/3">
                                            <label
                                                class="block text-sm font-semibold text-slate-700 sm:text-slate-600">Temperatur
                                                (°C)</label>

                                        </div>
                                        <input type="text" name="annealing_temp"
                                            x-model="compoundForm.annealing_temp" placeholder="Aktual"
                                            class="w-full sm:w-2/3 rounded-md border-slate-300 text-sm focus:ring-red-500 focus:border-red-500">
                                    </div>

                                </div>
                            </div>

                        </div>

                        {{-- Keterangan / Catatan Bawah --}}
                        <div class="mt-6">
                            <label class="block text-sm font-bold text-slate-700 mb-1">Keterangan / Catatan</label>
                            <textarea name="keterangan" x-model="compoundForm.keterangan" rows="2"
                                placeholder="Catatan tambahan (Bila terjadi hal yang meragukan...)"
                                class="w-full rounded-md border-slate-300 text-sm focus:ring-red-500 focus:border-red-500"></textarea>
                            {{-- <div
                                class="text-xs text-slate-500 mt-2 space-y-1 bg-white p-3 rounded border border-slate-200">
                                <p>• Pengukuran dilakukan setiap minggu pada Shift 1.</p>
                                <p>• Bila terjadi hal yang meragukan (berbau, berubah warna, banyak busa) segera info ke
                                    Engineering.</p>
                                <p>• Bila konsentrasi berkurang segera tambahkan compound.</p>
                            </div> --}}
                        </div>

                    </div>

                    {{-- FOOTER SUBMIT (Responsif: Lebar Penuh di HP) --}}
                    <div
                        class="bg-slate-100 px-4 sm:px-6 py-4 flex flex-col sm:flex-row-reverse gap-3 border-t border-slate-200">
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
