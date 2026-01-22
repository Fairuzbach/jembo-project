{{-- GUNAKAN TELEPORT AGAR MODAL PINDAH KE BODY (PALING DEPAN) --}}
<template x-teleport="body">

    <div x-show="showDetailModal" x-cloak style="display: none;" class="fixed inset-0 z-[9999] overflow-y-auto">

        {{-- 1. BACKDROP (Layar Gelap) --}}
        <div class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm transition-opacity"></div>

        {{-- 2. WRAPPER UTAMA --}}
        <div class="flex min-h-full items-center justify-center p-4" @click="showDetailModal = false">

            {{-- 3. KARTU MODAL --}}
            <div class="relative w-full max-w-3xl bg-white rounded-xl shadow-2xl border-t-8 border-yellow-400 overflow-hidden"
                @click.stop>

                {{-- Header --}}
                <div class="bg-slate-50 px-8 py-5 flex justify-between items-center border-b border-slate-200">
                    <div>
                        <h3 class="text-xl font-black text-slate-800 uppercase tracking-wider">Detail Tiket</h3>
                        <p class="text-xs text-slate-500 font-bold mt-1">Informasi lengkap permintaan pekerjaan</p>
                    </div>
                    <button @click="showDetailModal = false"
                        class="bg-white rounded-full p-2 hover:bg-red-50 text-slate-400 hover:text-red-500 transition-all shadow-sm border border-slate-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                    </button>
                </div>

                {{-- Content --}}
                <div class="p-8 max-h-[80vh] overflow-y-auto custom-scrollbar">
                    <template x-if="ticket">
                        <div class="space-y-8">
                            {{-- 1. HEADER: Nomor Tiket & Status --}}
                            <div class="flex justify-between items-end border-b-2 pb-6">
                                <div>
                                    <span class="text-xs font-bold text-slate-400 uppercase mb-1 block">NOMOR
                                        TIKET</span>
                                    <p class="text-4xl font-black text-slate-900 font-mono" x-text="ticket.ticket_num">
                                    </p>
                                </div>
                                <div>
                                    <span class="px-5 py-2 font-black rounded-lg uppercase text-sm border shadow-sm"
                                        :class="{
                                            'bg-emerald-100 text-emerald-800 border-emerald-200': ticket
                                                .status === 'completed',
                                            'bg-blue-100 text-blue-800 border-blue-200': ticket
                                                .status === 'in_progress',
                                            'bg-purple-100 text-purple-800 border-purple-200': ticket
                                                .status === 'pending',
                                            'bg-orange-100 text-orange-800 border-orange-200': ticket
                                                .status === 'waiting_spv',
                                            'bg-rose-100 text-rose-800 border-rose-200': ticket
                                                .status === 'cancelled' || ticket.status === 'rejected',
                                            'bg-slate-100 text-slate-800 border-slate-200': !['completed',
                                                'in_progress', 'pending', 'waiting_spv', 'cancelled', 'rejected'
                                            ].includes(ticket.status)
                                        }"
                                        x-text="ticket.status ? ticket.status.replace('_', ' ') : '-'"></span>
                                </div>
                            </div>

                            {{-- 2. GRID INFORMASI UTAMA --}}
                            <div class="grid grid-cols-2 gap-6">
                                {{-- Baris 1: Orang --}}
                                <div>
                                    <span class="text-xs font-bold text-slate-400 uppercase block mb-1">Nama
                                        Pelapor</span>
                                    <p class="font-bold text-slate-800" x-text="ticket.requester_name"></p>
                                </div>

                                {{-- Baris 2: Lokasi & Tujuan --}}
                                <div>
                                    <span class="text-xs font-bold text-slate-400 uppercase block mb-1">Lokasi
                                        Plant</span>
                                    <p class="font-bold text-slate-800"
                                        x-text="ticket.plant_info ? ticket.plant_info.name : (ticket.plant || '-')"></p>
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-slate-400 uppercase block mb-1">Department
                                        Pelapor</span>
                                    <p class="font-bold text-blue-600" x-text="ticket.department || '-'"></p>
                                </div>

                                {{-- Baris 3: Teknis & Kategori --}}
                                <div>
                                    <span class="text-xs font-bold text-slate-400 uppercase block mb-1">Approved
                                        By</span>
                                    <p class="font-bold text-slate-800">
                                        {{-- Tampilkan: Nama PIC (Divisi PIC) --}}
                                        <span
                                            x-text="ticket.processed_by_name ? (ticket.processed_by_name + (ticket.approver_divisi ? ' (' + ticket.approver_divisi + ')' : '')) : '-'"></span>
                                    </p>
                                </div>

                                {{-- Baris 4: Tanggal --}}
                                <div x-show="ticket.target_completion_date">
                                    <span class="text-xs font-bold text-slate-400 uppercase block mb-1">Target
                                        Selesai</span>
                                    <p class="font-bold text-slate-800"
                                        x-text="new Date(ticket.target_completion_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })">
                                    </p>
                                </div>
                                <div x-show="ticket.actual_completion_date">
                                    <span class="text-xs font-bold text-slate-400 uppercase block mb-1">Selesai
                                        Pada</span>
                                    <p class="font-bold text-emerald-600"
                                        x-text="new Date(ticket.actual_completion_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })">
                                    </p>
                                </div>
                            </div>

                            {{-- 3. DESKRIPSI --}}
                            <div>
                                <span class="text-xs font-bold text-slate-400 uppercase block mb-2">Deskripsi
                                    Permintaan</span>
                                <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-100 text-justify">
                                    <p class="text-slate-700 leading-relaxed" x-text="ticket.description"></p>
                                </div>
                            </div>

                            {{-- 4. CATATAN --}}
                            <div x-show="ticket.completion_note">
                                <span class="text-xs font-bold text-emerald-600 uppercase block mb-2">Catatan
                                    Penyelesaian</span>
                                <div class="bg-emerald-50 p-4 rounded-lg border border-emerald-100">
                                    <p class="text-emerald-800" x-text="ticket.completion_note"></p>
                                </div>
                            </div>

                            <div x-show="ticket.cancellation_note">
                                <span class="text-xs font-bold text-red-500 uppercase block mb-2">Alasan
                                    Pembatalan</span>
                                <div class="bg-red-50 p-4 rounded-lg border border-red-100">
                                    <p class="text-red-700 font-medium" x-text="ticket.cancellation_note"></p>
                                </div>
                            </div>

                            <div x-show="ticket.rejection_reason">
                                <span class="text-xs font-bold text-red-500 uppercase block mb-2">Alasan
                                    Penolakan</span>
                                <div class="bg-red-50 p-4 rounded-lg border border-red-100">
                                    <p class="text-red-700 font-medium" x-text="ticket.rejection_reason"></p>
                                </div>
                            </div>

                            {{-- 5. AREA FOTO --}}
                            <div class="grid grid-cols-2 gap-4 pt-2"
                                x-show="ticket.photo_path || ticket.photo_completed_path">
                                <div x-show="ticket.photo_path">
                                    <span class="text-xs font-bold text-slate-400 uppercase block mb-2">Foto
                                        Laporan</span>
                                    <a :href="'/storage/' + ticket.photo_path" target="_blank">
                                        <img :src="'/storage/' + ticket.photo_path"
                                            class="w-full h-48 object-cover rounded-lg border border-slate-200 hover:opacity-90 transition-opacity cursor-pointer"
                                            alt="Foto Laporan">
                                    </a>
                                </div>

                                <div x-show="ticket.photo_completed_path">
                                    <span class="text-xs font-bold text-emerald-600 uppercase block mb-2">Bukti
                                        Penyelesaian</span>
                                    <a :href="'/storage/' + ticket.photo_completed_path" target="_blank">
                                        <img :src="'/storage/' + ticket.photo_completed_path"
                                            class="w-full h-48 object-cover rounded-lg border-2 border-emerald-400 hover:opacity-90 transition-opacity cursor-pointer"
                                            alt="Foto Selesai">
                                    </a>
                                </div>
                            </div>
                            {{-- 6. RIWAYAT AKTIVITAS (HISTORY) --}}
                            <div class="border-t border-slate-100 pt-6"
                                x-show="ticket.histories && ticket.histories.length > 0">
                                <span class="text-xs font-bold text-slate-400 uppercase block mb-4">Riwayat
                                    Aktivitas</span>

                                <div class="relative pl-2 border-l-2 border-slate-200 space-y-6 ml-1">
                                    <template x-for="history in ticket.histories" :key="history.id">
                                        <div class="relative pl-4">
                                            {{-- Dot Indicator --}}
                                            <div
                                                class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full bg-slate-50 border-2 border-slate-300">
                                            </div>

                                            {{-- Isi History --}}
                                            <div>
                                                <div
                                                    class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 mb-1">
                                                    <span class="text-sm font-bold text-slate-800"
                                                        x-text="history.user ? history.user.name : 'System'"></span>
                                                    <span
                                                        class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-500 border border-slate-200"
                                                        x-text="history.action"></span>
                                                    <span class="text-[10px] text-slate-400"
                                                        x-text="new Date(history.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })"></span>
                                                </div>
                                                <p class="text-xs text-slate-600 bg-slate-50 p-2 rounded border border-slate-100 inline-block w-full"
                                                    x-text="history.description"></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            {{-- 6. FOOTER ACTION --}}
                            <div class="flex justify-end pt-6 border-t gap-3">
                                <button @click="showDetailModal = false"
                                    class="px-6 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-lg">
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

</template>
