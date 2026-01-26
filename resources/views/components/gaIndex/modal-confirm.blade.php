<template x-teleport="body">
    <div x-data="{
        showConfirmModal: false, // <--- TAMBAHAN PENTING 1: Definisi variabel show
        data: {
            nik: '-',
            manual_requester_name: '-',
            department: '-',
            category: '-',
            parameter_permintaan: '-',
            description: '-',
            target_completion_date: '-',
            plant_id: ''
        }
    }"
        @open-confirm-modal.window="
            data = window.gaFormData || data; // Ambil data
            showConfirmModal = true;          // <--- TAMBAHAN PENTING 2: Munculkan modal
        "
        x-show="showConfirmModal" style="display: none;" class="fixed inset-0 z-[60] overflow-y-auto">

        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" x-show="showConfirmModal"
            x-transition.opacity @click="showConfirmModal = false">
        </div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-100 transform transition-all"
                x-show="showConfirmModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

                <div class="bg-gradient-to-r from-orange-500 to-red-500 p-6 text-center">
                    <div
                        class="mx-auto bg-white/20 w-16 h-16 rounded-full flex items-center justify-center mb-3 backdrop-blur-sm">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-black text-white uppercase tracking-wider">Konfirmasi Laporan</h3>
                    <p class="text-white/80 text-sm mt-1">Pastikan data di bawah ini sudah benar</p>
                </div>

                <div class="p-6 space-y-4 bg-slate-50">
                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span
                                    class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">NIK</span>
                                <span class="font-bold text-slate-800" x-text="data.nik || '-'"></span>
                            </div>
                            <div>
                                <span
                                    class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nama</span>
                                <span class="font-bold text-slate-800"
                                    x-text="data.manual_requester_name || '-'"></span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between border-b border-slate-200 pb-2">
                            <span class="text-slate-500">Departemen Pelapor</span>
                            <span class="font-bold text-slate-800" x-text="data.department || '-'"></span>
                        </div>
                        <div class="flex justify-between border-b border-slate-200 pb-2">
                            <span class="text-slate-500">Jenis Permintaan</span>
                            <span class="font-bold text-slate-800" x-text="data.parameter_permintaan || '-'"></span>
                        </div>
                        <div class="flex justify-between border-b border-slate-200 pb-2">
                            <span class="text-slate-500">Target Selesai</span>
                            <span class="font-bold text-slate-800"
                                x-text="data.target_completion_date ? data.target_completion_date : 'Tidak ditentukan'"></span>
                        </div>
                        <div>
                            <span class="block text-slate-500 mb-1">Uraian Pekerjaan:</span>
                            <p class="font-medium text-slate-800 bg-white p-3 rounded-lg border border-slate-200 text-xs italic"
                                x-text="data.description || '-'"></p>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-white border-t border-slate-100 flex gap-3">
                    <button @click="showConfirmModal = false"
                        class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition">Periksa
                        Lagi</button>

                    {{-- TOMBOL SUBMIT FINAL --}}
                    <button @click="$dispatch('submit-confirmed'); showConfirmModal = false;"
                        class="flex-1 px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-bold rounded-xl hover:from-blue-700 hover:to-blue-800 shadow-lg shadow-blue-500/30 transition transform active:scale-95">
                        Ya, Kirim Sekarang
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
