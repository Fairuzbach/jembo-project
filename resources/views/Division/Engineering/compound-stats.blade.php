<x-app-layout>

    @push('styles')
        <style>
            .hide-scrollbar::-webkit-scrollbar {
                display: none;
            }

            .hide-scrollbar {
                -ms-overflow-style: none;
                scrollbar-width: none;
            }
        </style>
    @endpush

    <div class="py-4 sm:py-8">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">

            {{-- ══ HEADER ══ --}}
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight leading-tight">
                        Statistik Compound
                    </h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        Tren pH · Konsentrasi · Suhu
                        @if ($plant)
                            <span class="text-slate-300 mx-1">•</span>
                            <span class="font-semibold text-indigo-500">{{ $plant }}</span>
                        @endif
                        @if (count($labels) > 0)
                            <span class="text-slate-300 mx-1">•</span>
                            <span class="text-slate-400">{{ count($labels) }} titik data</span>
                        @endif
                    </p>
                </div>
                <a href="{{ route('eng.index') }}"
                    class="flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-600 rounded-lg text-xs font-bold transition-colors shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span class="hidden sm:inline">Dashboard</span>
                </a>
            </div>

            {{-- ══ FILTER PANEL ══ --}}
            <div
                class="bg-white rounded-xl border border-slate-200 shadow-sm mb-5 divide-y divide-slate-100 overflow-hidden">

                {{-- Baris 1: Plant + Mode --}}
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-2.5">
                    <span
                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest shrink-0 w-14">Area</span>
                    <div class="flex bg-slate-100 rounded-lg p-0.5 gap-0.5">
                        <a href="{{ route('eng.compound.stats', ['plant' => 'Plant A', 'machine' => 'all', 'filter' => $filter, 'mode' => $mode]) }}"
                            class="px-3.5 py-1.5 rounded-md text-[11px] font-black transition-all
                               {{ $plant == 'Plant A' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                            Plant A
                        </a>
                        <a href="{{ route('eng.compound.stats', ['plant' => 'Autowire', 'machine' => 'all', 'filter' => $filter, 'mode' => $mode]) }}"
                            class="px-3.5 py-1.5 rounded-md text-[11px] font-black transition-all
                               {{ $plant == 'Autowire' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                            Autowire
                        </a>
                    </div>

                    <div class="ml-auto flex bg-slate-100 rounded-lg p-0.5 gap-0.5">
                        <a href="{{ route('eng.compound.stats', ['plant' => $plant, 'filter' => $filter, 'mode' => 'avg', 'machine' => $machineId]) }}"
                            class="px-3 py-1.5 rounded-md text-[11px] font-bold transition-all
                               {{ $mode == 'avg' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                            AVG
                        </a>
                        <a href="{{ route('eng.compound.stats', ['plant' => $plant, 'filter' => $filter, 'mode' => 'raw', 'machine' => $machineId]) }}"
                            class="px-3 py-1.5 rounded-md text-[11px] font-bold transition-all
                               {{ $mode == 'raw' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                            RAW
                        </a>
                    </div>
                </div>

                {{-- Baris 2: Machine (Plant A only) --}}
                @if ($plant === 'Plant A')
                    <div class="flex items-center gap-3 px-4 py-2.5">
                        <span
                            class="text-[10px] font-black text-slate-400 uppercase tracking-widest shrink-0 w-14">Mesin</span>
                        <div class="flex overflow-x-auto hide-scrollbar gap-1.5 snap-x py-0.5">
                            @foreach ($plantAMachines as $id => $name)
                                <a href="{{ route('eng.compound.stats', ['plant' => $plant, 'machine' => $id, 'filter' => $filter, 'mode' => $mode]) }}"
                                    class="snap-start shrink-0 px-3 py-1.5 rounded-lg text-[11px] font-bold border transition-all whitespace-nowrap
                                       {{ $machineId == $id
                                           ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
                                           : 'bg-white text-slate-500 border-slate-200 hover:border-slate-300 hover:text-slate-700' }}">
                                    {{ $name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Baris 3: Periode --}}
                <div class="flex items-center gap-3 px-4 py-2.5">
                    <span
                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest shrink-0 w-14">Periode</span>

                    @if ($mode === 'avg')
                        <div class="flex overflow-x-auto hide-scrollbar gap-1.5 snap-x py-0.5">
                            @foreach (['weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'quarterly' => 'Kuartalan', 'semester' => 'Semesteran', 'yearly' => 'Tahunan'] as $key => $label)
                                <a href="{{ route('eng.compound.stats', ['plant' => $plant, 'filter' => $key, 'mode' => $mode, 'machine' => $machineId]) }}"
                                    class="snap-start shrink-0 px-3 py-1.5 rounded-lg text-[11px] font-bold border transition-all whitespace-nowrap
                                       {{ $filter == $key
                                           ? 'bg-slate-800 text-white border-slate-800 shadow-sm'
                                           : 'bg-white text-slate-500 border-slate-200 hover:border-slate-300 hover:text-slate-700' }}">
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    @else
                        <span
                            class="text-[11px] text-amber-700 bg-amber-50 px-3 py-1.5 rounded-lg border border-amber-200 font-medium">
                            Mode RAW — semua titik data harian ditampilkan tanpa dirata-rata.
                        </span>
                    @endif
                </div>

            </div>

            {{-- ══ CHART SECTION ══ --}}
            <div x-data="{ activeChart: 'ph' }">

                {{-- Tab switcher --}}
                <div class="flex items-center gap-2 mb-3">
                    <div class="flex bg-white border border-slate-200 rounded-xl p-1 gap-1 shadow-sm">
                        <button @click="activeChart = 'ph'"
                            :class="activeChart === 'ph'
                                ?
                                'bg-blue-600 text-white shadow-sm' :
                                'text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
                            class="flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-[11px] font-bold transition-all">
                            <span class="w-2 h-2 rounded-full"
                                :class="activeChart === 'ph' ? 'bg-white' : 'bg-blue-400'"></span>
                            pH Level
                        </button>
                        <button @click="activeChart = 'kons'"
                            :class="activeChart === 'kons'
                                ?
                                'bg-violet-600 text-white shadow-sm' :
                                'text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
                            class="flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-[11px] font-bold transition-all">
                            <span class="w-2 h-2 rounded-full"
                                :class="activeChart === 'kons' ? 'bg-white' : 'bg-violet-400'"></span>
                            Konsentrasi
                        </button>
                        <button @click="activeChart = 'temp'"
                            :class="activeChart === 'temp'
                                ?
                                'bg-rose-600 text-white shadow-sm' :
                                'text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
                            class="flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-[11px] font-bold transition-all">
                            <span class="w-2 h-2 rounded-full"
                                :class="activeChart === 'temp' ? 'bg-white' : 'bg-rose-400'"></span>
                            Temperatur
                        </button>

                    </div>

                    @if (count($labels) === 0)
                        <span
                            class="text-[10px] bg-amber-100 text-amber-700 px-2.5 py-1.5 rounded-lg font-bold border border-amber-200">
                            Belum ada data
                        </span>
                    @endif
                </div>

                {{-- ── pH Chart ── --}}
                <div x-show="activeChart === 'ph'" x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <div
                            class="flex items-center justify-between px-4 py-3 border-b border-slate-100 bg-slate-50/60">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-md bg-blue-100 flex items-center justify-center">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                </div>
                                <div>
                                    <h3 class="text-xs font-extrabold text-slate-800">Tren pH Level</h3>
                                    <p class="text-[10px] text-slate-400">Rentang normal: 6 – 10</p>
                                </div>
                            </div>
                            <span
                                class="text-[10px] text-slate-400 bg-white px-2 py-1 rounded-lg border border-slate-200 font-mono hidden sm:block">
                                {{ count($labels) }} data point
                            </span>
                        </div>
                        <div class="p-3 sm:p-5">
                            @if (count($labels) > 0)
                                <div class="relative w-full h-[260px] sm:h-[380px]">
                                    <canvas id="phChart"></canvas>
                                </div>
                            @else
                                @include('Division.Engineering.partials._empty-chart')
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ── Konsentrasi Chart ── --}}
                <div x-show="activeChart === 'kons'" style="display:none"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <div
                            class="flex items-center justify-between px-4 py-3 border-b border-slate-100 bg-slate-50/60">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-md bg-violet-100 flex items-center justify-center">
                                    <span class="w-2 h-2 rounded-full bg-violet-500"></span>
                                </div>
                                <div>
                                    <h3 class="text-xs font-extrabold text-slate-800">Tren Konsentrasi (%)</h3>
                                    <p class="text-[10px] text-slate-400">Rentang normal: 0 – 15%</p>
                                </div>
                            </div>
                            <span
                                class="text-[10px] text-slate-400 bg-white px-2 py-1 rounded-lg border border-slate-200 font-mono hidden sm:block">
                                {{ count($labels) }} data point
                            </span>
                        </div>
                        <div class="p-3 sm:p-5">
                            @if (count($labels) > 0)
                                <div class="relative w-full h-[260px] sm:h-[380px]">
                                    <canvas id="konsChart"></canvas>
                                </div>
                            @else
                                @include('Division.Engineering.partials._empty-chart')
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ── Temperatur Chart ── --}}
                <div x-show="activeChart === 'temp'" style="display:none"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <div
                            class="flex items-center justify-between px-4 py-3 border-b border-slate-100 bg-slate-50/60">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-md bg-rose-100 flex items-center justify-center">
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                </div>
                                <div>
                                    <h3 class="text-xs font-extrabold text-slate-800">Tren Temperatur (°C)</h3>
                                    <p class="text-[10px] text-slate-400">Rentang normal: 30 – 60°C</p>
                                </div>
                            </div>
                            <span
                                class="text-[10px] text-slate-400 bg-white px-2 py-1 rounded-lg border border-slate-200 font-mono hidden sm:block">
                                {{ count($labels) }} data point
                            </span>
                        </div>
                        <div class="p-3 sm:p-5">
                            @if (count($labels) > 0)
                                <div class="relative w-full h-[260px] sm:h-[380px]">
                                    <canvas id="tempChart"></canvas>
                                </div>
                            @else
                                @include('Division.Engineering.partials._empty-chart')
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Empty state partial: resources/views/Division/Engineering/partials/_empty-chart.blade.php --}}
    {{-- Isi file tersebut:
<div class="flex flex-col items-center justify-center h-48 text-slate-300">
    <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
    </svg>
    <p class="text-xs font-semibold text-slate-400">Belum ada data untuk filter ini</p>
    <p class="text-[10px] text-slate-300 mt-1">Coba ubah periode atau pilih mesin lain</p>
</div>
--}}

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const labels = @json($labels);
            const stdValues = @json($stdValues);
            const machineId = @json($machineId);
            const isBakB = machineId == 2;

            if (!labels.length) return;

            const drawPhData = @json($drawPhData);
            const annPhData = @json($annPhData);
            const annPhData2 = @json($annPhData2);
            const drawKonsData = @json($drawKonsData);
            const annKonsData = @json($annKonsData);
            const annKonsData2 = @json($annKonsData2);
            const drawTempData = @json($drawTempData);
            const annTempData = @json($annTempData);
            const annTempData2 = @json($annTempData2);

            // ── Helpers ──────────────────────────────────────────
            const line = (label, data, color, dash = []) => ({
                label,
                data,
                borderColor: color,
                backgroundColor: 'transparent',
                borderWidth: 2,
                borderDash: dash,
                tension: 0.35,
                fill: false,
                pointBackgroundColor: '#fff',
                pointBorderColor: color,
                pointRadius: data.length > 30 ? 2 : 4,
                pointHoverRadius: 6,
            });

            const stdLines = (label, stdObj, color) => {
                if (!stdObj || stdObj.min === null) return [];
                const base = {
                    borderColor: color,
                    borderWidth: 1.5,
                    borderDash: [6, 4],
                    fill: false,
                    pointRadius: 0,
                    pointHitRadius: 0,
                    tension: 0,
                };
                const result = [{
                    ...base,
                    label: `Min Std ${label} (${stdObj.min})`,
                    data: labels.map(() => stdObj.min),
                }];
                if (stdObj.max !== null && stdObj.max !== stdObj.min) {
                    result.push({
                        ...base,
                        label: `Max Std ${label} (${stdObj.max})`,
                        data: labels.map(() => stdObj.max),
                    });
                }
                return result;
            };

            const opts = (yLabel, yMin, yMax) => ({
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 350
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 8,
                            boxHeight: 8,
                            font: {
                                size: 10,
                                weight: 'bold'
                            },
                            usePointStyle: true,
                            padding: 14,
                            color: '#64748b',
                        },
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(15,23,42,0.92)',
                        titleFont: {
                            size: 11,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 11
                        },
                        padding: 10,
                        cornerRadius: 8,
                        caretSize: 4,
                    },
                },
                scales: {
                    y: {
                        suggestedMin: yMin,
                        suggestedMax: yMax,
                        grid: {
                            color: '#f1f5f9',
                            borderDash: [4, 4]
                        },
                        title: {
                            display: true,
                            text: yLabel,
                            font: {
                                size: 10,
                                weight: 'bold'
                            },
                            color: '#94a3b8',
                        },
                        ticks: {
                            font: {
                                size: 10
                            },
                            color: '#94a3b8'
                        },
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            },
                            color: '#94a3b8',
                            maxRotation: 40,
                            minRotation: 40,
                            maxTicksLimit: 20,
                        },
                    },
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                },
            });

            // ── pH ───────────────────────────────────────────────
            new Chart(document.getElementById('phChart'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        line('Draw', drawPhData, '#2563eb'),
                        line('Ann — Bak A', annPhData, '#059669'),
                        ...(isBakB ? [line('Ann — Bak B', annPhData2, '#4f46e5', [4, 3])] : []),
                        ...stdLines('Draw', stdValues.draw_ph, '#93c5fd'),
                        ...stdLines('Ann', stdValues.ann_ph, '#6ee7b7'),
                    ],
                },
                options: opts('Nilai pH', 6, 10),
            });

            // ── Konsentrasi ──────────────────────────────────────
            new Chart(document.getElementById('konsChart'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        line('Draw', drawKonsData, '#7c3aed'),
                        line('Ann — Bak A', annKonsData, '#d97706'),
                        ...(isBakB ? [line('Ann — Bak B', annKonsData2, '#be185d', [4, 3])] : []),
                        ...stdLines('Draw', stdValues.draw_kons, '#c4b5fd'),
                        ...stdLines('Ann', stdValues.ann_kons, '#fcd34d'),
                    ],
                },
                options: opts('Konsentrasi (%)', 0, 15),
            });

            // ── Temperatur ───────────────────────────────────────
            new Chart(document.getElementById('tempChart'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        line('Draw', drawTempData, '#dc2626'),
                        line('Ann — Bak A', annTempData, '#ea580c'),
                        ...(isBakB ? [line('Ann — Bak B', annTempData2, '#0369a1', [4, 3])] : []),
                        ...stdLines('Draw', stdValues.draw_temp, '#fca5a5'),
                        ...stdLines('Ann', stdValues.ann_temp, '#fdba74'),
                    ],
                },
                options: opts('Temperatur (°C)', 30, 60),
            });
        });
    </script>

</x-app-layout>
