@section('browser_title', 'Facilities Dashboard')

<x-app-layout>
    {{-- 1. LOAD LIBRARY EKSTERNAL (CDN) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dhtmlx-gantt@8.0.6/codebase/dhtmlxgantt.css">
    <script src="https://cdn.jsdelivr.net/npm/dhtmlx-gantt@8.0.6/codebase/dhtmlxgantt.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- 2. DATA INJECTION (PHP -> JS Global Variable) --}}
    <script>
        window.facilitiesData = {
            stats: {
                catLabels: @json($chartCatLabels ?? []),
                catValues: @json($chartCatValues ?? []),
                statusLabels: @json($chartStatusLabels ?? []),
                statusValues: @json($chartStatusValues ?? []),
                plantLabels: @json($chartPlantLabels ?? []),
                plantValues: @json($chartPlantValues ?? []),
                techLabels: @json($chartTechLabels ?? []),
                techValues: @json($chartTechValues ?? [])
            },
            gantt: @json($ganttData ?? ['data' => [], 'links' => []])
        };
    </script>

    @vite(['resources/js/app.js', 'resources/css/app.css'])

    <x-slot name="header">
        <div class="flex justify-between items-center -my-2">
            <h2 class="font-bold text-2xl text-[#1E3A5F] leading-tight uppercase tracking-wider flex items-center gap-4">
                <span
                    class="w-1 h-10 bg-gradient-to-b from-[#22C55E] to-emerald-400 inline-block shadow-lg rounded-full"></span>
                {{ __('Facilities Dashboard') }}
            </h2>
            <div class="flex items-center gap-4">
                {{-- Filter Month --}}
                <form method="GET" action="{{ route('fh.dashboard') }}" class="flex items-center gap-3">
                    <label class="text-xs text-slate-500 font-bold uppercase">Month:</label>
                    <input type="month" name="month" value="{{ $selectedMonth ?? '' }}"
                        class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition shadow-sm hover:shadow-md">
                    <button type="submit"
                        class="px-5 py-2.5 bg-gradient-to-br from-slate-600 to-slate-700 text-white rounded-xl text-sm font-bold shadow-md hover:shadow-lg transition-all duration-300 hover:scale-105">Filter</button>
                </form>
                {{-- Export Button --}}
                <div class="relative">
                    <button onclick="toggleExportMenu()"
                        class="bg-gradient-to-br from-[#3B82F6] via-blue-500 to-[#1E40AF] text-white px-6 py-2.5 rounded-xl font-bold text-sm uppercase shadow-lg hover:shadow-xl transition-all duration-300 flex items-center gap-2 border border-blue-400/50 hover:scale-105 active:scale-95">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Export
                    </button>
                    <div id="exportMenu"
                        class="hidden absolute right-0 top-full mt-3 w-60 bg-white rounded-2xl shadow-2xl z-50 border border-slate-100 overflow-hidden transform transition-all duration-200">
                        <button onclick="exportToPDF(); toggleExportMenu();"
                            class="w-full text-left px-5 py-4 text-gray-800 hover:bg-gray-50 flex items-center gap-4 border-b border-slate-100 last:border-0 group active:bg-red-100">
                            <span class="text-xl">📄</span>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">Export as PDF</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    {{-- 3. STYLE CSS (Agar Chart Tidak Blank) --}}
    <style>
        #gantt_chart_robust {
            width: 100%;
            height: 600px !important;
            background: #fff;
            border: 1px solid #e2e8f0;
            box-sizing: content-box !important;
        }

        #gantt_chart_robust * {
            box-sizing: content-box !important;
        }

        .gantt_task_line.gantt-task-completed {
            background-color: #10b981 !important;
            border: 1px solid #059669 !important;
        }

        .gantt_task_line.gantt-task-completed .gantt_task_progress {
            background-color: #059669 !important;
        }

        .gantt_task_line.gantt-task-in_progress {
            background-color: #3b82f6 !important;
            border: 1px solid #2563eb !important;
        }

        .gantt_task_line.gantt-task-in_progress .gantt_task_progress {
            background-color: #2563eb !important;
        }

        .gantt_task_line.gantt-task-pending,
        .gantt_task_line.gantt-task-waiting_approval {
            background-color: #f59e0b !important;
            border: 1px solid #d97706 !important;
        }

        .gantt_task_line.gantt-task-rejected {
            background-color: #ef4444 !important;
            border: 1px solid #dc2626 !important;
        }

        .gantt_tooltip {
            background: #1f2937 !important;
            color: #f3f4f6 !important;
            border-radius: 6px;
            padding: 10px;
            z-index: 9999;
        }

        input[type="month"],
        select {
            @apply transition-all duration-200 shadow-sm;
        }

        button {
            @apply transition-all duration-200;
        }
    </style>

    <div class="py-12 bg-[#F8FAFC]">
        <div id="dashboard-content" class="max-w-8xl mx-auto sm:px-6 lg:px-8 p-4 bg-[#F8FAFC]">

            <x-facilityDashboard.counter :countTotal="$countTotal" :countPending="$countPending" :countProgress="$countProgress" :countDone="$countDone" />

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <x-facilityDashboard.chart-category />
                <x-facilityDashboard.chart-status />
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <x-facilityDashboard.chart-plant />
                <x-facilityDashboard.chart-tech />
            </div>

            <div class="bg-white p-4 rounded shadow overflow-hidden">
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-4">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                        <h3 class="font-bold text-gray-700">Project Timeline</h3>
                        <div class="flex gap-2 bg-slate-50 rounded-lg p-1 border border-slate-200 flex-wrap">
                            <button type="button" onclick="robust_filterByPlant('all')" id="plant-filter-all"
                                class="plant-filter-btn px-3 py-1.5 text-xs font-semibold rounded-md bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-sm">Semua</button>
                            @foreach ($chartPlantLabels ?? [] as $plant)
                                <button type="button" onclick="robust_filterByPlant('{{ $plant }}')"
                                    id="plant-filter-{{ \Illuminate\Support\Str::slug($plant) }}"
                                    class="plant-filter-btn px-3 py-1.5 text-xs font-semibold rounded-md bg-white text-slate-700 border border-slate-200 hover:bg-slate-50">{{ $plant }}</button>
                            @endforeach
                        </div>
                        <div class="flex gap-2 bg-slate-50 rounded-lg p-1 border border-slate-200">
                            <button onclick="robust_changeZoom('day')"
                                class="zoom-btn-fh px-3 py-1.5 text-xs bg-white border rounded">Hari</button>
                            <button onclick="robust_changeZoom('week')"
                                class="zoom-btn-fh px-3 py-1.5 text-xs bg-white border rounded">Minggu</button>
                            <button onclick="robust_changeZoom('month')"
                                class="zoom-btn-fh px-3 py-1.5 text-xs bg-blue-500 text-white rounded">Bulan</button>
                        </div>
                    </div>
                </div>

                {{-- Container Chart --}}
                <div id="gantt_chart_robust"></div>
            </div>
        </div>
    </div>

    {{-- 4. PANGGIL PARTIAL SCRIPT DI SINI --}}
    @include('Division.Facilities.partials.gantt_script')

</x-app-layout>
