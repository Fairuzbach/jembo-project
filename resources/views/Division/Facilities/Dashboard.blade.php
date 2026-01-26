@section('browser_title', 'Facilities Dashboard')

<x-app-layout>
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
            // Data Gantt dari Service (sudah format {data:[], links:[]})
            gantt: @json($ganttData ?? ['data' => [], 'links' => []])
        };
    </script>
    <x-slot name="header">
        <div class="flex justify-between items-center -my-2">
            <h2 class="font-bold text-2xl text-[#1E3A5F] leading-tight uppercase tracking-wider flex items-center gap-4">
                <span
                    class="w-1 h-10 bg-gradient-to-b from-[#22C55E] to-emerald-400 inline-block shadow-lg rounded-full"></span>
                {{ __('Facilities Dashboard') }}
            </h2>

            <div class="flex items-center gap-4">
                <form method="GET" action="{{ route('fh.dashboard') }}" class="flex items-center gap-3">
                    <label class="text-xs text-slate-500 font-bold uppercase">Month:</label>
                    <input type="month" name="month" value="{{ $selectedMonth ?? '' }}"
                        class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition shadow-sm hover:shadow-md">
                    <button type="submit"
                        class="px-5 py-2.5 bg-gradient-to-br from-slate-600 to-slate-700 text-white rounded-xl text-sm font-bold shadow-md hover:shadow-lg hover:from-slate-700 hover:to-slate-800 transition-all duration-300 hover:scale-105 active:scale-95">Filter</button>
                </form>

                <div class="relative">
                    <button onclick="toggleExportMenu()"
                        class="bg-gradient-to-br from-[#3B82F6] via-blue-500 to-[#1E40AF] text-white px-6 py-2.5 rounded-xl font-bold text-sm uppercase shadow-lg hover:shadow-xl hover:from-[#2563EB] hover:to-[#1e3a8a] transition-all duration-300 flex items-center gap-2 border border-blue-400/50 hover:scale-105 active:scale-95">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Export
                    </button>
                    <div id="exportMenu"
                        class="hidden absolute right-0 top-full mt-3 w-60 bg-white rounded-2xl shadow-2xl z-50 border border-slate-100 overflow-hidden transform transition-all duration-200">
                        <div
                            class="px-5 py-4 bg-gradient-to-r from-blue-50 via-blue-50 to-blue-100 border-b border-blue-200/50">
                            <p class="text-xs font-bold text-blue-900 uppercase tracking-widest">Export Options</p>
                        </div>
                        <button onclick="exportToPDF(); toggleExportMenu();"
                            class="w-full text-left px-5 py-4 text-gray-800 hover:bg-gradient-to-r hover:from-red-50 hover:to-orange-50 transition-all duration-200 flex items-center gap-4 border-b border-slate-100 last:border-0 group active:bg-red-100">
                            <div
                                class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center group-hover:bg-red-200 transition-all shadow-sm">
                                <span class="text-xl">📄</span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">Export as PDF</p>
                                <p class="text-xs text-gray-500">Download analytics dashboard</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <style>
        /* Enhanced Input Styling */
        input[type="text"],
        input[type="email"],
        input[type="date"],
        input[type="month"],
        select {
            @apply transition-all duration-200 shadow-sm;
        }

        input:focus,
        select:focus {
            @apply shadow-md outline-none;
        }

        /* Smooth table scrolling */
        .gantt-table-container {
            position: relative;
        }

        /* Enhanced button transitions */
        button {
            @apply transition-all duration-200;
        }

        /* Counter card animation on load */
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .counter-card {
            animation: slideUp 0.6s ease-out backwards;
        }

        .counter-card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .counter-card:nth-child(2) {
            animation-delay: 0.2s;
        }

        .counter-card:nth-child(3) {
            animation-delay: 0.3s;
        }

        .counter-card:nth-child(4) {
            animation-delay: 0.4s;
        }

        .counter-card:nth-child(5) {
            animation-delay: 0.5s;
        }

        /* Smooth scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(226, 232, 240, 0.3);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.5);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.8);
        }

        tbody tr:hover>td:last-child>div {
            opacity: 1 !important;
        }
    </style>

    {{-- SCRIPTS --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js">
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="{{ asset('vendorDHTMLX/dhtmlxgantt.css') }}">
    <script src="{{ asset('vendorDHTMLX/dhtmlxgantt.js') }}"></script>

    @vite(['resources/js/app.js', 'resources/js/facilities/dashboard.js'])

    <div class="py-12 bg-[#F8FAFC]">
        <div id="dashboard-content" class="max-w-8xl mx-auto sm:px-6 lg:px-8 p-4 bg-[#F8FAFC]">

            {{-- 1. COUNTERS --}}
            <x-facilityDashboard.counter :countTotal="$countTotal" :countPending="$countPending" :countProgress="$countProgress" :countDone="$countDone" />

            {{-- 2. CHARTS GRID --}}

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                {{-- Chart Category --}}
                <x-facilityDashboard.chart-category />

                {{-- Chart Status --}}
                <x-facilityDashboard.chart-status />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                {{-- Chart Plant --}}
                <x-facilityDashboard.chart-plant />
                {{-- Chart Technician PIC --}}
                <x-facilityDashboard.chart-tech />
            </div>

            {{-- 3. GANTT CHART --}}
            <div class="bg-white p-4 rounded shadow overflow-hidden">
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-4">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                        <h3 class="font-bold text-gray-700">Project Timeline</h3>

                        {{-- Plant Filter Dropdown --}}
                        <div class="flex gap-2 bg-slate-50 rounded-lg p-1 border border-slate-200 flex-wrap">
                            <button type="button" onclick="filterByPlant('all')" id="plant-filter-all"
                                class="plant-filter-btn px-3 py-1.5 text-xs font-semibold rounded-md transition-all duration-200 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-sm">
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                Semua
                            </button>
                            @foreach ($chartPlantLabels ?? [] as $plant)
                                <button type="button" onclick="filterByPlant('{{ $plant }}')"
                                    id="plant-filter-{{ str_replace(' ', '-', strtolower($plant)) }}"
                                    class="plant-filter-btn px-3 py-1.5 text-xs font-semibold rounded-md transition-all duration-200 bg-white text-slate-700 border border-slate-200 hover:bg-slate-50">
                                    {{ $plant }}
                                </button>
                            @endforeach
                        </div>

                        {{-- Zoom Controls --}}
                        <div class="flex gap-2 bg-slate-50 rounded-lg p-1 border border-slate-200">
                            <button type="button" onclick="changeZoom('day')" id="zoom-fh-day"
                                class="zoom-btn-fh px-3 py-1.5 text-xs font-semibold rounded-md transition-all duration-200 bg-white text-slate-700 border border-slate-200 hover:bg-slate-50">
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Hari
                            </button>
                            <button type="button" onclick="changeZoom('week')" id="zoom-fh-week"
                                class="zoom-btn-fh px-3 py-1.5 text-xs font-semibold rounded-md transition-all duration-200 bg-white text-slate-700 border border-slate-200 hover:bg-slate-50">
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Minggu
                            </button>
                            <button type="button" onclick="changeZoom('month')" id="zoom-fh-month"
                                class="zoom-btn-fh px-3 py-1.5 text-xs font-semibold rounded-md transition-all duration-200 bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-sm">
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Bulan
                            </button>
                        </div>
                    </div>

                    <div class="text-xs text-gray-500 space-x-2 whitespace-nowrap">
                        <span class="inline-block w-3 h-3 bg-emerald-500 rounded-sm"></span> Done
                        <span class="inline-block w-3 h-3 bg-blue-500 rounded-sm"></span> Process
                        <span class="inline-block w-3 h-3 bg-yellow-500 rounded-sm"></span> Pending
                    </div>
                </div>

                {{-- Container Wajib DHTMLX --}}
                <div id="gantt_here" style='width:100%; height:500px;'></div>
            </div>

        </div>
    </div>
</x-app-layout>
