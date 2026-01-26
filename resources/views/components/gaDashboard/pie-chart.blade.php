<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    {{-- 1. Lokasi --}}
    <div class="bg-white p-5 rounded-sm shadow-md border-t-4 border-slate-900">
        <h4
            class="text-sm font-black text-slate-900 uppercase tracking-widest mb-4 border-b border-slate-200 pb-2 flex items-center gap-2">
            <span class="text-yellow-400 text-lg leading-none">///</span> Statistik per Lokasi
        </h4>
        <div class="h-64"><canvas id="locChart"></canvas></div>
    </div>

    {{-- 2. Department --}}
    <div class="bg-white p-5 rounded-sm shadow-md border-t-4 border-slate-900">
        <h4
            class="text-sm font-black text-slate-900 uppercase tracking-widest mb-4 border-b border-slate-200 pb-2 flex items-center gap-2">
            <span class="text-yellow-400 text-lg leading-none">///</span> Statistik per Department
        </h4>
        <div class="h-64"><canvas id="deptChart"></canvas></div>
    </div>

    {{-- 3. Parameter --}}
    <div class="bg-white p-5 rounded-sm shadow-md border-t-4 border-slate-900">
        <h4
            class="text-sm font-black text-slate-900 uppercase tracking-widest mb-4 border-b border-slate-200 pb-2 flex items-center gap-2">
            <span class="text-yellow-400 text-lg leading-none">///</span> Parameter Permintaan
        </h4>
        <div class="h-64"><canvas id="paramChart"></canvas></div>
    </div>

    {{-- 4. Bobot --}}
    <div class="bg-white p-5 rounded-sm shadow-md border-t-4 border-slate-900">
        <h4
            class="text-sm font-black text-slate-900 uppercase tracking-widest mb-4 border-b border-slate-200 pb-2 flex items-center gap-2">
            <span class="text-yellow-400 text-lg leading-none">///</span> Bobot Pekerjaan
        </h4>
        <div class="h-64"><canvas id="bobotChart"></canvas></div>
    </div>
</div>
