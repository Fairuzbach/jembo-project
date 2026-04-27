<div x-data="{
    showDetailModal: false,
    showImagePreview: false,
    imgPreviewUrl: '',
    ticket: null
}" {{-- Ini adalah kunci rahasianya: Mendengarkan event dari tombol manapun --}}
    @open-detail-modal.window="
        ticket = $event.detail; 
        showDetailModal = true; 
        showImagePreview = false;
    ">

    <template x-teleport="body">
        <div x-show="showDetailModal" class="relative z-[60]" style="display: none;">

            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" x-show="showDetailModal"
                x-transition.opacity></div>

            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">

                    <div class="relative w-full max-w-2xl transform rounded-2xl bg-white p-6 text-left shadow-2xl transition-all"
                        @click.away="if(!showImagePreview) showDetailModal = false">

                        <div class="flex items-center justify-between mb-6 border-b border-slate-100 pb-4">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Detail Work Order</h3>
                                <p class="text-sm text-slate-500" x-text="ticket ? ticket.ticket_num : '-'"></p>
                            </div>
                            <button @click="showDetailModal = false"
                                class="text-slate-400 hover:text-slate-600 transition-colors">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <template x-if="ticket">
                            <div class="space-y-6">
                                <div class="flex flex-wrap gap-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider"
                                        :class="{
                                            'bg-yellow-100 text-yellow-700': (ticket.status || '').includes('waiting'),
                                            'bg-blue-100 text-blue-700': (ticket.status === 'on_progress' || ticket
                                                .status === 'pending'),
                                            'bg-green-100 text-green-700': ticket.status === 'completed',
                                            'bg-red-100 text-red-700': ticket.status === 'rejected'
                                        }"
                                        x-text="ticket.status ? ticket.status.replace(/_/g, ' ') : '-'">
                                    </span>
                                    <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-bold"
                                        x-text="ticket.plant || '-'">
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50 p-4 rounded-xl">
                                    <div>
                                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">
                                            Requester</p>
                                        <p class="font-semibold text-slate-800" x-text="ticket.requester_name"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">
                                            Category</p>
                                        <p class="font-semibold text-slate-800" x-text="ticket.category"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Date
                                            Created</p>
                                        <p class="font-semibold text-slate-800"
                                            x-text="new Date(ticket.created_at).toLocaleDateString('id-ID')"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">
                                            Target Date</p>
                                        <p class="font-semibold text-slate-800"
                                            x-text="ticket.target_completion_date || '-'">
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 mb-2">Description</h4>
                                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                                        <p class="text-slate-600 text-sm leading-relaxed whitespace-pre-wrap"
                                            x-text="ticket.description"></p>
                                    </div>
                                </div>
                                <template x-if="ticket && ticket.status === 'rejected' && ticket.rejection_reason">
                                    <div>
                                        <h4 class="text-sm font-bold text-rose-700 mb-2">Alasan Penolakan</h4>
                                        <div class="p-4 bg-rose-50 rounded-xl border border-rose-200">
                                            <p class="text-rose-700 text-sm leading-relaxed whitespace-pre-wrap"
                                                x-text="ticket.rejection_reason"></p>
                                        </div>
                                    </div>
                                </template>

                                <div x-show="ticket.machine_id || ticket.new_machine_name">
                                    <h4 class="text-sm font-bold text-slate-900 mb-2">Machine Detail</h4>
                                    <div
                                        class="flex items-center gap-3 p-3 bg-blue-50 text-blue-700 rounded-lg border border-blue-100">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span class="font-medium text-sm"
                                            x-text="ticket.new_machine_name || ('Machine Name: ' + ticket.machine_name)"></span>
                                    </div>
                                </div>

                                <template x-if="ticket.photo_path">
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-900 mb-2">Attached Photo</h4>
                                        <div class="rounded-xl overflow-hidden border border-slate-200 cursor-zoom-in group relative"
                                            @click="imgPreviewUrl = '/storage/' + ticket.photo_path; showImagePreview = true">

                                            <img :src="ticket ? '/storage/' + ticket.photo_path : ''"
                                                class="w-full h-auto max-h-64 object-cover group-hover:scale-105 transition-transform duration-500">

                                            <div
                                                class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity duration-300">
                                                <span
                                                    class="text-white text-xs font-bold bg-slate-900/60 px-4 py-2 rounded-full backdrop-blur-md shadow-lg flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7">
                                                        </path>
                                                    </svg>
                                                    Click to Zoom
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="!ticket">
                            <div class="flex flex-col items-center justify-center py-12">
                                <svg class="animate-spin h-10 w-10 text-blue-500 mb-4"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <p class="text-slate-500 font-medium">Memuat detail tiket...</p>
                            </div>
                        </template>

                        <div class="mt-8 pt-4 border-t border-slate-100 flex justify-end">
                            <button @click="showDetailModal = false"
                                class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold transition-colors">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="showImagePreview" class="fixed inset-0 z-[70] flex items-center justify-center p-4 sm:p-8"
                style="display: none;">

                <div class="fixed inset-0 bg-slate-950/95 backdrop-blur-md transition-opacity"
                    @click="showImagePreview = false" x-show="showImagePreview" x-transition.opacity></div>

                <button @click="showImagePreview = false"
                    class="absolute top-4 right-4 sm:top-8 sm:right-8 text-white/50 hover:text-white bg-white/10 hover:bg-white/20 p-2 rounded-full backdrop-blur-lg transition-all z-[80]">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="relative z-[75] max-w-6xl w-full flex flex-col items-center justify-center"
                    x-show="showImagePreview" x-transition.scale.origin.center>

                    <img :src="imgPreviewUrl" @click.stop=""
                        class="max-w-full max-h-[85vh] rounded-lg shadow-2xl object-contain border border-white/10 select-none">

                    <a :href="imgPreviewUrl" target="_blank"
                        class="mt-6 px-6 py-2 bg-white/10 hover:bg-white/20 text-white text-sm font-bold rounded-full backdrop-blur-md transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Buka Gambar Asli
                    </a>
                </div>
            </div>

        </div>
    </template>
</div>
