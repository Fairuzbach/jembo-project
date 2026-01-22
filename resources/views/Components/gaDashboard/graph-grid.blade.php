@props(['filterMonth', 'perfPercentage', 'perfTotal', 'perfCompleted'])
<div class="bg-white p-6 rounded-sm shadow-md border-t-4 border-yellow-400 mb-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
        <div>
            <h3 class="text-lg font-black text-slate-900 uppercase tracking-wider">
                Pencapaian Bulanan
            </h3>
            <p class="text-xs text-slate-500 font-bold mt-1">
                Berdasarkan Target Penyelesaian Tiket
            </p>
        </div>

        {{-- FILTER KHUSUS BULAN --}}
        <form action="{{ route('ga.dashboard') }}" method="GET" class="flex items-center gap-2">
            {{-- Keep other filters if exist (optional, agar tidak reset filter lain) --}}
            @if (request('start_date'))
                <input type="hidden" name="start_date" value="{{ request('start_date') }}">
            @endif
            @if (request('end_date'))
                <input type="hidden" name="end_date" value="{{ request('end_date') }}">
            @endif

            <label for="filter_month" class="text-xs font-bold text-slate-600 uppercase">Pilih
                Bulan:</label>
            <input type="month" name="filter_month" id="filter_month" value="{{ $filterMonth }}"
                onchange="this.form.submit()"
                class="border-2 border-slate-200 rounded-sm text-sm font-bold text-slate-800 focus:border-yellow-400 focus:ring-0 py-1">
        </form>
    </div>

    <div class="flex flex-col md:flex-row items-center gap-8">
        {{-- KANVAS CHART DOUGHNUT --}}
        <div class="relative w-48 h-48">
            <canvas id="performanceChart"></canvas>
            {{-- Teks Persentase di Tengah --}}
            <div class="absolute inset-0 flex items-center justify-center flex-col pointer-events-none">
                <span class="text-3xl font-black text-slate-900">{{ $perfPercentage }}%</span>
                <span class="text-[10px] font-bold text-slate-400 uppercase">Selesai</span>
            </div>
        </div>

        {{-- KETERANGAN TEKS --}}
        <div class="flex-1 w-full">
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-slate-50 p-4 rounded-sm border-l-4 border-slate-300">
                    <p class="text-xs font-bold text-slate-500 uppercase">Total Target</p>
                    <p class="text-2xl font-black text-slate-800">{{ $perfTotal }} <span
                            class="text-sm font-normal text-slate-400">Tiket</span></p>
                </div>
                <div class="bg-green-50 p-4 rounded-sm border-l-4 border-green-500">
                    <p class="text-xs font-bold text-green-600 uppercase">Terealisasi</p>
                    <p class="text-2xl font-black text-green-700">{{ $perfCompleted }} <span
                            class="text-sm font-normal text-green-500">Tiket</span></p>
                </div>
            </div>
            <div class="mt-4">
                {{-- Progress Bar Visual --}}
                <div class="w-full bg-slate-200 rounded-full h-4 overflow-hidden">
                    <div class="bg-gradient-to-r from-yellow-400 to-yellow-600 h-4 rounded-full transition-all duration-1000 ease-out"
                        style="width: {{ $perfPercentage }}%"></div>
                </div>
                <p class="text-xs text-slate-400 mt-2 font-medium italic text-right">
                    *Menghitung tiket dengan target selesai bulan
                    {{ Carbon\Carbon::parse($filterMonth)->translatedFormat('F Y') }}
                </p>
            </div>
        </div>
    </div>
</div>
