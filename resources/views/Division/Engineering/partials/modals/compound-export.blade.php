<template x-teleport="body">
    <div x-show="showExportCompoundModal" style="display: none;" class="relative z-[70]">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" x-show="showExportCompoundModal"
            x-transition.opacity @click="showExportCompoundModal = false"></div>

        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md transform rounded-2xl bg-white p-6 text-left shadow-2xl transition-all"
                    @click.away="showExportCompoundModal = false">

                    <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100">
                        <h3 class="text-lg font-bold text-slate-800">Export Laporan Compound</h3>
                        <button @click="showExportCompoundModal = false" class="text-slate-400 hover:text-red-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form action="{{ route('eng.compound.export') }}" method="GET">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Pilih Plant <span
                                        class="text-red-500">*</span></label>
                                <select name="plant_id"
                                    class="w-full rounded border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2"
                                    required>
                                    <option value="">-- Pilih Plant --</option>
                                    {{-- Asumsi ID Plant A = 1, Autowire = 2. Sesuaikan dengan ID di Database Anda! --}}
                                    <option value="1">Plant A</option>
                                    <option value="2">Autowire (Multi 3 Honta)</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1">Bulan <span
                                            class="text-red-500">*</span></label>
                                    <select name="bulan"
                                        class="w-full rounded border-slate-300 shadow-sm focus:border-indigo-500 text-sm py-2"
                                        required>
                                        @foreach (range(1, 12) as $m)
                                            <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>
                                                {{ Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1">Tahun <span
                                            class="text-red-500">*</span></label>
                                    <select name="tahun"
                                        class="w-full rounded border-slate-300 shadow-sm focus:border-indigo-500 text-sm py-2"
                                        required>
                                        @foreach (range(date('Y') - 2, date('Y')) as $y)
                                            <option value="{{ $y }}" {{ date('Y') == $y ? 'selected' : '' }}>
                                                {{ $y }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end gap-2">
                            <button type="button" @click="showExportCompoundModal = false"
                                class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 bg-white hover:bg-slate-50">Batal</button>
                            <button type="submit"
                                class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 shadow-md flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Download Excel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
