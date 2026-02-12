@section('browser_title', 'GA Dashboard')

<x-app-layout>
    {{-- HEADER --}}
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-extrabold text-xl text-slate-900 leading-tight uppercase flex items-center gap-3">
                <span class="w-3 h-8 bg-yellow-400 rounded-sm inline-block"></span>
                {{ __('Dashboard Statistik') }}
            </h2>

            <div class="flex gap-2">
                {{-- TOMBOL DOWNLOAD PDF --}}
                <button onclick="exportToPDF()"
                    class="bg-red-600 text-white hover:bg-red-700 font-bold py-2 px-4 rounded text-sm uppercase tracking-wide transition flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    Export PDF
                </button>

                <a href="{{ route('ga.index') }}"
                    class="bg-slate-900 text-white hover:bg-slate-800 font-bold py-2 px-4 rounded text-sm uppercase tracking-wide transition">
                    &larr; Kembali ke Data
                </a>
            </div>
        </div>
    </x-slot>

    {{-- LOAD LIBRARY (CDN) --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@2.1.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js">
    </script>

    {{-- Library PDF Generator --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.css">
    <script src="https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.js"></script>


    {{-- LOAD RESOURCES --}}

    {{-- DATA INJECTION (PHP ke JS Global) --}}
    {{-- Ini digunakan oleh file index.js untuk chart statistik lainnya (Pie, Bar Chart Dept, dll) --}}
    <script>
        window.gaDashboardData = {
            performance: {
                percentage: {{ $perfPercentage }},
                total: {{ $perfTotal }},
                completed: {{ $perfCompleted }}
            },
            loc: {
                labels: @json($chartLocLabels),
                values: @json($chartLocValues)
            },
            dept: {
                labels: @json($chartDeptLabels),
                values: @json($chartDeptValues)
            },
            param: {
                labels: @json($chartParamLabels),
                values: @json($chartParamValues)
            },
            bobot: {
                labels: @json($chartBobotLabels),
                values: @json($chartBobotValues)
            },
            meta: {
                filterMonth: "{{ $filterMonth }}",
                // Safe navigation operator (?.) untuk menghindari error jika data kosong
                defaultStartDateFilename: "{{ $workOrders->min('created_at')?->format('Y-m-d') ?? date('Y-m-d') }}",
                defaultStartDateHeader: "{{ $workOrders->min('created_at')?->translatedFormat('d F Y') ?? date('d F Y') }}"
            }
        };
        window.gaGanttData = @json($tasks ?? ['data' => [], 'links' => []]);

        // console.log('Gantt Data Loaded:', window.gaGanttData);
    </script>
    @vite(['resources/js/app.js'])
    {{-- KONTEN UTAMA --}}
    <div class="py-12 bg-slate-50">

        <div id="dashboard-content" class="max-w-8xl mx-auto sm:px-6 lg:px-8 p-4 bg-slate-50">

            {{-- 1. STATISTIK CARDS --}}
            <x-gaDashboard.stats-card :count-total="$countTotal" :count-in-progress="$countInProgress" :count-completed="$countCompleted" :count-rejected="$countRejected"
                :count-cancelled="$countCancelled" :count-pending="$countPending" :count-waiting-approval-g-a="$countWaitingApprovalGA" />
            <x-gaDashboard.date-range />

            {{-- 2. GRID GRAFIK --}}
            <x-gaDashboard.graph-grid :filterMonth="$filterMonth" :perfPercentage="$perfPercentage" :perfTotal="$perfTotal" :perfCompleted="$perfCompleted" />

            {{-- 3. PIE CHART & DATE RANGE --}}

            <x-gaDashboard.pie-chart />

            {{-- 4. GANTT CHART  --}}

            <x-gaDashboard.gantt-chart :tasks="$tasks" />

        </div>
    </div>
</x-app-layout>
