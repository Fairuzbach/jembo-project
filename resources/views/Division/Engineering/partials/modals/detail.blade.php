<template x-teleport="body">
    <div x-show="showDetailModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
        <div x-show="showDetailModal" class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity"
            @click="showDetailModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div x-show="showDetailModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl">

                <div class="bg-slate-50 px-4 py-3 sm:px-6 flex justify-between items-center border-b border-slate-200">
                    <h3 class="text-base font-semibold leading-6 text-slate-900">Detail Work Order</h3>
                    <button @click="showDetailModal = false"
                        class="text-slate-400 hover:text-slate-500">&times;</button>
                </div>
                <div class="bg-white px-6 py-6 max-h-[80vh] overflow-y-auto">
                    <template x-if="ticket">
                        <div class="space-y-6">
                            <div class="flex justify-between items-start border-b border-slate-200 pb-4">
                                <div>
                                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Nomor
                                        Tiket</span>
                                    <p class="text-2xl font-bold text-indigo-600 font-mono mt-1"
                                        x-text="ticket.ticket_num"></p>
                                </div>
                                <div class="text-right">
                                    <span
                                        class="text-xs font-bold text-slate-500 uppercase tracking-wider">Status</span>

                                    <div class="mt-1">
                                        <span
                                            class="px-3 py-1 text-sm font-semibold rounded-full bg-indigo-100 text-indigo-800"
                                            x-text="ticket.improvement_status ? ticket.improvement_status.replace('_', ' ').toUpperCase() : ''"></span>

                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                                <div>
                                    <span class="text-xs text-slate-500 block mb-1">Tanggal & Jam Lapor</span>
                                    <p class="text-sm font-medium text-slate-900">
                                        <span
                                            x-text="ticket.report_date ? ticket.report_date.substring(0,10).replace(/-/g, '/') : ''"></span>

                                        • <span
                                            x-text="ticket.report_time ? ticket.report_time.substring(0,5) : ''"></span>

                                    </p>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 block mb-1">Pelapor</span>
                                    <p class="text-sm font-medium text-slate-900"><span
                                            x-text="ticket.requester_name"></span></p>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 block mb-1">Plant / Area</span>
                                    <p class="text-sm font-medium text-slate-900" x-text="ticket.plant"></p>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 block mb-1">Mesin / Unit</span>
                                    <p class="text-sm font-medium text-slate-900" x-text="ticket.machine_name"></p>

                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 block mb-1">Judul</span>
                                    <p class="text-sm font-medium text-slate-900" x-text="ticket.damaged_part"></p>

                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 block mb-1">Parameter Improvement</span>
                                    <p class="text-sm font-medium text-slate-900"
                                        x-text="ticket.improvement_parameters"></p>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 block mb-2">Engineer</span>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-if="ticket.technicians || ticket.engineer_tech">
                                            <template
                                                x-for="techName in (ticket.technicians || ticket.engineer_tech).split(',')"
                                                :key="techName">
                                                <span
                                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700 border border-indigo-200 shadow-sm">

                                                    <svg class="w-3 h-3 mr-1.5 opacity-50" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                                        </path>
                                                    </svg>
                                                    <span x-text="techName.trim()"></span>
                                                </span>
                                            </template>
                                        </template>
                                        <template x-if="!ticket.technicians && !ticket.engineer_tech">
                                            <span class="text-sm text-slate-400 italic">- Tidak ada teknisi -</span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-slate-50 p-4 rounded-lg border border-slate-100">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wide block mb-2">Uraian
                                    Improvement</span>
                                <p class="text-sm text-slate-800 whitespace-pre-wrap leading-relaxed"
                                    x-text="ticket.kerusakan_detail"></p>
                            </div>
                            <template x-if="ticket.photo_path">
                                <div>
                                    <span
                                        class="text-xs font-bold text-slate-500 uppercase tracking-wide block mb-2">Foto
                                        Bukti</span>
                                    <div class="rounded-lg overflow-hidden border border-slate-200">
                                        <img :src="'/storage/' + ticket.photo_path" alt="Bukti Foto"
                                            class="w-full h-auto max-h-96 object-contain bg-slate-100">
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
                <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="button"
                        class="inline-flex w-full justify-center rounded-md bg-white border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm hover:bg-slate-50 sm:ml-3 sm:w-auto"
                        @click="showDetailModal = false">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</template>
