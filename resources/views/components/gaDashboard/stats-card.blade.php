@props([
    'countTotal' => 0,
    'countDelayed' => 0,
    'countInProgress' => 0,
    'countCompleted' => 0,
    'countPending' => 0, // Ini akan berisi angka 5
    'countRejected' => 0,
    'countCancelled' => 0,
    'countWaitingApprovalGA' => 0, // Ini antrian yang belum diapprove (opsional ditampilkan)
])

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6 mb-8" x-show="show" x-transition>

    {{-- 1. TOTAL TIKET --}}
    <div
        class="bg-white rounded-sm shadow-md p-5 relative overflow-hidden group hover:-translate-y-1 transition-all duration-300 border-l-4 border-slate-800 hover:shadow-lg">
        <div class="relative z-10">
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Total Tiket</p>
            <p class="text-4xl md:text-5xl font-black text-slate-800">{{ $countTotal }}</p>
        </div>
        <div
            class="absolute -right-4 -bottom-4 text-slate-900 opacity-5 group-hover:opacity-10 group-hover:scale-110 group-hover:-rotate-12 transition-all duration-500">
            <svg class="w-28 h-28" fill="currentColor" viewBox="0 0 24 24">
                <path
                    d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" />
            </svg>
        </div>
    </div>

    {{-- 2. PENDING APPROVAL / SIAP KERJA (AMBER) --}}
    <div
        class="bg-white rounded-sm shadow-md p-5 relative overflow-hidden group hover:-translate-y-1 transition-all duration-300 border-l-4 border-amber-500 hover:shadow-amber-500/20">
        <div class="relative z-10">
            <p class="text-[10px] font-black text-amber-600 uppercase tracking-widest mb-1">Pending (Siap Kerja)</p>
            <p class="text-4xl md:text-5xl font-black text-slate-800">{{ $countPending }}</p>
            @if ($countWaitingApprovalGA > 0)
                <p class="text-[10px] text-amber-600 font-bold mt-1">+ {{ $countWaitingApprovalGA }} Menunggu Approval
                </p>
            @endif
        </div>
        <div
            class="absolute -right-4 -bottom-4 text-amber-500 opacity-10 group-hover:opacity-20 group-hover:scale-110 group-hover:-rotate-12 transition-all duration-500">
            <svg class="w-28 h-28" fill="currentColor" viewBox="0 0 24 24">
                <path
                    d="M6 2v6h.01L6 8.01 10 12l-4 4 .01.01H6V22h12v-5.99h-.01L18 16l-4-4 4-3.99-.01-.01H18V2H6zm10 14.5V20H8v-3.5l4-4 4 4z" />
            </svg>
        </div>
    </div>

    {{-- 3. IN PROGRESS (BLUE) --}}
    <div
        class="bg-white rounded-sm shadow-md p-5 relative overflow-hidden group hover:-translate-y-1 transition-all duration-300 border-l-4 border-blue-600 hover:shadow-blue-600/20">
        <div class="relative z-10">
            <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-1">In Progress</p>
            <p class="text-4xl md:text-5xl font-black text-slate-800">{{ $countInProgress }}</p>
        </div>
        <div
            class="absolute -right-4 -bottom-4 text-blue-600 opacity-10 group-hover:opacity-20 group-hover:scale-110 group-hover:rotate-90 transition-all duration-700">
            <svg class="w-28 h-28" fill="currentColor" viewBox="0 0 24 24">
                <path
                    d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.488.488 0 0 0-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54a.484.484 0 0 0-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58a.49.49 0 0 0-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z" />
            </svg>
        </div>
    </div>

    {{-- 4. SELESAI (EMERALD) --}}
    <div
        class="bg-white rounded-sm shadow-md p-5 relative overflow-hidden group hover:-translate-y-1 transition-all duration-300 border-l-4 border-emerald-500 hover:shadow-emerald-500/20">
        <div class="relative z-10">
            <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-1">Selesai</p>
            <p class="text-4xl md:text-5xl font-black text-slate-800">{{ $countCompleted }}</p>
        </div>
        <div
            class="absolute -right-4 -bottom-4 text-emerald-500 opacity-10 group-hover:opacity-20 group-hover:scale-110 group-hover:-rotate-12 transition-all duration-500">
            <svg class="w-28 h-28" fill="currentColor" viewBox="0 0 24 24">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
            </svg>
        </div>
    </div>

    {{-- 5. REJECTED (ROSE) --}}
    <div
        class="bg-white rounded-sm shadow-md p-5 relative overflow-hidden group hover:-translate-y-1 transition-all duration-300 border-l-4 border-rose-500 hover:shadow-rose-500/20">
        <div class="relative z-10">
            <p class="text-[10px] font-black text-rose-600 uppercase tracking-widest mb-1">Ditolak</p>
            <p class="text-4xl md:text-5xl font-black text-slate-800">{{ $countRejected }}</p>
        </div>
        <div
            class="absolute -right-4 -bottom-4 text-rose-500 opacity-10 group-hover:opacity-20 group-hover:scale-110 transition-all duration-500">
            <svg class="w-28 h-28" fill="currentColor" viewBox="0 0 24 24">
                <path
                    d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z" />
            </svg>
        </div>
    </div>

    {{-- 6. CANCELLED (ZINC) --}}
    <div
        class="bg-white rounded-sm shadow-md p-5 relative overflow-hidden group hover:-translate-y-1 transition-all duration-300 border-l-4 border-zinc-500 hover:shadow-zinc-500/20">
        <div class="relative z-10">
            <p class="text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-1">Dibatalkan</p>
            <p class="text-4xl md:text-5xl font-black text-slate-800">{{ $countCancelled }}</p>
        </div>
        <div
            class="absolute -right-4 -bottom-4 text-zinc-500 opacity-10 group-hover:opacity-20 group-hover:scale-110 transition-all duration-500">
            <svg class="w-28 h-28" fill="currentColor" viewBox="0 0 24 24">
                <path
                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8 0-1.85.63-3.55 1.69-4.9L16.9 18.31C15.55 19.37 13.85 20 12 20zm6.31-3.1L7.1 5.69C8.45 4.63 10.15 4 12 4c4.41 0 8 3.59 8 8 0 1.85-.63 3.55-1.69 4.9z" />
            </svg>
        </div>
    </div>

</div>
