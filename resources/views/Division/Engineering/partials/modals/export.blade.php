<template x-teleport="body">
    <div x-show="showExportModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
        <div x-show="showExportModal" x-transition.opacity
            class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" @click="showExportModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div x-show="showExportModal"
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200">
                <div class="bg-white px-4 py-4 sm:px-6 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">Export Data Laporan</h3>
                </div>
                <form action="{{ route('eng.export') }}" method="GET">
                    <div class="px-6 py-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Dari Tanggal</label>
                                <input type="date" name="start_date" required
                                    class="w-full rounded-md border-slate-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Sampai Tanggal</label>
                                <input type="date" name="end_date" required
                                    class="w-full rounded-md border-slate-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-4 sm:flex sm:flex-row-reverse gap-3">
                        <button type="submit"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Download</button>
                        <button type="button" @click="showExportModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
