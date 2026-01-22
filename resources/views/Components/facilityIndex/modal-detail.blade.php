<template x-teleport="body">
    <div x-show="showDetailModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">

        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl transform rounded-2xl bg-white p-6 text-left shadow-xl transition-all"
                @click.away="showDetailModal = false">

                <div class="flex items-center justify-between mb-6 border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Detail Work Order</h3>
                        <p class="text-sm text-slate-500" x-text="ticket ? ticket.ticket_num : '-'"></p>
                    </div>
                    <button @click="showDetailModal = false" class="text-slate-400 hover:text-slate-500">
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
                                                // Jika status null, anggap string kosong agar tidak error .includes
                                                'bg-yellow-100 text-yellow-700': (ticket && ticket.status || '')
                                                    .includes('waiting'),
                                            
                                                'bg-blue-100 text-blue-700': ticket && (ticket
                                                    .status === 'on_progress' || ticket.status === 'pending'),
                                            
                                                'bg-green-100 text-green-700': ticket && ticket.status === 'completed',
                                            
                                                'bg-red-100 text-red-700': ticket && ticket.status === 'rejected'
                                            }"
                                // Cek dulu apakah status ada sebelum di-replace
                                x-text="(ticket && ticket.status) ? ticket.status.replace(/_/g, ' ') : '-'">
                            </span>

                            <span
                                class="px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-bold"
                                x-text="ticket.plant || '-'">
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50 p-4 rounded-xl">
                            <div>
                                <p class="text-xs font-medium text-slate-500 uppercase">Requester</p>
                                <p class="font-semibold text-slate-800" x-text="ticket.requester_name">
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-slate-500 uppercase">Category</p>
                                <p class="font-semibold text-slate-800" x-text="ticket.category"></p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-slate-500 uppercase">Date Created</p>
                                <p class="font-semibold text-slate-800"
                                    x-text="new Date(ticket.created_at).toLocaleDateString('id-ID')"></p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-slate-500 uppercase">Target Date</p>
                                <p class="font-semibold text-slate-800"
                                    x-text="ticket.target_completion_date || '-'"></p>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-sm font-bold text-slate-900 mb-2">Description</h4>
                            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                                <p class="text-slate-600 leading-relaxed whitespace-pre-wrap"
                                    x-text="ticket.description"></p>
                            </div>
                        </div>

                        <div x-show="ticket.machine_id || ticket.new_machine_name">
                            <h4 class="text-sm font-bold text-slate-900 mb-2">Machine Detail</h4>
                            <div
                                class="flex items-center gap-3 p-3 bg-blue-50 text-blue-700 rounded-lg border border-blue-100">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="font-medium"
                                    x-text="ticket.new_machine_name || ('Machine Name: ' + ticket.machine_name)"></span>
                            </div>
                        </div>

                        <template x-if="ticket.photo_path">
                            <div>
                                <h4 class="text-sm font-bold text-slate-900 mb-2">Attached Photo</h4>
                                <div class="rounded-xl overflow-hidden border border-slate-200">
                                    <img :src="'/storage/' + ticket.photo_path" alt="Ticket Photo"
                                        class="w-full h-auto max-h-64 object-cover hover:scale-105 transition-transform duration-500">
                                </div>
                            </div>
                        </template>

                    </div>
                </template>

                <template x-if="!ticket">
                    <div class="flex flex-col items-center justify-center py-10 space-y-3">
                        <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <p class="text-slate-400 text-sm">Memuat data...</p>
                    </div>
                </template>

                <div class="mt-8 flex justify-end">
                    <button @click="showDetailModal = false"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-medium transition-colors">
                        Close
                    </button>
                </div>

            </div>
        </div>
    </div>
</template>