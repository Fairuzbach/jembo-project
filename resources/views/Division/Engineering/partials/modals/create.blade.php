<template x-teleport="body">
    <div x-show="showCreateModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog"
        aria-modal="true">
        <div x-show="showCreateModal" x-transition.opacity
            class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" @click="showCreateModal = false"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div x-show="showCreateModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl">

                <div class="bg-white border-b border-slate-200 px-4 py-4 sm:px-6 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        Fill the Improvement Request Form
                    </h3>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-500 transition">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form x-ref="createForm" action="{{ route('eng.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="px-4 py-5 sm:p-6 space-y-6">
                        {{-- Row 1 --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Tanggal Lapor</label>
                                <input type="date" name="report_date" x-model="currentDate" readonly
                                    class="w-full rounded-md border-slate-300 bg-slate-100 text-slate-600 shadow-sm cursor-not-allowed font-bold">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Jam Lapor (WIB)</label>
                                <input type="text" name="report_time" x-model="currentTime" readonly
                                    class="w-full rounded-md border-slate-300 bg-slate-100 text-slate-600 shadow-sm cursor-not-allowed font-bold">
                            </div>
                        </div>

                        {{-- Row 2 --}}
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Metode Pengerjaan</label>
                            <div class="flex items-center gap-4">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" name="work_method_dummy" value="sendiri" x-model="work_method"
                                        class="form-radio text-blue-600 focus:ring-blue-500 border-gray-300">
                                    <span class="ml-2 text-sm text-slate-700">Dilakukan Sendiri</span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" name="work_method_dummy" value="bersama" x-model="work_method"
                                        class="form-radio text-blue-600 focus:ring-blue-500 border-gray-300">
                                    <span class="ml-2 text-sm text-slate-700">Bersama Tim (Max 5)</span>
                                </label>
                            </div>
                        </div>

                        <template x-if="work_method === 'sendiri'">
                            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                <p class="text-sm text-blue-800 font-medium">Engineer: <span
                                        x-text="currentUsername"></span></p>
                                <p class="text-xs text-blue-600 mt-1">Anda akan tercatat sebagai pelaksana tunggal
                                    pekerjaan ini.</p>
                                <input type="hidden" name="engineer_tech[]" :value="currentUsername">
                            </div>
                        </template>

                        <template x-if="work_method === 'bersama'">
                            <div class="mb-4">
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Pilih Anggota Tim (Termasuk Anda jika ikut) <span class="text-red-500">*</span>
                                </label>
                                <div
                                    class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto border p-2 rounded bg-slate-50">
                                    @foreach ($technicians as $tech)
                                        <label class="inline-flex items-center space-x-2 cursor-pointer">
                                            <input type="checkbox" value="{{ $tech->name }}"
                                                :checked="form.engineer_tech.includes('{{ $tech->name }}')"
                                                :disabled="form.engineer_tech.length >= 5 && !form.engineer_tech.includes(
                                                    '{{ $tech->name }}')"
                                                @change="toggleEngineer('{{ $tech->name }}')"
                                                class="rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                                            <span class="text-sm text-slate-700">{{ $tech->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <p class="text-xs mt-1"
                                    :class="form.engineer_tech.length == 0 ? 'text-red-500' : 'text-slate-500'">
                                    <span x-show="form.engineer_tech.length == 0">Wajib pilih minimal 1 orang.</span>
                                    <span x-show="form.engineer_tech.length > 0">Terpilih: <span
                                            x-text="form.engineer_tech.length"></span>/5</span>
                                </p>
                                <template x-for="name in form.engineer_tech">
                                    <input type="hidden" name="engineer_tech[]" :value="name">
                                </template>
                            </div>
                        </template>

                        {{-- Row 3 --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Plant</label>
                                <select name="plant" x-model="selectedPlant" @change="onPlantChange()"
                                    class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                                    <option value="">Pilih Plant</option>
                                    <template x-for="plant in allPlants" :key="plant.id">
                                        <option :value="plant.name" x-text="plant.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Mesin <span
                                        x-show="isManualInput && selectedPlant"
                                        class="text-xs text-indigo-500 ml-1">(Input Manual)</span></label>
                                <select x-show="!isManualInput" x-model="form.machine_name" name="machine_name"
                                    class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    :disabled="isManualInput" :required="!isManualInput">
                                    <option value="">Pilih Mesin...</option>
                                    <template x-for="mesin in machineOptions" :key="mesin.id">
                                        <option :value="mesin.name" x-text="mesin.name"></option>
                                    </template>
                                </select>
                                <input x-show="isManualInput" type="text" x-model="form.machine_name"
                                    name="machine_name" placeholder="Ketik nama mesin..."
                                    class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    :disabled="!isManualInput" :required="isManualInput">
                            </div>
                        </div>

                        {{-- Row 4 --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Judul</label>
                                <input type="text" name="damaged_part" x-model="form.damaged_part"
                                    placeholder="Contoh: Pengukuran hasil ketebalan lapisan timah.."
                                    class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-indigo-500"
                                    required>
                                <input type="hidden" name="kerusakan" x-bind:value="form.damaged_part">
                            </div>
                            <div class="mb-4">
                                <label for="improvement_parameters"
                                    class="block text-sm font-semibold text-slate-700 mb-1">Parameter
                                    Improvement</label>
                                <select name="improvement_parameters" x-model="form.improvement_parameters"
                                    class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-indigo-500"
                                    required>
                                    <option value="" disabled selected>-- Pilih Parameter --</option>
                                    @foreach ($improvementParameters as $param)
                                        <option value="{{ $param->name }}"
                                            {{ old('improvement_parameters') == $param->name ? 'selected' : '' }}>
                                            {{ $param->name }} ({{ $param->name }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1">Prioritas</label>
                                    <select name="priority" x-model="form.priority"
                                        class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-indigo-500">
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                        <option value="critical">Critical</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1">Uraian
                                        Improvement</label>
                                    <textarea name="kerusakan_detail" x-model="form.kerusakan_detail" rows="1" placeholder="Jelaskan..."
                                        class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-indigo-500" required></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1">Status Awal</label>
                                    <select name="initial_status" x-model="form.initial_status"
                                        class="w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 font-bold"
                                        :class="{ 'text-blue-600': form.initial_status === 'OPEN', 'text-amber-600': form
                                                .initial_status === 'WIP', 'text-emerald-600': form
                                                .initial_status === 'CLOSED' }">
                                        <option value="OPEN">OPEN</option>
                                        <option value="WIP">WIP (On Progress)</option>
                                        <option value="CLOSED">CLOSED (Selesai)</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Upload Foto
                                    (Opsional)</label>
                                <input type="file" name="photo" @change="handleFile"
                                    class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                            </div>
                        </div>
                        <div class="px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse items-center gap-3 rounded-b-lg">
                            <button type="button" @click="showConfirmModal = true"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:w-auto sm:text-sm transition-colors">Lihat
                                & Kirim</button>
                            <button type="button" @click="showCreateModal = false"
                                class="text-slate-400 hover:text-red-500 transition mr-auto sm:mr-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
