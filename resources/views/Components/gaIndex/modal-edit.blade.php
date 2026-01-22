{{-- MODAL EDIT / UPDATE WORK ORDER --}}
<div x-show="showEditModal" x-cloak style="display: none;" class="fixed inset-0 z-[60] overflow-y-auto">

    {{-- 1. BACKDROP (Visual Gelap) --}}
    <div class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm transition-opacity" @click="showEditModal = false"></div>

    {{-- 2. WRAPPER UTAMA --}}
    <div class="flex min-h-full items-center justify-center p-4">

        {{-- 3. KARTU MODAL --}}
        <div class="relative w-full max-w-2xl bg-white rounded-xl shadow-2xl overflow-hidden" @click.stop>

            {{-- Header --}}
            <div class="bg-blue-600 px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-black text-white uppercase tracking-wider">Update Pekerjaan</h3>
                <button @click="showEditModal = false" class="text-white hover:text-blue-200 transition-colors"
                    type="button">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            {{-- Form Update --}}
            {{-- Action URL dinamis berdasarkan ID tiket yang diedit --}}
            <form :action="'/ga/update-status/' + editForm.id" method="POST" enctype="multipart/form-data"
                class="p-6">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    {{-- Info Tiket (Read Only) --}}
                    <div class="flex justify-between items-center border-b pb-2 mb-4">
                        <div class="font-mono font-black text-xl text-slate-800" x-text="editForm.ticket_num"></div>
                        <span class="text-xs font-bold text-slate-400 uppercase">Update Data</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        {{-- 1. Status --}}
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
                            <select name="status" x-model="editForm.status"
                                class="w-full border-slate-300 rounded-lg focus:ring-blue-500 text-sm font-bold">
                                <option value="pending">PENDING</option>
                                <option value="in_progress">IN PROGRESS</option>
                                <option value="completed">COMPLETED</option>
                                <option value="cancelled">CANCELLED</option>
                            </select>
                        </div>

                        {{-- 2. Department Tujuan --}}
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Department</label>
                            <select name="department" x-model="editForm.department"
                                class="w-full border-slate-300 rounded-lg focus:ring-blue-500 text-sm">
                                <option value="Low Voltage">Low Voltage</option>
                                <option value="Medium Voltage">Medium Voltage</option>
                                <option value="IT">IT</option>
                                <option value="FH">FH</option>
                                <option value="PE">PE</option>
                                <option value="MT">MT</option>
                                <option value="GA">GA</option>
                                <option value="FO">FO</option>
                                <option value="SS">SS</option>
                                <option value="SC">SC</option>
                                <option value="QR">QR</option>
                                <option value="FA">FA</option>
                                <option value="HC">HC</option>
                                <option value="Sales">Sales</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Planning">Planning</option>
                            </select>
                        </div>

                        {{-- 3. Bobot / Kategori --}}
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Bobot Pekerjaan</label>
                            <select name="category" x-model="editForm.category"
                                class="w-full border-slate-300 rounded-lg focus:ring-blue-500 text-sm">
                                <option value="LOW">RINGAN (Low)</option>
                                <option value="MEDIUM">SEDANG (Medium)</option>
                                <option value="HIGH">BERAT (High)</option>
                            </select>
                        </div>

                        {{-- 4. Tanggal Mulai --}}
                        {{-- 2. TANGGAL MULAI PENGERJAAN --}}
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">
                                Tanggal Mulai Pengerjaan
                            </label>
                            <input id="modal_start_date" type="text" name="start_date" x-model="editForm.start_date"
                                placeholder="Pilih Tanggal"
                                class="date-picker w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                    </div>

                    {{-- 5. PIC --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nama PIC / Teknisi</label>
                        <input type="text" name="processed_by_name" x-model="editForm.pic"
                            class="w-full border-slate-300 rounded-lg focus:ring-blue-500 text-sm" required>
                    </div>

                    {{-- 6. Target Selesai --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Revisi Target
                            Selesai</label>
                        <input type="text" name="target_date" x-model="editForm.target_date"
                            class="date-picker w-full border-slate-300 rounded-lg focus:ring-blue-500 text-sm"
                            placeholder="Pilih Target...">
                    </div>
                </div>

                {{-- 7. KONDISIONAL INPUT (COMPLETED) --}}
                <div x-show="editForm.status === 'completed'" x-transition class="pt-4 border-t mt-4 space-y-3">
                    <div class="bg-emerald-50 p-3 rounded-lg border border-emerald-100">
                        <h4 class="text-xs font-black text-emerald-800 uppercase mb-3 flex items-center gap-2">
                            <span class="w-2 h-2 bg-emerald-500 rounded-full"></span> Detail Penyelesaian
                        </h4>

                        {{-- 1. TANGGAL SELESAI AKTUAL --}}
                        <div class="mb-3">
                            <label class="block text-xs font-bold text-emerald-700 uppercase mb-1">
                                Tanggal Selesai Aktual
                            </label>
                            <input type="datetime-local" name="actual_completion_date"
                                x-model="editForm.actual_end_date"
                                class="w-full rounded-lg border-emerald-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm">
                        </div>

                        {{-- Foto Bukti --}}
                        <div class="mb-3">
                            <label class="block text-xs font-bold text-emerald-700 uppercase mb-1">
                                Foto Bukti Penyelesaian <span class="text-red-500">*</span>
                            </label>
                            <input type="file" name="completion_photo" accept="image/*"
                                class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-700 hover:file:bg-emerald-200">
                        </div>

                        {{-- Catatan Penyelesaian --}}
                        <div>
                            <label class="block text-xs font-bold text-emerald-700 uppercase mb-1">Catatan
                                Penyelesaian</label>
                            <textarea name="completion_note" x-model="editForm.completion_note" rows="2"
                                class="w-full border-emerald-300 rounded-lg focus:ring-emerald-500 text-sm placeholder-slate-400"
                                placeholder="Contoh: AC sudah dingin, freon ditambah..."></textarea>
                        </div>
                    </div>
                </div>

                {{-- 8. KONDISIONAL INPUT (CANCELLED) --}}
                <div x-show="editForm.status === 'cancelled'" x-transition class="pt-4 border-t mt-4">
                    <div class="bg-red-50 p-3 rounded-lg border border-red-100">
                        <label class="block text-xs font-bold text-red-600 uppercase mb-1">
                            Alasan Pembatalan <span class="text-red-500">*</span>
                        </label>
                        <textarea name="cancellation_note" x-model="editForm.cancellation_note" rows="2"
                            class="w-full border-red-300 rounded-lg focus:ring-red-500 text-sm placeholder-red-300"
                            placeholder="Jelaskan alasan kenapa tiket ini dibatalkan..."></textarea>
                    </div>
                </div>

                {{-- Tombol Aksi --}}
                <div class="mt-6 flex justify-end gap-3 pt-4 border-t">
                    <button type="button" @click="showEditModal = false"
                        class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-lg uppercase text-xs hover:bg-slate-200 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white font-bold rounded-lg uppercase text-xs hover:bg-blue-700 shadow-md transition-all active:scale-95">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
