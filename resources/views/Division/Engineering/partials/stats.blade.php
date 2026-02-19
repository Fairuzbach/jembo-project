<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6" x-data="{ show: false }" x-init="setTimeout(() => show = true, 100)">

    {{-- Card Total --}}
    <div x-show="show" x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
        class="relative group bg-white overflow-hidden shadow-sm sm:rounded-xl p-6 border border-indigo-100 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 hover:border-indigo-300 cursor-default">
        <div
            class="absolute -right-6 -top-6 w-24 h-24 bg-indigo-50 rounded-full group-hover:bg-indigo-100 transition-all duration-500">
        </div>

        <div class="relative flex items-center justify-between z-10">
            <div>
                <div class="text-sm font-semibold text-indigo-500 mb-1 tracking-wide uppercase">Total Tiket</div>
                <div class="text-3xl font-extrabold text-slate-800 group-hover:text-indigo-600 transition-colors">
                    {{ $countTotal }}</div>
            </div>
            <div
                class="p-3 bg-indigo-50 rounded-lg text-indigo-600 group-hover:scale-110 group-hover:bg-indigo-600 group-hover:text-white transition-all duration-300 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" />

                </svg>
            </div>
        </div>
    </div>

    {{-- Card Open --}}
    <div x-show="show" x-transition:enter="transition ease-out duration-500 delay-100"
        x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
        class="relative group bg-white overflow-hidden shadow-sm sm:rounded-xl p-6 border border-blue-100 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 hover:border-blue-300 cursor-default">
        <div
            class="absolute -right-6 -top-6 w-24 h-24 bg-blue-50 rounded-full group-hover:bg-blue-100 transition-all duration-500">
        </div>

        <div class="relative flex items-center justify-between z-10">
            <div>
                <div class="text-sm font-semibold text-blue-500 mb-1 tracking-wide uppercase">OPEN</div>
                <div class="text-3xl font-extrabold text-slate-800 group-hover:text-blue-600 transition-colors">
                    {{ $countPending }}</div>
            </div>
            <div
                class="p-3 bg-blue-50 rounded-lg text-blue-600 group-hover:scale-110 group-hover:bg-blue-500 group-hover:text-white transition-all duration-300 shadow-sm">

                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>

    {{-- Card WIP --}}
    <div x-show="show" x-transition:enter="transition ease-out duration-500 delay-200"
        x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
        class="relative group bg-white overflow-hidden shadow-sm sm:rounded-xl p-6 border border-amber-100 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 hover:border-amber-300 cursor-default">

        <div
            class="absolute -right-6 -top-6 w-24 h-24 bg-amber-50 rounded-full group-hover:bg-amber-100 transition-all duration-500">
        </div>

        <div class="relative flex items-center justify-between z-10">
            <div>
                <div class="text-sm font-semibold text-amber-500 mb-1 tracking-wide uppercase">WIP (IN PROGRESS)</div>

                <div class="text-3xl font-extrabold text-slate-800 group-hover:text-amber-600 transition-colors">
                    {{ $countInProgress }}</div>
            </div>
            <div
                class="p-3 bg-amber-50 rounded-lg text-amber-600 group-hover:scale-110 group-hover:bg-amber-500 group-hover:text-white transition-all duration-300 shadow-sm">

                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />

                </svg>
            </div>
        </div>
    </div>

    {{-- Card CLOSED --}}
    <div x-show="show" x-transition:enter="transition ease-out duration-500 delay-200"
        x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
        class="relative group bg-white overflow-hidden shadow-sm sm:rounded-xl p-6 border border-emerald-100 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 hover:border-emerald-300 cursor-default">
        <div
            class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-50 rounded-full group-hover:bg-emerald-100 transition-all duration-500">
        </div>

        <div class="relative flex items-center justify-between z-10">
            <div>
                <div class="text-sm font-semibold text-emerald-500 mb-1 tracking-wide uppercase">CLOSED</div>
                <div class="text-3xl font-extrabold text-slate-800 group-hover:text-emerald-600 transition-colors">
                    {{ $countCompleted }}</div>
            </div>
            <div
                class="p-3 bg-emerald-50 rounded-lg text-emerald-600 group-hover:scale-110 group-hover:bg-emerald-500 group-hover:text-white transition-all duration-300 shadow-sm">

                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>
</div>
