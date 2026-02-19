<template x-teleport="body">
    <div x-show="showConfirmModal" style="display: none;" class="fixed inset-0 z-[60] overflow-y-auto" role="dialog"
        aria-modal="true">
        <div x-show="showConfirmModal" class="fixed inset-0 bg-slate-900 bg-opacity-90 transition-opacity"
            @click="showConfirmModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div x-show="showConfirmModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border-2 border-indigo-500">

                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4"> [cite: 404]
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">

                            <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg font-semibold leading-6 text-slate-900" id="modal-title">Konfirmasi
                                Laporan</h3>
                            <div class="mt-4 space-y-3 text-sm text-slate-600">
                                <div class="grid grid-cols-2 gap-2 bg-slate-50 p-3 rounded-md">
                                    <span class="font-semibold">Tanggal:</span> <span x-text="currentDate"></span>

                                    <span class="font-semibold">Jam:</span> <span x-text="currentTime"></span>
                                    <span class="font-semibold">Shift:</span> <span x-text="currentShift"></span>
                                    <span class="font-semibold">Plant:</span> <span x-text="form.plant"></span>
                                    <span class="font-semibold">Mesin:</span> <span x-text="form.machine_name"></span>

                                    <span class="font-semibold">Judul:</span> <span x-text="form.damaged_part"></span>

                                    <span class="font-semibold">Parameter Improvement:</span> <span
                                        x-text="form.improvement_parameters ? form.improvement_parameters : 'Belum dipilih'"></span>

                                    <span class="font-semibold">Prioritas:</span> <span
                                        x-text="form.priority.toUpperCase()"></span>
                                </div>
                                <div>
                                    <span class="font-bold block">Uraian Improvement:</span>
                                    <p class="italic" x-text="form.kerusakan_detail"></p>
                                </div>
                                <template x-if="form.file_name">
                                    <div class="text-indigo-500 text-xs">📎 File terlampir: <span
                                            x-text="form.file_name"></span></div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-2">
                    <button type="button" @click="submitForm()"
                        class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto">Ya,
                        Kirim Laporan</button>
                    <button type="button" @click="showConfirmModal = false"
                        class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Periksa
                        Lagi</button>
                </div>
            </div>
        </div>
    </div>
</template>
