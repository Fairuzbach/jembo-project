@props(['countTotal', 'countPending', 'countProgress', 'countDone'])
<div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
    <div
        class="counter-card bg-white p-7 rounded-2xl shadow-md border border-slate-100 hover:shadow-lg hover:border-blue-200 transition-all duration-300">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Total</p>
        <p class="text-4xl font-bold text-[#1E3A5F] mt-2">{{ $countTotal }}</p>
        <div class="mt-3 h-1 bg-gradient-to-r from-slate-400 to-slate-500 rounded-full"></div>
    </div>
    <div
        class="counter-card bg-white p-7 rounded-2xl shadow-md border border-slate-100 hover:shadow-lg hover:border-amber-200 transition-all duration-300">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Pending</p>
        <p class="text-4xl font-bold text-amber-600 mt-2">{{ $countPending }}</p>
        <div class="mt-3 h-1 bg-gradient-to-r from-amber-400 to-yellow-500 rounded-full"></div>
    </div>
    <div
        class="counter-card bg-white p-7 rounded-2xl shadow-md border border-slate-100 hover:shadow-lg hover:border-blue-400 transition-all duration-300">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">In Progress</p>
        <p class="text-4xl font-bold text-blue-600 mt-2">{{ $countProgress }}</p>
        <div class="mt-3 h-1 bg-gradient-to-r from-blue-400 to-cyan-500 rounded-full"></div>
    </div>
    <div
        class="counter-card bg-white p-7 rounded-2xl shadow-md border border-slate-100 hover:shadow-lg hover:border-emerald-200 transition-all duration-300">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Completed</p>
        <p class="text-4xl font-bold text-[#22C55E] mt-2">{{ $countDone }}</p>
        <div class="mt-3 h-1 bg-gradient-to-r from-emerald-400 to-teal-500 rounded-full"></div>
    </div>

    {{-- Completion % for selected period --}}
    <div
        class="counter-card bg-white p-7 rounded-2xl shadow-md border border-slate-100 hover:shadow-lg hover:border-indigo-200 transition-all duration-300">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Completion (Selected)</p>
        {{-- <p class="text-4xl font-bold text-indigo-600 mt-2">{{ $completionPct }}%</p> --}}
        <p class="text-xs text-slate-400 mt-3">period: <span class="font-semibold">{{ $selectedMonth ?? 'All' }}</span>
        </p>
    </div>
</div>
