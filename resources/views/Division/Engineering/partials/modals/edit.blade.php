<template x-teleport="body">
    <div x-show="showEditModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" @click="showEditModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                <div class="bg-white px-4 py-4 sm:px-6 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900"
                        x-text="userRole === 'eng.admin' ? 'Admin Approval #' + editForm.ticket_num : 'Update Status Laporan #' + editForm.ticket_num">
                    </h3>
                </div>

                <form x-ref="editFormHtml" :action="'/engineering/' + editForm.id + '/update-status'" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="px-6 py-6 space-y-6">
                        <div class="bg-slate-50 p-4 rounded-md border border-slate-200 text-sm">
                            <p class="font-bold text-slate-700" x-text="ticket.damaged_part"></p>
                            <p class="text-slate-500 mt-1" x-text="ticket.kerusakan_detail"></p>
                        </div>

                        {{-- USER VIEW (UPDATE STATUS) --}}
                        <template x-if="userRole !== 'eng.admin'">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Update Status
                                    Pengerjaan</label>
                                <div class="grid grid-cols-2 gap-4">
                                    <label class="cursor-pointer">
                                        <input type="radio" name="status" value="WIP" x-model="editForm.status"
                                            class="peer sr-only">
                                        <div
                                            class="rounded-md border border-slate-200 p-4 text-center hover:bg-amber-50 peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-700 transition-all">
                                            <div class="font-bold">WIP</div>
                                            <div class="text-xs">Sedang Dikerjakan</div>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="status" value="CLOSED" x-model="editForm.status"
                                            class="peer sr-only">
                                        <div
                                            class="rounded-md border border-slate-200 p-4 text-center hover:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 transition-all">
                                            <div class="font-bold">CLOSED</div>
                                            <div class="text-xs">Selesai (Auto Date)</div>
                                        </div>
                                    </label>
                                </div>
                                <p class="text-xs text-slate-500 mt-2">*Memilih CLOSED akan otomatis mencatat tanggal
                                    selesai hari ini.</p>
                            </div>
                        </template>

                        {{-- ADMIN VIEW (APPROVAL) --}}
                        <template x-if="userRole === 'eng.admin'">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Keputusan Admin</label>
                                <select name="status" x-model="editForm.status"
                                    class="w-full rounded-md border-slate-300">
                                    <option value="OPEN">OPEN (Pending)</option>
                                    <option value="WIP">WIP (In Progress)</option>
                                    <option value="CLOSED">CLOSED (Completed)</option>
                                    <option value="cancelled">CANCELLED</option>
                                </select>
                            </div>
                        </template>
                    </div>
                    <div class="bg-slate-50 px-6 py-4 sm:flex sm:flex-row-reverse border-t border-slate-200 gap-3">
                        <button type="submit"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">Simpan
                            Status</button> [cite: 512, 513]
                        <button type="button" @click="showEditModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
                        [cite: 514, 515]
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
