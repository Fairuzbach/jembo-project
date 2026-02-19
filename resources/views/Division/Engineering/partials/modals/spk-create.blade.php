<template x-teleport="body">
    <div x-show="showSpkModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog"
        aria-modal="true">
        {{-- Backdrop --}}
        <div x-show="showSpkModal" x-transition.opacity
            class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" @click="showSpkModal = false"></div>

        {{-- Modal Dialog --}}
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div x-show="showSpkModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-5xl border-t-4 border-blue-600">

                {{-- Header mirip Kop Surat --}}
                <div class="bg-white px-6 py-4 border-b border-slate-200 flex justify-between items-start">
                    <div>
                        <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest">PT JEMBO CABLE COMPANY
                            Tbk.</h3>
                        <h2 class="text-xl font-extrabold text-blue-700 mt-1">SURAT PERINTAH KERJA (SPK)</h2>
                        <p class="text-xs text-slate-400 mt-1">Maintenance & Engineering Dept.</p>
                    </div>
                    <button @click="showSpkModal = false"
                        class="text-slate-400 hover:text-slate-500 bg-slate-100 hover:bg-slate-200 rounded-full p-1 transition">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                {{-- FORM START --}}
                <form x-ref="spkCreateForm" action="{{ route('eng.store') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    {{-- Hidden input untuk membedakan tipe form di controller jika perlu --}}
                    <input type="hidden" name="form_type" value="SPK">

                    <div class="px-6 py-6">
                        {{-- SECTION 1: Informasi Dasar (Top Bar) --}}
                        <div
                            class="bg-slate-50 p-4 rounded-lg border border-slate-200 mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                            {{-- No SPK (Auto generated backend) --}}
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">No. SPK</label>
                                <input type="text" value="AUTO-GENERATED" disabled
                                    class="w-full rounded-md border-slate-300 bg-slate-200 text-slate-500 text-sm font-mono cursor-not-allowed">
                            </div>
                            {{-- Tanggal & Jam Lapor --}}
                            <div class="flex gap-2">
                                <div class="w-3/5">
                                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Tgl
                                        Lapor</label>
                                    <input type="date" name="report_date" x-model="currentDate" readonly
                                        class="w-full rounded-md border-slate-300 bg-slate-100 text-slate-700 text-sm font-semibold cursor-not-allowed">
                                </div>
                                <div class="w-2/5">
                                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Jam</label>
                                    <input type="text" name="report_time" x-model="currentTime" readonly
                                        class="w-full rounded-md border-slate-300 bg-slate-100 text-slate-700 text-sm font-semibold cursor-not-allowed text-center">
                                </div>
                            </div>
                            {{-- Pemberi Tugas & Shift --}}
                            <div class="flex gap-2">
                                <div class="w-full">
                                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Pemberi
                                        Tugas (User)</label>
                                    <input type="text" value="{{ auth()->user()->name }}" disabled
                                        class="w-full rounded-md border-slate-300 bg-slate-100 text-slate-700 text-sm font-semibold cursor-not-allowed">
                                </div>
                                <div class="w-24 flex-shrink-0">
                                    <label
                                        class="block text-xs font-semibold text-slate-500 uppercase mb-1">Shift</label>
                                    <input type="text" x-model="currentShift" disabled
                                        class="w-full rounded-md border-slate-300 bg-slate-100 text-slate-700 text-sm font-semibold cursor-not-allowed text-center">
                                </div>
                            </div>
                        </div>

                        {{-- SECTION 2: Detail Masalah & Pelaksanaan (Main Grid) --}}
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {{-- KOLOM KIRI: Masalah & Lokasi --}}
                            <div class="space-y-4">
                                <div class="bg-blue-50 p-3 rounded-t-lg border-b border-blue-100">
                                    <h4 class="font-bold text-blue-800 text-sm uppercase">A. Detail Lokasi & Masalah
                                    </h4>
                                </div>

                                {{-- Plant Selection --}}
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1">Plant Area <span
                                            class="text-red-500">*</span></label>
                                    <select name="plant" x-model="selectedPlant" @change="onPlantChange()"
                                        class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        required>
                                        <option value="">-- Pilih Plant --</option>
                                        <template x-for="plant in allPlants" :key="plant.id">
                                            <option :value="plant.name" x-text="plant.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- Nama Mesin --}}
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">
                                            Nama Mesin <span class="text-red-500">*</span>
                                            <span x-show="isManualInput && selectedPlant"
                                                class="text-xs text-blue-500 ml-1 font-normal">(Input Manual)</span>
                                        </label>
                                        <select x-show="!isManualInput" x-model="form.machine_name" name="machine_name"
                                            class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            :disabled="isManualInput" :required="!isManualInput">
                                            <option value="">-- Pilih Mesin --</option>
                                            <template x-for="mesin in machineOptions" :key="mesin.id">
                                                <option :value="mesin.name" x-text="mesin.name"></option>
                                            </template>
                                        </select>
                                        <input x-show="isManualInput" type="text" x-model="form.machine_name"
                                            name="machine_name" placeholder="Ketik nama mesin..."
                                            class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            :disabled="!isManualInput" :required="isManualInput">
                                    </div>
                                    {{-- Nama Operator (FIELD BARU) --}}
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Operator
                                            <span class="text-red-500">*</span></label>
                                        <input type="text" name="operator_name" x-model="form.operator_name"
                                            placeholder="Nama operator di lokasi..."
                                            class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            required>
                                    </div>
                                </div>

                                {{-- Uraian Masalah (Judul & Detail digabung secara visual) --}}
                                <div class="border border-slate-300 rounded-md p-3 bg-slate-50">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Uraian Masalah /
                                        Kerusakan <span class="text-red-500">*</span></label>
                                    <input type="text" name="damaged_part" x-model="form.damaged_part"
                                        placeholder="Judul singkat masalah..."
                                        class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm mb-2 font-semibold"
                                        required>
                                    <textarea name="kerusakan_detail" x-model="form.kerusakan_detail" rows="3"
                                        placeholder="Jelaskan detail permasalahan secara rinci..."
                                        class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        required></textarea>
                                    {{-- Hidden field kerusakan untuk kompatibilitas backend lama --}}
                                    <input type="hidden" name="kerusakan" x-bind:value="form.damaged_part">
                                </div>

                                {{-- Target Penyelesaian (FIELD BARU) --}}
                                <div class="flex gap-4 items-end bg-yellow-50 p-3 rounded-md border border-yellow-200">
                                    <div class="flex-grow">
                                        <label class="block text-sm font-bold text-yellow-800 mb-1">Target
                                            Penyelesaian</label>
                                        <input type="date" name="target_date" x-model="form.target_date"
                                            class="w-full rounded-md border-yellow-400 text-slate-900 shadow-sm focus:border-yellow-500 focus:ring-yellow-500 sm:text-sm">
                                    </div>
                                    <div class="w-24 flex-shrink-0">
                                        <input type="time" name="target_time" x-model="form.target_time"
                                            class="w-full rounded-md border-yellow-400 text-slate-900 shadow-sm focus:border-yellow-500 focus:ring-yellow-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>

                            {{-- KOLOM KANAN: Pelaksanaan & Tindakan --}}
                            <div class="space-y-4 h-full flex flex-col">
                                <div class="bg-blue-50 p-3 rounded-t-lg border-b border-blue-100">
                                    <h4 class="font-bold text-blue-800 text-sm uppercase">B. Pelaksanaan & Tindakan
                                    </h4>
                                </div>

                                {{-- Pelaksana / Teknisi (Checkbox List) --}}
                                <div class="flex-grow">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Pelaksana (Tim
                                        Engineer) <span class="text-red-500">*</span></label>
                                    <div
                                        class="border border-slate-300 rounded-md p-2 h-48 overflow-y-auto bg-white grid grid-cols-2 gap-2">
                                        @foreach ($technicians as $tech)
                                            <label
                                                class="inline-flex items-center space-x-2 cursor-pointer hover:bg-slate-50 p-1 rounded">
                                                <input type="checkbox" value="{{ $tech->name }}"
                                                    :checked="form.engineer_tech.includes('{{ $tech->name }}')"
                                                    @change="toggleEngineer('{{ $tech->name }}')"
                                                    class="rounded text-blue-600 focus:ring-blue-500 border-slate-300">
                                                <span class="text-sm text-slate-700 truncate"
                                                    title="{{ $tech->name }}">{{ $tech->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <p class="text-xs mt-1"
                                        :class="form.engineer_tech.length == 0 ? 'text-red-500' : 'text-slate-500'">
                                        <span x-show="form.engineer_tech.length == 0">Wajib pilih minimal 1
                                            pelaksana.</span>
                                        <span x-show="form.engineer_tech.length > 0">Terpilih: <span
                                                x-text="form.engineer_tech.length"></span> orang</span>
                                    </p>
                                    {{-- Hidden inputs untuk array engineer_tech --}}
                                    <template x-for="name in form.engineer_tech">
                                        <input type="hidden" name="engineer_tech[]" :value="name">
                                    </template>
                                </div>

                                {{-- Tindakan Perbaikan (FIELD BARU) --}}
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1">Tindakan Perbaikan
                                        (Rencana/Aktual)</label>
                                    <textarea name="action_taken" x-model="form.action_taken" rows="2"
                                        placeholder="Deskripsikan tindakan perbaikan..."
                                        class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                                </div>

                                {{-- Spare Part (FIELD BARU) --}}
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1">Spare Part yang
                                        Digunakan</label>
                                    <textarea name="spare_parts" x-model="form.spare_parts" rows="2" placeholder="List spare part jika ada..."
                                        class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-slate-50"></textarea>
                                </div>

                                {{-- Upload Foto & Prioritas (Tambahan agar lengkap) --}}
                                <div class="grid grid-cols-2 gap-4 pt-2 border-t border-slate-200">
                                    <div>
                                        <label
                                            class="block text-sm font-semibold text-slate-700 mb-1">Prioritas</label>
                                        <select name="priority" x-model="form.priority"
                                            class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-blue-500 sm:text-sm">
                                            <option value="low">Low</option>
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                            <option value="critical">Critical</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">Upload Foto /
                                            Lampiran</label>
                                        <input type="file" name="photo"
                                            class="block w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Footer/Submit Section --}}
                        <div
                            class="mt-8 pt-4 border-t-2 border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-4">
                            <div class="text-xs text-slate-500 italic">
                                *Pastikan semua field bertanda bintang diisi sebelum menyimpan.
                            </div>
                            <div class="flex gap-3 w-full sm:w-auto">
                                <button type="button" @click="showSpkModal = false"
                                    class="w-1/2 sm:w-auto inline-flex justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none transition">
                                    Batal
                                </button>
                                {{-- Tombol Submit Langsung (Tanpa Konfirmasi Tambahan) --}}
                                <button type="submit"
                                    class="w-1/2 sm:w-auto inline-flex justify-center rounded-md border border-transparent bg-blue-600 px-6 py-2 text-sm font-bold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    SIMPAN SPK
                                </button>
                            </div>
                        </div>
                    </div> {{-- End Padding 6 --}}
                </form>
                {{-- FORM END --}}
            </div>
        </div>
    </div>
</template>
