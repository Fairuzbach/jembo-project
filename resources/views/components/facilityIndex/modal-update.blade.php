<template x-teleport="body">
    <div x-show="showEditModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm transition-opacity" @click="showEditModal = false">
        </div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div
                class="relative w-full max-w-lg bg-white rounded-[2.5rem] shadow-2xl overflow-visible transform transition-all">

                {{-- Header --}}
                <div
                    class="bg-gradient-to-r from-[#1E3A5F] to-[#2d5285] px-8 py-7 flex justify-between items-center border-b border-slate-200/50">
                    <h3 class="text-white font-bold text-lg tracking-tight">Update Ticket Status</h3>
                    <button @click="showEditModal = false"
                        class="text-white/60 hover:text-white hover:bg-white/10 rounded-full p-2.5 transition duration-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form x-bind:action="'/fh/' + editForm.id + '/update-status'" method="POST" class="p-8 space-y-6">
                    @csrf
                    @method('PUT')

                    {{-- 1. DROPDOWN TEKNISI (MULTI SELECT) - Kode Anda sudah Benar --}}
                    <div x-data="{ openDropdown: false }">
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Pilih Teknisi <span class="text-xs font-normal text-slate-400">(Max 5)</span>
                        </label>
                        <div class="relative">
                            <button type="button" @click="openDropdown = !openDropdown"
                                class="w-full bg-white border border-slate-200 text-left rounded-xl px-4 py-3 text-sm font-medium text-slate-600 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 flex justify-between items-center">
                                <span
                                    x-text="editForm.selectedTechs.length > 0 ? editForm.selectedTechs.length + ' Technician(s) Selected' : '-- Select Technicians --'"></span>
                                <svg class="w-4 h-4 text-slate-400 transition-transform"
                                    :class="openDropdown ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            {{-- Dropdown List --}}
                            <div x-show="openDropdown" @click.away="openDropdown = false"
                                class="absolute z-50 mt-2 w-full bg-white border border-slate-100 rounded-xl shadow-xl max-h-60 overflow-y-auto custom-scrollbar p-2"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100" style="display: none;">
                                <template x-for="tech in techniciansData" :key="tech.id">
                                    <div @click="toggleTech(tech.id)"
                                        class="flex items-center justify-between px-3 py-2 rounded-lg cursor-pointer transition hover:bg-blue-50 group">
                                        <div class="flex items-center gap-3">
                                            <div class="w-5 h-5 rounded border flex items-center justify-center transition"
                                                :class="editForm.selectedTechs.includes(tech.id) ?
                                                    'bg-blue-500 border-blue-500 text-white' :
                                                    'border-slate-300 bg-white'">
                                                <svg x-show="editForm.selectedTechs.includes(tech.id)" class="w-3 h-3"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                            <span class="text-sm text-slate-600 font-medium group-hover:text-blue-700"
                                                x-text="tech.name"></span>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="techniciansData.length === 0"
                                    class="px-4 py-3 text-sm text-slate-400 text-center">No technicians available.</div>
                            </div>
                            <div x-show="['fh.admin', 'super.admin', 'super.fh.admin'].includes(currentUserRole)"
                                x-transition class="p-4 bg-orange-50 border border-orange-200 rounded-xl mb-6">
                                <label class="block text-sm font-bold text-orange-800 mb-2">
                                    Revisi Kategori Permintaan
                                    <span class="text-xs font-normal text-orange-600 block leading-tight mt-0.5">
                                        *Khusus Admin: Ubah jika user salah memilih kategori.
                                    </span>
                                </label>
                                <div class="relative">
                                    <select name="category" x-model="editForm.category"
                                        class="w-full appearance-none rounded-lg border-orange-300 text-sm py-2.5 px-4 bg-white focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition cursor-pointer text-slate-700 font-medium shadow-sm">
                                        <option value="">-- Pilih Kategori --</option>
                                        {{-- Sesuaikan value ini dengan option di Form Create Anda --}}
                                        <option value="Modifikasi Mesin">Modifikasi Mesin</option>
                                        <option value="Pemasangan Mesin">Pemasangan Mesin</option>
                                        <option value="Pembongkaran Mesin">Pembongkaran Mesin</option>
                                        <option value="Relokasi Mesin">Relokasi Mesin</option>
                                        <option value="Perbaikan">Perbaikan</option>
                                        <option value="Pembuatan Alat Baru">Pembuatan Alat Baru</option>
                                        <option value="Rakit Steel Drum">Rakit Steel Drum</option>
                                        <option value="Lain - Lain">Lain - Lain</option>
                                    </select>
                                    <div
                                        class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-orange-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tags Selected --}}
                        <div class="flex flex-wrap gap-2 mt-3" x-show="editForm.selectedTechs.length > 0">
                            <template x-for="id in editForm.selectedTechs" :key="id">
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-xs font-bold border border-blue-100 shadow-sm">
                                    <span x-text="getTechName(id)"></span>
                                    <button type="button" @click="toggleTech(id)"
                                        class="hover:text-red-500 hover:bg-blue-100 rounded-full p-0.5 transition">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                    <input type="hidden" name="facility_tech_ids[]" :value="id">
                                </span>
                            </template>
                        </div>
                    </div>

                    {{-- 2. STATUS --}}
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Status</label>
                        <div class="relative">
                            <select name="status" x-model="editForm.status"
                                class="w-full appearance-none rounded-xl border-slate-200 text-sm py-3 px-4 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition cursor-pointer">
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <div
                                class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- 3. START DATE (Muncul jika In Progress atau Completed) --}}
                    <div x-show="editForm.status == 'in_progress' || editForm.status == 'completed'"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0">
                        <label class="block text-sm font-bold text-slate-700 mb-2">Tanggal Mulai (Start Date)</label>
                        {{-- PERBAIKAN: Gunakan type="datetime-local" agar aman tanpa JS plugin tambahan --}}
                        <input type="datetime-local" name="start_date" x-model="editForm.start_date"
                            class="w-full rounded-xl border-slate-200 text-sm py-3 px-4 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition">
                    </div>

                    {{-- 4. COMPLETION FIELDS (BARU: Muncul HANYA jika Completed) --}}
                    <div x-show="editForm.status === 'completed'"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="space-y-6 pt-2 border-t border-slate-100">

                        {{-- Actual Completion Date --}}
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Tanggal Selesai (Actual
                                Completion)</label>
                            <input type="datetime-local" name="actual_completion_date"
                                x-model="editForm.actual_completion_date"
                                class="w-full rounded-xl border-slate-200 text-sm py-3 px-4 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition">
                        </div>

                        {{-- Completion Note --}}
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Catatan Penyelesaian</label>
                            <textarea name="completion_note" rows="3" x-model="editForm.completion_note"
                                placeholder="Catatan penyelesaian..."
                                class="w-full rounded-xl border-slate-200 text-sm py-3 px-4 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition resize-none"></textarea>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                            class="w-full py-3.5 bg-gradient-to-br from-[#1E3A5F] to-[#152a47] text-white font-bold rounded-xl hover:from-[#162c46] hover:to-[#0f1f33] shadow-lg hover:shadow-xl transition transform active:scale-[0.98]">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
