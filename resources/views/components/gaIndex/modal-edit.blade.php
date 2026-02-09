{{-- WRAPPER UTAMA ALPINE --}}
<div x-data="{
    showEditModal: false,
    showAcceptModal: false,

    // Variabel Detail untuk Header
    ticketDetail: {
        id: '',
        num: '',
        requester: '',
        dept: '',
        plantName: '',
        desc: ''
    },

    // Form data tunggal untuk semua aksi (Edit & Accept)
    editForm: {
        id: '',
        ticket_num: '',
        status: '',
        pic: '',
        department: '',
        category: 'LOW',
        parameter_permintaan: '',
        start_date: '',
        target_date: '',
        actual_end_date: '',
        completion_note: '',
        cancellation_note: ''
    },

    // Fungsi Buka Modal Edit (Update Status)
    openEditModal(data) {
        this.fillData(data);
        this.showEditModal = true;
    },

    // Fungsi Buka Modal Accept (Validasi Awal)
    openAcceptModal(data) {
        this.fillData(data);
        // Logika default target date (Hari ini + 3 hari) jika belum ada
        if (!this.editForm.target_date) {
            let date = new Date();
            date.setDate(date.getDate() + 3);
            this.editForm.target_date = date.toISOString().split('T')[0];
        }
        this.showAcceptModal = true;
    },

    // Sinkronisasi data ke variabel Alpine
    fillData(data) {
        this.editForm.id = data.id;
        this.editForm.ticket_num = data.ticket_num;
        this.editForm.status = data.status;
        this.editForm.pic = data.processed_by_name || '';
        let targetDept = data.department || data.requester_department || '';
        this.editForm.department = targetDept.toUpperCase();
        this.editForm.category = data.category || 'LOW';
        this.editForm.parameter_permintaan = data.parameter_permintaan || '';

        this.editForm.start_date = data.actual_start_date ? data.actual_start_date.split('T')[0] : '';
        this.editForm.target_date = data.target_completion_date ? data.target_completion_date.split('T')[0] : '';

        if (data.actual_completion_date) {
            let d = new Date(data.actual_completion_date);
            d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
            this.editForm.actual_end_date = d.toISOString().slice(0, 16);
        } else {
            this.editForm.actual_end_date = '';
        }

        this.editForm.completion_note = data.completion_note || '';
        this.editForm.cancellation_note = data.cancellation_note || '';

        // Detail Header
        this.ticketDetail.num = data.ticket_num;
        this.ticketDetail.requester = data.requester_name;
        this.ticketDetail.dept = data.department;
        this.ticketDetail.plantName = data.plant_info ? data.plant_info.name : (data.plant || '-');
        this.ticketDetail.desc = data.description;
    }
}" @open-edit-modal.window="openEditModal($event.detail)"
    @open-accept-modal.window="openAcceptModal($event.detail)">

    {{-- --- MODAL 1: UPDATE / EDIT STATUS (VERSI GABUNGAN) --- --}}
    <template x-teleport="body">
        <div x-show="showEditModal" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto">
            <div class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm" @click="showEditModal = false"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-xl shadow-2xl overflow-hidden" @click.stop>

                    {{-- Header --}}
                    <div class="bg-blue-600 px-6 py-4 flex justify-between items-center text-white">
                        <h3 class="text-lg font-black uppercase">Update Pekerjaan</h3>
                        <button @click="showEditModal = false" class="hover:text-blue-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form :action="'/ga/update-status/' + editForm.id" method="POST" enctype="multipart/form-data"
                        class="p-6">
                        @csrf
                        @method('PUT')

                        <div class="space-y-4">
                            {{-- Ticket Header Info --}}
                            <div class="flex justify-between items-center border-b pb-2">
                                <div class="font-mono font-black text-xl text-slate-800" x-text="editForm.ticket_num">
                                </div>
                                <span class="text-xs font-bold text-slate-400 uppercase"
                                    x-text="ticketDetail.plantName"></span>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                {{-- Status --}}
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
                                    <select name="status" x-model="editForm.status"
                                        class="w-full border-slate-300 rounded-lg text-sm font-bold">
                                        <option value="pending">PENDING</option>
                                        <option value="in_progress">IN PROGRESS</option>
                                        <option value="completed">COMPLETED</option>
                                        <option value="cancelled">CANCELLED</option>
                                    </select>
                                </div>
                                {{-- Department --}}
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase mb-1">Department</label>
                                    <select name="department" x-model="editForm.department"
                                        class="w-full border-slate-300 rounded-lg text-sm">
                                        <option value="">-- Pilih Department --</option>
                                        <option value="FACILITY">FACILITY</option>
                                        <option value="GENERAL AFFAIR">GENERAL AFFAIR</option>
                                        <option value="INFORMATION TECHNOLOGY">IT</option>
                                        <option value="LOW VOLTAGE">LOW VOLTAGE</option>
                                        <option value="MEDIUM VOLTAGE">MEDIUM VOLTAGE</option>
                                        <option value="FIBER OPTIC">FIBER OPTIC</option>
                                        <option value="PROCUREMENT">PROCUREMENT</option>
                                        <option value="QUALITY ASSURANCE">QUALITY ASSURANCE</option>
                                        <option value="MAINTENANCE">MAINTENANCE</option>
                                        <option value="SALES SUPPORT">SALES SUPPORT</option>
                                        <option value="PROCESS ENGINEERING">PROCESS ENGINEERING</option>
                                        <option value="PRODUCTION PLANNING">PRODUCTION PLANNING</option>
                                        <option value="FINANCE">FINANCE</option>
                                        <option value="ACCOUNTING">ACCOUNTING</option>
                                        <option value="MARKETING">MARKETING</option>
                                        <option value="HUMAN CAPITAL">HUMAN CAPITAL</option>
                                        <option value="SALES 1">SALES 1</option>
                                        <option value="SALES 2">SALES 2</option>

                                    </select>
                                </div>
                            </div>

                            {{-- KLASIFIKASI JENIS (Dipindahkan dari Modal Accept) --}}
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Klasifikasi
                                    Jenis</label>
                                <select name="parameter_permintaan" x-model="editForm.parameter_permintaan"
                                    class="w-full border-slate-300 rounded-lg text-sm font-bold">
                                    <option value="">-- Pilih Klasifikasi --</option>
                                    @php $modalCats = \App\Models\GeneralAffair\Category::where('status', 'active')->get(); @endphp
                                    @foreach ($modalCats as $cat)
                                        <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                {{-- Bobot --}}
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Bobot
                                        Pekerjaan</label>
                                    <select name="category" x-model="editForm.category"
                                        class="w-full border-slate-300 rounded-lg text-sm">
                                        <option value="LOW">RINGAN (Low)</option>
                                        <option value="MEDIUM">SEDANG (Medium)</option>
                                        <option value="HIGH">BERAT (High)</option>
                                    </select>
                                </div>
                                {{-- Target Selesai --}}
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Target
                                        Selesai</label>
                                    <input type="date" name="target_completion_date" x-model="editForm.target_date"
                                        class="w-full border-slate-300 rounded-lg text-sm font-bold">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                {{-- PIC --}}
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nama PIC /
                                        Teknisi</label>
                                    <input type="text" name="processed_by_name" x-model="editForm.pic"
                                        class="w-full border-slate-300 rounded-lg text-sm" required>
                                </div>
                                {{-- Tgl Mulai --}}
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Tgl Mulai
                                        Pengerjaan</label>
                                    <input type="date" name="start_date" x-model="editForm.start_date"
                                        class="w-full border-slate-300 rounded-lg text-sm">
                                </div>
                            </div>
                        </div>

                        {{-- KONDISIONAL COMPLETED --}}
                        <div x-show="editForm.status === 'completed'" x-transition
                            class="pt-4 border-t mt-4 space-y-3 bg-emerald-50 p-3 rounded-lg border border-emerald-100">
                            <div class="grid grid-cols-1 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-emerald-700 uppercase mb-1">Tgl Selesai
                                        Aktual</label>
                                    <input type="datetime-local" name="actual_completion_date"
                                        x-model="editForm.actual_end_date"
                                        class="w-full rounded-lg border-emerald-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-emerald-700 uppercase mb-1">Foto Bukti
                                        Penyelesaian</label>
                                    <input type="file" name="completion_photo"
                                        class="w-full text-xs file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-emerald-100 file:text-emerald-700">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-emerald-700 uppercase mb-1">Catatan
                                        Penyelesaian</label>
                                    <textarea name="completion_note" x-model="editForm.completion_note" rows="2"
                                        class="w-full border-emerald-300 rounded-lg text-sm"></textarea>
                                </div>
                            </div>
                        </div>

                        {{-- KONDISIONAL CANCELLED --}}
                        <div x-show="editForm.status === 'cancelled'" x-transition
                            class="pt-4 border-t mt-4 bg-red-50 p-3 rounded-lg border border-red-100">
                            <label class="block text-xs font-bold text-red-600 uppercase mb-1">Alasan Pembatalan
                                *</label>
                            <textarea name="cancellation_note" x-model="editForm.cancellation_note" rows="2"
                                class="w-full border-red-300 rounded-lg text-sm"></textarea>
                        </div>

                        <div class="mt-6 flex justify-end gap-3 pt-4 border-t">
                            <button type="button" @click="showEditModal = false"
                                class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-lg text-xs uppercase">Batal</button>
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white font-bold rounded-lg text-xs uppercase shadow-md hover:bg-blue-700 transition-all active:scale-95">Simpan
                                Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

    {{-- MODAL 2 (ACCEPT) SEKARANG BISA DIHAPUS JIKA TIDAK DIPERLUKAN LAGI --}}
</div>
