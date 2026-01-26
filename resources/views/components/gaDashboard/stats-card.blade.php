@props(['countTotal', 'countDelayed', 'countInProgress', 'countCompleted'])
{{-- Legacy support for old 'countPending' prop --}}
@php
    $countDelayed = $countDelayed ?? ($countPending ?? 0);
@endphp
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8" x-show="show" x-transition>

    {{-- 1. Card Total --}}
    <div
        class="bg-white rounded-sm shadow-md p-6 relative overflow-hidden group hover:-translate-y-2 transition-all duration-300 border-l-4 border-slate-900 hover:shadow-xl">
        <div class="relative z-10">
            <p
                class="text-xs font-black text-slate-500 uppercase tracking-widest mb-1 group-hover:text-slate-800 transition-colors">
                Total Tiket</p>
            <p class="text-5xl font-black text-slate-900">{{ $countTotal }}</p>
        </div>
        {{-- Animated Icon --}}
        <div
            class="absolute -right-6 -bottom-6 text-slate-900 opacity-5 group-hover:opacity-10 group-hover:scale-110 group-hover:-rotate-12 transition-all duration-500 ease-out">
            <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24">
                <path
                    d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" />
            </svg>
        </div>
        {{-- Bottom Accent --}}
        <div class="absolute bottom-0 left-0 w-0 h-1 bg-slate-900 group-hover:w-full transition-all duration-500">
        </div>
    </div>

    {{-- 2. Card Overdue/Delayed (Red Glow) --}}
    <div
        class="bg-white rounded-sm shadow-md p-6 relative overflow-hidden group hover:-translate-y-2 transition-all duration-300 border-l-4 border-red-500 hover:shadow-red-500/20 hover:shadow-xl">
        <div class="relative z-10">
            <p
                class="text-xs font-black text-red-600 uppercase tracking-widest mb-1 group-hover:text-red-700 transition-colors">
                Overdue</p>
            <p class="text-5xl font-black text-slate-900">{{ $countDelayed }}</p>
        </div>
        <div
            class="absolute -right-6 -bottom-6 text-red-500 opacity-10 group-hover:opacity-20 group-hover:scale-110 group-hover:rotate-12 transition-all duration-500 ease-out">
            <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24">
                <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z" />
            </svg>
        </div>
        <div class="absolute bottom-0 left-0 w-0 h-1 bg-red-500 group-hover:w-full transition-all duration-500">
        </div>
    </div>

    {{-- 3. Card In Progress (Blue Glow & Gear Spin) --}}
    <div
        class="bg-white rounded-sm shadow-md p-6 relative overflow-hidden group hover:-translate-y-2 transition-all duration-300 border-l-4 border-blue-600 hover:shadow-blue-600/20 hover:shadow-xl">
        <div class="relative z-10">
            <p
                class="text-xs font-black text-blue-600 uppercase tracking-widest mb-1 group-hover:text-blue-700 transition-colors">
                In Progress</p>
            <p class="text-5xl font-black text-slate-900">{{ $countInProgress }}</p>
        </div>
        <div
            class="absolute -right-6 -bottom-6 text-blue-600 opacity-10 group-hover:opacity-20 group-hover:scale-110 group-hover:rotate-90 transition-all duration-700 ease-out">
            <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24">
                <path
                    d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.488.488 0 0 0-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54a.484.484 0 0 0-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58a.49.49 0 0 0-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z" />
            </svg>
        </div>
        <div class="absolute bottom-0 left-0 w-0 h-1 bg-blue-600 group-hover:w-full transition-all duration-500">
        </div>
    </div>

    {{-- 4. Card Selesai (Emerald Glow) --}}
    <div
        class="bg-white rounded-sm shadow-md p-6 relative overflow-hidden group hover:-translate-y-2 transition-all duration-300 border-l-4 border-emerald-500 hover:shadow-emerald-500/20 hover:shadow-xl">
        <div class="relative z-10">
            <p
                class="text-xs font-black text-emerald-600 uppercase tracking-widest mb-1 group-hover:text-emerald-700 transition-colors">
                Selesai</p>
            <p class="text-5xl font-black text-slate-900">{{ $countCompleted }}</p>
        </div>
        <div
            class="absolute -right-6 -bottom-6 text-emerald-500 opacity-10 group-hover:opacity-20 group-hover:scale-110 group-hover:-rotate-12 transition-all duration-500 ease-out">
            <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24">
                <path
                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
            </svg>
        </div>
        <div class="absolute bottom-0 left-0 w-0 h-1 bg-emerald-500 group-hover:w-full transition-all duration-500">
        </div>
    </div>
</div>
