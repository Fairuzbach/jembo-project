@props(['countTotal', 'countWaitingApproval', 'countProgress', 'countDone'])
<div class="grid grid-cols-1 md:grid-cols-4 gap-5">
    <div
        class="group bg-gradient-to-br from-white to-slate-50 rounded-2xl p-6 border border-slate-200 shadow-sm hover:shadow-lg hover:border-slate-300 transition-all duration-300 overflow-hidden relative">
        <div
            class="absolute top-0 right-0 w-20 h-20 bg-slate-100/30 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-300">
        </div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Total Tiket</p>
                <div
                    class="w-12 h-12 rounded-xl bg-gradient-to-br from-slate-200 to-slate-300 flex items-center justify-center">
                    <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                </div>
            </div>
            <h3 class="text-4xl font-extrabold text-slate-800">{{ $countTotal }}</h3>
            <p class="text-sm text-slate-500 mt-2">Semua tiket kerja</p>
        </div>
    </div>

    <div
        class="group bg-gradient-to-br from-amber-50 to-orange-50 rounded-2xl p-6 border border-amber-200 shadow-sm hover:shadow-lg hover:border-amber-300 transition-all duration-300 overflow-hidden relative">
        <div
            class="absolute top-0 right-0 w-20 h-20 bg-amber-100/30 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-300">
        </div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-bold text-amber-600 uppercase tracking-widest">Pending</p>
                <div
                    class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-300 to-amber-400 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <h3 class="text-4xl font-extrabold text-amber-700">{{ $countWaitingApproval }}</h3>
            <p class="text-sm text-amber-600/80 mt-2">Menunggu konfirmasi</p>
        </div>
    </div>

    <div
        class="group bg-gradient-to-br from-blue-50 to-cyan-50 rounded-2xl p-6 border border-blue-200 shadow-sm hover:shadow-lg hover:border-blue-300 transition-all duration-300 overflow-hidden relative">
        <div
            class="absolute top-0 right-0 w-20 h-20 bg-blue-100/30 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-300">
        </div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-bold text-blue-600 uppercase tracking-widest">In Progress</p>
                <div
                    class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-400 to-blue-500 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
            <h3 class="text-4xl font-extrabold text-blue-700">{{ $countProgress }}</h3>
            <p class="text-sm text-blue-600/80 mt-2">Sedang dikerjakan</p>
        </div>
    </div>

    <div
        class="group bg-gradient-to-br from-emerald-50 to-teal-50 rounded-2xl p-6 border border-emerald-200 shadow-sm hover:shadow-lg hover:border-emerald-300 transition-all duration-300 overflow-hidden relative">
        <div
            class="absolute top-0 right-0 w-20 h-20 bg-emerald-100/30 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-300">
        </div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-bold text-emerald-600 uppercase tracking-widest">Completed</p>
                <div
                    class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <h3 class="text-4xl font-extrabold text-emerald-700">{{ $countDone }}</h3>
            <div class="mt-4">
                <div class="flex justify-between items-center mb-1.5">
                    <span class="text-xs text-emerald-600 font-semibold">Completion Rate</span>
                    <span class="text-xs font-bold text-emerald-700">
                        {{ $countTotal > 0 ? round(($countDone / $countTotal) * 100) : 0 }}%
                    </span>
                </div>
                <div class="w-full bg-emerald-200/30 h-2 rounded-full overflow-hidden">
                    <div class="bg-gradient-to-r from-emerald-400 to-teal-400 h-2 rounded-full transition-all duration-500"
                        style="width: {{ $countTotal > 0 ? ($countDone / $countTotal) * 100 : 0 }}%">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
