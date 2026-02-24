{{-- WRAPPER UTAMA ALPINE --}}
<div x-data="{
    showEditModal: false,
    showAcceptModal: false,

    // Variabel Detail
    ticketDetail: { id: '', num: '', requester: '', dept: '', plantName: '', desc: '' },

    // Form data
    editForm: {
        id: '',
        ticket_num: '',
        status: '',
        pic: '',
        department: '',
        category: 'RINGAN',
        parameter_permintaan: '',
        start_date: '',
        target_date: '',
        actual_end_date: '',
        completion_note: '',
        cancellation_note: ''
    },

    openEditModal(data) {
        this.fillData(data);
        this.showEditModal = true;
    },
    openAcceptModal(data) {
        this.fillData(data);
        if (!this.editForm.target_date) {
            let date = new Date();
            date.setDate(date.getDate() + 3);
            this.editForm.target_date = date.toISOString().split('T')[0];
        }
        this.showAcceptModal = true;
    },

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
        } else { this.editForm.actual_end_date = ''; }

        this.editForm.completion_note = data.completion_note || '';
        this.editForm.cancellation_note = data.cancellation_note || '';

        this.ticketDetail.num = data.ticket_num;
        this.ticketDetail.requester = data.requester_name;
        this.ticketDetail.dept = data.department;
        this.ticketDetail.plantName = data.plant_info ? data.plant_info.name : (data.plant || '-');
        this.ticketDetail.desc = data.description;
    }
}" @open-edit-modal.window="openEditModal($event.detail)"
    @open-accept-modal.window="openAcceptModal($event.detail)">

    {{-- --- MODAL EDIT / UPDATE STATUS --- --}}
    <template x-teleport="body">
        <div x-show="showEditModal" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto">
            <div class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm" @click="showEditModal = false"></div>

            {{-- Wrapper: P-4 di HP, Flex Center --}}
            <div class="flex min-h-full items-center justify-center p-4">

                {{-- CARD MODAL: 
                     - Mobile: w-full (Penuh)
                     - Desktop: max-w-3xl (LEBAR SEPERTI AWAL) 
                --}}
                <div class="relative w-full max-w-lg md:max-w-3xl bg-white rounded-xl shadow-2xl overflow-hidden"
                    @click.stop>

                    {{-- Header --}}
                    <div
                        class="bg-blue-600 px-4 md:px-8 py-4 flex justify-between items-center text-white sticky top-0 z-10">
                        <h3 class="text-sm md:text-xl font-black uppercase tracking-wide">Update Pekerjaan</h3>
                        <button @click="showEditModal = false" class="hover:text-blue-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form :action="'/ga/update-status/' + editForm.id" method="POST" enctype="multipart/form-data"
                        class="p-4 md:p-8 max-h-[85vh] overflow-y-auto"> {{-- Padding Besar di Desktop --}}
                        @csrf
                        @method('PUT')

                        <div class="space-y-4 md:space-y-6">
                            <div
                                class="flex flex-col md:flex-row md:justify-between md:items-center border-b pb-4 mb-2 gap-2">
                                <div>
                                    <div class="font-mono font-black text-xl md:text-2xl text-slate-800"
                                        x-text="editForm.ticket_num"></div>
                                    <div class="text-xs text-slate-500 mt-1 md:hidden" x-text="ticketDetail.plantName">
                                    </div>
                                </div>
                                <span
                                    class="hidden md:block text-sm font-bold text-slate-500 uppercase bg-slate-100 px-3 py-1 rounded border"
                                    x-text="ticketDetail.plantName"></span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-8">

                                <div class="space-y-4">
                                    <div>
                                        <label
                                            class="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
                                        <select name="status" x-model="editForm.status"
                                            class="w-full border-slate-300 rounded-lg text-sm font-bold h-11">
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
                                            class="w-full border-slate-300 rounded-lg text-sm h-10 md:h-11">
                                            <option value="">-- Pilih Department --</option>
                                            <option value="JEMBO ENERGINDO">JEMBO ENERGINDO</option>
                                            <option value="FH">FH</option>
                                            <option value="GENERAL AFFAIR">GENERAL AFFAIR</option>
                                            <option value="INFORMATION TECHNOLOGY">IT</option>
                                            <option value="LOW VOLTAGE">LOW VOLTAGE</option>
                                            <option value="MEDIUM VOLTAGE">MEDIUM VOLTAGE</option>
                                            <option value="FIBER OPTIC">FIBER OPTIC</option>
                                            <option value="SUPPLY CHAIN">SUPPLY CHAIN</option>
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

                                    {{-- Klasifikasi --}}
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Klasifikasi
                                            Jenis</label>
                                        <select name="parameter_permintaan" x-model="editForm.parameter_permintaan"
                                            required class="w-full border-slate-300 rounded-lg text-sm font-bold h-11">
                                            <option value="">-- Pilih Klasifikasi --</option>
                                            @php $modalCats = \App\Models\GeneralAffair\Category::where('status', 'active')->get(); @endphp
                                            @foreach ($modalCats as $cat)
                                                <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                {{-- KANAN --}}
                                <div class="space-y-4">
                                    {{-- Bobot --}}
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Bobot
                                            Pekerjaan</label>
                                        <select name="category" x-model="editForm.category"
                                            class="w-full border-slate-300 rounded-lg text-sm h-11">
                                            <option value="RINGAN">RINGAN</option>
                                            <option value="SEDANG">SEDANG</option>
                                            <option value="BERAT">BERAT</option>
                                        </select>
                                    </div>

                                    {{-- Target Selesai --}}
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Target
                                            Selesai</label>
                                        <input type="date" name="target_date" x-model="editForm.target_date"
                                            class="w-full border-slate-300 rounded-lg text-sm font-bold h-11">
                                    </div>

                                    {{-- PIC & Tgl Mulai (Grid Kecil di dalam kolom kanan) --}}
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">PIC /
                                                Teknisi</label>
                                            <input type="text" name="processed_by_name" x-model="editForm.pic"
                                                class="w-full border-slate-300 rounded-lg text-sm h-11" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Tgl
                                                Mulai</label>
                                            <input type="date" name="start_date" x-model="editForm.start_date"
                                                class="w-full border-slate-300 rounded-lg text-sm h-11">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- KONDISIONAL COMPLETED --}}
                        <div x-show="editForm.status === 'completed'" x-transition
                            class="pt-6 border-t mt-6 space-y-4 bg-emerald-50 p-6 rounded-lg border border-emerald-100">
                            <h4
                                class="font-bold text-emerald-800 uppercase text-sm border-b border-emerald-200 pb-2 mb-2">
                                Penyelesaian</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                <div>
                                    <label class="block text-xs font-bold text-emerald-700 uppercase mb-1">Tgl Selesai
                                        Aktual</label>
                                    <input type="datetime-local" name="actual_completion_date"
                                        x-model="editForm.actual_end_date"
                                        class="w-full rounded-lg border-emerald-300 text-sm h-11 bg-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-emerald-700 uppercase mb-1">Foto
                                        Bukti</label>
                                    <input type="file" accept="image/*, .pdf" name="completion_photo"
                                        class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-700 hover:file:bg-emerald-200">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-emerald-700 uppercase mb-1">Catatan
                                        Penyelesaian</label>
                                    <textarea name="completion_note" x-model="editForm.completion_note" rows="2"
                                        class="w-full border-emerald-300 rounded-lg text-sm p-3"></textarea>
                                </div>
                            </div>
                        </div>

                        {{-- KONDISIONAL CANCELLED --}}
                        <div x-show="editForm.status === 'cancelled'" x-transition
                            class="pt-6 border-t mt-6 bg-red-50 p-6 rounded-lg border border-red-100">
                            <label class="block text-xs font-bold text-red-600 uppercase mb-1">Alasan Pembatalan
                                *</label>
                            <textarea name="cancellation_note" x-model="editForm.cancellation_note" rows="3"
                                class="w-full border-red-300 rounded-lg text-sm p-3"></textarea>
                        </div>

                        {{-- Footer Buttons --}}
                        <div
                            class="mt-8 flex flex-col-reverse md:flex-row justify-end gap-3 pt-4 border-t bg-white sticky bottom-0">
                            <button type="button" @click="showEditModal = false"
                                class="w-full md:w-auto px-6 py-3 bg-slate-100 text-slate-600 font-bold rounded-lg text-sm uppercase transition-colors hover:bg-slate-200">
                                Batal
                            </button>
                            <button type="submit"
                                class="w-full md:w-auto px-8 py-3 bg-blue-600 text-white font-bold rounded-lg text-sm uppercase shadow-lg hover:bg-blue-700 transition-all hover:scale-105">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
