{{-- MODAL ACCEPT GA (VALIDASI, DETAIL & KLASIFIKASI) --}}
<div x-data="{
    showAcceptModal: false,
    acceptId: null,

    // Variabel Detail Tiket
    ticketDetail: {
        num: '',
        requester: '',
        dept: '',
        plantName: '', // <-- Kita ubah variabelnya biar jelas
        desc: ''
    },

    // Data Form Input
    formData: {
        category: 'RINGAN',
        parameter_permintaan: '',
        target_completion_date: ''
    },

    // Fungsi Buka Modal
    openAcceptModal(ticket) {
        this.acceptId = ticket.id;

        // 1. ISI DETAIL TIKET
        this.ticketDetail.num = ticket.ticket_num;
        this.ticketDetail.requester = ticket.requester_name;
        this.ticketDetail.dept = ticket.requester_department;

        // --- PERBAIKAN: AMBIL NAMA PLANT ---
        // Cek apakah ada relasi plant_info (dari with: plantInfo), jika ada ambil namenya.
        // Jika tidak, ambil raw datanya (fallback).
        if (ticket.plant_info && ticket.plant_info.name) {
            this.ticketDetail.plantName = ticket.plant_info.name;
        } else {
            this.ticketDetail.plantName = ticket.plant; // Fallback jika raw data sudah berupa nama
        }

        this.ticketDetail.desc = ticket.description;

        // 2. ISI FORM INPUT
        this.formData.category = ticket.category || 'RINGAN';
        this.formData.parameter_permintaan = ticket.parameter_permintaan || '';

        // Default target: Hari ini + 3 hari
        let date = new Date();
        date.setDate(date.getDate() + 3);
        this.formData.target_completion_date = ticket.target_completion_date || date.toISOString().split('T')[0];

        this.showAcceptModal = true;
    }
}" @open-accept-modal.window="openAcceptModal($event.detail)">

    <template x-teleport="body">
        <div x-show="showAcceptModal" style="display: none;" class="fixed inset-0 z-[60] overflow-y-auto">
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity"
                @click="showAcceptModal = false"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                {{-- Container Modal --}}
                <div
                    class="relative w-full max-w-lg bg-white rounded-xl shadow-2xl overflow-hidden transform transition-all">

                    {{-- Header Biru --}}
                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                        <h3 class="text-lg font-black text-slate-800 uppercase">Validasi Tiket</h3>
                        <button @click="showAcceptModal = false" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="p-6">

                        {{-- === BAGIAN 1: DETAIL TIKET (READONLY) === --}}
                        <div class="bg-blue-50/50 border border-blue-100 rounded-lg p-4 mb-6">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="text-xs font-bold text-blue-600 bg-blue-100 px-2 py-0.5 rounded"
                                        x-text="ticketDetail.num"></span>
                                    <h4 class="text-sm font-bold text-slate-800 mt-1" x-text="ticketDetail.requester">
                                    </h4>
                                    {{-- TAMPILKAN NAMA PLANT DI SINI --}}
                                    <p class="text-[10px] text-slate-500 uppercase tracking-wide">
                                        <span x-text="ticketDetail.dept"></span> • <span
                                            class="font-bold text-slate-700" x-text="ticketDetail.plantName"></span>
                                    </p>
                                </div>
                            </div>

                            {{-- Deskripsi --}}
                            <div class="mt-2 pt-2 border-t border-blue-100">
                                <p class="text-xs text-slate-600 italic leading-relaxed max-h-24 overflow-y-auto">
                                    "<span x-text="ticketDetail.desc"></span>"
                                </p>
                            </div>
                        </div>

                        {{-- === BAGIAN 2: FORM KLASIFIKASI GA === --}}
                        <form :action="'/ga/process/' + acceptId" method="POST">
                            @csrf

                            <input type="hidden" name="action" value="approve">


                            {{-- Footer Buttons --}}
                            <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                                <button type="button" @click="showAcceptModal = false"
                                    class="px-5 py-2.5 bg-white border border-slate-300 text-slate-600 font-bold rounded-lg uppercase text-xs hover:bg-slate-50 transition-colors">
                                    Batal
                                </button>
                                <button type="submit"
                                    class="px-5 py-2.5 bg-emerald-500 text-white font-bold rounded-lg uppercase text-xs hover:bg-emerald-600 shadow-md transition-all transform active:scale-95 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    APPROVE
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
