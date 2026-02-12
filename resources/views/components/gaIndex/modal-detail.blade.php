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
                                                .status === 'waiting_approval',
                                            'bg-rose-100 text-rose-800 border-rose-200': ticket
                                                .status === 'cancelled' || ticket.status === 'rejected',
                                            'bg-slate-100 text-slate-800 border-slate-200': !['completed',
                                                'in_progress', 'pending', 'waiting_approval', 'cancelled',
                                                'rejected'
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
                                    <span class="text-xs font-bold text-slate-400 uppercase block mb-1">PIC /
                                        Teknisi</span>
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
                            <div class="grid grid-cols-2 gap-4 pt-2">

                                {{-- 1. FOTO LAPORAN (AWAL) --}}
                                <template x-if="ticket.photo_path">
                                    <div>
                                        <span class="text-xs font-bold text-slate-400 uppercase block mb-2">Foto
                                            Laporan</span>

                                        {{-- Cek apakah PDF --}}
                                        <template x-if="ticket.photo_path.toLowerCase().endsWith('.pdf')">
                                            <a :href="'/storage/' + ticket.photo_path" target="_blank"
                                                class="flex items-center justify-center h-48 border border-slate-200 rounded-lg bg-slate-50 hover:bg-slate-100 transition-colors p-4 text-center cursor-pointer group">
                                                <div class="space-y-2">
                                                    <svg class="w-10 h-10 text-slate-400 mx-auto group-hover:text-slate-600"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                        </path>
                                                    </svg>
                                                    <span class="text-xs font-bold text-slate-500 block">Lihat Dokumen
                                                        PDF</span>
                                                </div>
                                            </a>
                                        </template>

                                        {{-- Cek apakah Gambar (Normal) --}}
                                        <template x-if="!ticket.photo_path.toLowerCase().endsWith('.pdf')">
                                            <a :href="'/storage/' + ticket.photo_path" target="_blank">
                                                <img :src="'/storage/' + ticket.photo_path"
                                                    class="w-full h-48 object-cover rounded-lg border border-slate-200 hover:opacity-90 transition-opacity cursor-pointer"
                                                    alt="Foto Laporan">
                                            </a>
                                        </template>
                                    </div>
                                </template>

                                {{-- 2. FOTO PENYELESAIAN (AKHIR) --}}

                                {{-- GUNAKAN INI HANYA JIKA MODAL ADA DI LUAR LOOP --}}
                                <div x-data>
                                    {{-- Ambil path langsung dari object ticket di AlpineJS --}}
                                    <template x-if="ticket.photo_completed_path || ticket.photo_completion_path">
                                        <div>
                                            <span class="text-xs font-bold text-emerald-600 uppercase block mb-2">Bukti
                                                Penyelesaian</span>

                                            {{-- Simpan path ke variabel lokal biar pendek --}}
                                            <div x-data="{ get path() { return ticket.photo_completed_path || ticket.photo_completion_path } }">

                                                {{-- PDF --}}
                                                <template x-if="path.toLowerCase().endsWith('.pdf')">
                                                    <a :href="'/storage/' + path" target="_blank" class="...">
                                                        ... (Icon PDF) ...
                                                    </a>
                                                </template>

                                                {{-- Image --}}
                                                <template x-if="!path.toLowerCase().endsWith('.pdf')">
                                                    <a :href="'/storage/' + path" target="_blank">
                                                        {{-- PENTING: Gunakan :src agar dinamis --}}
                                                        <img :src="'/storage/' + path"
                                                            class="w-full h-48 object-cover rounded-lg border-2 border-emerald-400"
                                                            alt="Foto Selesai">
                                                    </a>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            {{-- 6. RIWAYAT AKTIVITAS (HISTORY) --}}
                            <div class="border-t border-slate-200 pt-8 mt-8" x-show="ticket?.histories?.length > 0"
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 transform translate-y-4"
                                x-transition:enter-end="opacity-100 transform translate-y-0">

                                <div class="flex items-center gap-3 mb-6">
                                    <div
                                        class="flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 shadow-lg shadow-indigo-500/30">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide">
                                        Riwayat Aktivitas
                                    </h3>
                                </div>

                                <div class="relative border-l-2 border-slate-200 space-y-6 ml-3 pb-2">
                                    <template x-for="(history, index) in ticket.histories" :key="history.id">
                                        <div class="relative ml-6 group"
                                            x-transition:enter="transition ease-out duration-300 delay-[calc(var(--index)*50ms)]"
                                            x-transition:enter-start="opacity-0 transform translate-x-4"
                                            x-transition:enter-end="opacity-100 transform translate-x-0"
                                            :style="`--index: ${index}`">

                                            {{-- Animated Dot with Pulse Effect --}}
                                            <div class="absolute -left-[33px] top-1">
                                                <div class="relative">
                                                    {{-- Outer pulse ring --}}
                                                    <div
                                                        class="absolute inset-0 w-4 h-4 rounded-full bg-indigo-400 opacity-0 group-hover:opacity-100 group-hover:animate-ping">
                                                    </div>

                                                    {{-- Main dot with gradient --}}
                                                    <div
                                                        class="relative w-4 h-4 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 border-2 border-white shadow-md group-hover:scale-110 transition-transform duration-200">
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Content Card --}}
                                            <div
                                                class="bg-white rounded-lg border border-slate-200 p-4 hover:shadow-md hover:border-indigo-200 transition-all duration-200 group-hover:-translate-y-0.5">

                                                {{-- Header --}}
                                                <div class="flex flex-wrap items-center gap-2 mb-3">
                                                    {{-- User Avatar & Name --}}
                                                    <div class="flex items-center gap-2">
                                                        <div
                                                            class="w-6 h-6 rounded-full bg-gradient-to-br from-slate-200 to-slate-300 flex items-center justify-center">
                                                            <span class="text-[10px] font-bold text-slate-600"
                                                                x-text="(history.user?.name ?? 'S')[0].toUpperCase()"></span>
                                                        </div>
                                                        <span class="text-sm font-semibold text-slate-800"
                                                            x-text="history.user?.name ?? 'System'"></span>
                                                    </div>

                                                    {{-- Action Badge with Dynamic Colors --}}
                                                    <span
                                                        class="text-[10px] font-bold px-2.5 py-1 rounded-full border shadow-sm"
                                                        :class="{
                                                            'bg-emerald-50 text-emerald-700 border-emerald-200': history
                                                                .action.toLowerCase().includes('dibuat') || history
                                                                .action.toLowerCase().includes('selesai'),
                                                            'bg-blue-50 text-blue-700 border-blue-200': history.action
                                                                .toLowerCase().includes('diperbarui') || history.action
                                                                .toLowerCase().includes('update'),
                                                            'bg-amber-50 text-amber-700 border-amber-200': history
                                                                .action.toLowerCase().includes('ditugaskan') || history
                                                                .action.toLowerCase().includes('assign'),
                                                            'bg-slate-50 text-slate-600 border-slate-200': !history
                                                                .action.toLowerCase().includes('dibuat') && !history
                                                                .action.toLowerCase().includes('diperbarui') && !history
                                                                .action.toLowerCase().includes('ditugaskan')
                                                        }"
                                                        x-text="history.action"></span>

                                                    {{-- Timestamp --}}
                                                    <span class="text-[11px] text-slate-400 font-medium ml-auto"
                                                        x-text="new Date(history.created_at.replace(' ', 'T')).toLocaleDateString('id-ID', { 
                                  day: '2-digit', 
                                  month: 'short', 
                                  year: 'numeric', 
                                  hour: '2-digit', 
                                  minute: '2-digit' 
                              })"></span>
                                                </div>

                                                {{-- Description with Icon --}}
                                                <div class="flex gap-2">
                                                    <svg class="w-4 h-4 text-slate-400 flex-shrink-0 mt-0.5"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <p class="text-xs text-slate-600 leading-relaxed flex-1"
                                                        x-text="history.description"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    {{-- End Marker --}}
                                    <div class="relative ml-6">
                                        <div
                                            class="absolute -left-[33px] top-0 w-4 h-4 rounded-full bg-slate-200 border-2 border-white">
                                        </div>
                                        <span class="text-[10px] text-slate-400 font-medium">Awal riwayat</span>
                                    </div>
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
