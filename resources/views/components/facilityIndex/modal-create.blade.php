@props(['listPlants'])
<template x-teleport="body">
    <div x-show="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm transition-opacity" @click="showCreateModal = false">
        </div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                class="relative w-full max-w-2xl bg-white rounded-[2.5rem] shadow-2xl overflow-hidden transform transition-all">
                <div
                    class="bg-gradient-to-r from-[#1E3A5F] to-[#2d5285] px-8 py-7 border-b border-slate-200/50 flex justify-between items-center sticky top-0 z-10">
                    <h3 class="text-white font-extrabold text-xl tracking-tight">Create New Ticket</h3>
                    <button @click="showCreateModal = false"
                        class="text-white/60 hover:text-white hover:bg-white/10 rounded-full p-2.5 transition duration-200"><svg
                            class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg></button>
                </div>
                <form action="{{ route('fh.store') }}" method="POST" enctype="multipart/form-data"
                    class="max-h-[75vh] overflow-y-auto custom-scrollbar">
                    @csrf
                    <div class="p-8 space-y-6">
                        {{-- GANTI INPUT NAMA MENJADI READONLY --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Requester
                                    Name</label>
                                <input type="text" value="{{ auth()->user()->name }}" readonly
                                    class="w-full rounded-xl border-slate-300 bg-slate-100 text-slate-500 cursor-not-allowed focus:ring-0">
                                {{-- Hidden Input untuk dikirim ke Controller --}}
                                <input type="hidden" name="requester_name" value="{{ auth()->user()->name }}">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Division</label>
                                <input type="text" value="{{ auth()->user()->divisi ?? '-' }}" readonly
                                    class="w-full rounded-xl border-slate-300 bg-slate-100 text-slate-500 cursor-not-allowed focus:ring-0">
                            </div>
                        </div>
                        <div
                            class="bg-blue-50/50 rounded-2xl p-4 border border-blue-100 grid grid-cols-2 gap-4 text-center">
                            <div>
                                <div class="text-[10px] font-bold text-blue-300 uppercase">Date</div>
                                <div class="font-bold text-[#1E3A5F] text-sm" x-text="currentDate"></div>
                                <input type="hidden" name="report_date" x-model="currentDateDB">
                            </div>
                            <div>
                                <div class="text-[10px] font-bold text-blue-300 uppercase">Time</div>
                                <div class="font-bold text-[#1E3A5F] text-sm" x-text="currentTime"></div>
                                <input type="hidden" name="report_time" x-model="currentTime">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Location / Plant
                                    <span class="text-rose-500">*</span></label>
                                <select name="plant_id" x-model="form.plant_id" @change="filterMachines()" required
                                    class="w-full rounded-xl border-slate-200 text-sm py-3 bg-slate-50/50">
                                    <option value="">Select Plant...</option>
                                    @foreach ($listPlants as $plant)
                                        <option value="{{ $plant->id }}">{{ $plant->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Category <span
                                        class="text-rose-500">*</span></label>
                                <select name="category" x-model="form.category" required
                                    class="w-full rounded-xl border-slate-200 text-sm py-3 bg-slate-50/50">
                                    <option value="">Select Category...</option>
                                    <option value="Modifikasi Mesin">Modifikasi Mesin</option>
                                    <option value="Pemasangan Mesin">Pemasangan Mesin</option>
                                    <option value="Pembongkaran Mesin">Pembongkaran Mesin</option>
                                    <option value="Relokasi Mesin">Relokasi Mesin</option>
                                    <option value="Perbaikan">Perbaikan</option>
                                    <option value="Pembuatan Alat Baru">Pembuatan Alat Baru</option>
                                    <option value="Rakit Steel Drum">Rakit Steel Drum</option>
                                    <option value="Lain - Lain">Lain - Lain</option>
                                </select>
                            </div>
                        </div>

                        {{-- LOGIKA KONDISIONAL MESIN --}}
                        {{-- 1. Jika Pemasangan Mesin -> Input Text Mesin Baru --}}
                        <div x-show="form.category == 'Pemasangan Mesin'" x-transition>
                            <label class="block text-sm font-bold text-blue-700 mb-2">Nama Mesin Baru <span
                                    class="text-rose-500">*</span></label>
                            <input type="text" name="new_machine_name" x-model="form.new_machine_name"
                                :required="form.category == 'Pemasangan Mesin'"
                                placeholder="Masukkan nama mesin baru..."
                                class="w-full rounded-xl border-blue-200 bg-blue-50/30 text-sm py-3 text-slate-600 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                            <p class="text-xs text-slate-400 mt-1 italic">Mesin ini akan didaftarkan di
                                Plant
                                yang dipilih.</p>
                        </div>

                        {{-- 2. Jika Kategori Lain -> Dropdown Pilih Mesin --}}
                        <div x-show="form.category != 'Pemasangan Mesin' && needsMachineSelect()" x-transition>
                            <label class="block text-sm font-bold text-blue-700 mb-2">Pilih Mesin <span
                                    class="text-rose-500">*</span></label>
                            <div class="relative">
                                <div x-show="!form.plant_id"
                                    class="absolute inset-0 bg-white/80 backdrop-blur-[1px] z-10 flex items-center justify-center rounded-xl border border-dashed border-slate-300">
                                    <span class="text-xs text-slate-400 font-medium italic">Pilih Plant
                                        Terlebih Dahulu</span>
                                </div>
                                <select name="machine_id" x-model="form.machine_id"
                                    :required="form.category != 'Pemasangan Mesin' && needsMachineSelect()"
                                    class="w-full rounded-xl border-blue-200 bg-blue-50/30 text-sm py-3">
                                    <option value="">-- Pilih Mesin --</option>
                                    <template x-for="machine in filteredMachines" :key="machine.id">
                                        <option :value="machine.id" x-text="machine.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>


                        <div><label class="block text-sm font-bold text-slate-700 mb-2">Request Target
                                Date</label><input type="text" name="target_completion_date"
                                placeholder="Select date..."
                                class="w-full rounded-xl border-slate-200 text-sm py-3 bg-slate-50/50"
                                x-init="flatpickr($el, { minDate: 'today', dateFormat: 'Y-m-d' })"></div>
                        <div><label class="block text-sm font-bold text-slate-700 mb-2">Description <span
                                    class="text-rose-500">*</span></label>
                            <textarea name="description" rows="3" required
                                class="w-full rounded-xl border-slate-200 text-sm py-3 bg-slate-50/50"></textarea>
                        </div>
                        <div><label class="block text-sm font-bold text-slate-700 mb-2">Attachment</label><input
                                name="photo" type="file"
                                class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                        </div>
                    </div>
                    <div
                        class="bg-gradient-to-r from-slate-50 to-slate-100 px-8 py-6 flex justify-end gap-3 border-t border-slate-200">
                        <button type="button" @click="showCreateModal = false"
                            class="px-6 py-3 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-200 border border-slate-200 transition duration-200 hover:text-slate-800">Cancel</button><button
                            type="submit"
                            class="px-8 py-3 bg-gradient-to-br from-[#1E3A5F] to-[#152a47] text-white rounded-xl text-sm font-bold shadow-lg hover:shadow-xl hover:from-[#162c46] hover:to-[#0f1f33] transition-all duration-300 hover:scale-105 active:scale-95">Create
                            Ticket</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
