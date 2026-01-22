@section('browser_title', 'Facilities Dashboard')

<x-app-layout>
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

    <div class="py-12 bg-[#F8FAFC]">
        <div id="dashboard-content" class="max-w-8xl mx-auto sm:px-6 lg:px-8 p-4 bg-[#F8FAFC]">

            {{-- 1. COUNTERS --}}
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
                    <p class="text-4xl font-bold text-indigo-600 mt-2">{{ $completionPct }}%</p>
                    <p class="text-xs text-slate-400 mt-3">period: <span
                            class="font-semibold">{{ $selectedMonth ?? 'All' }}</span></p>
                </div>
            </div>

            {{-- 2. CHARTS GRID --}}

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

                {{-- Chart Category --}}
                <div
                    class="bg-white p-7 rounded-2xl shadow-md border border-slate-100 hover:shadow-lg transition-all duration-300 overflow-hidden">
                    <h4 class="text-sm font-bold text-[#1E3A5F] uppercase mb-5 tracking-wider">Request by Category</h4>
                    <div class="h-64"><canvas id="catChart"></canvas></div>
                </div>

                {{-- Chart Status --}}
                <div
                    class="bg-white p-7 rounded-2xl shadow-md border border-slate-100 hover:shadow-lg transition-all duration-300 overflow-hidden">
                    <h4 class="text-sm font-bold text-[#1E3A5F] uppercase mb-5 tracking-wider">Workload Status</h4>
                    <div class="h-64"><canvas id="statusChart"></canvas></div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

                {{-- Chart Plant --}}
                <div
                    class="bg-white p-7 rounded-2xl shadow-md border border-slate-100 hover:shadow-lg transition-all duration-300 overflow-hidden">
                    <h4 class="text-sm font-bold text-[#1E3A5F] uppercase mb-5 tracking-wider">Requests by Plant</h4>
                    <div class="h-64"><canvas id="plantChart"></canvas></div>
                </div>

                {{-- Chart Technician PIC --}}
                <div
                    class="bg-white p-7 rounded-2xl shadow-md border border-slate-100 hover:shadow-lg transition-all duration-300 overflow-hidden">
                    <h4 class="text-sm font-bold text-purple-600 uppercase mb-5 tracking-wider">Technician Assignments
                        (PIC)</h4>
                    <div class="h-64"><canvas id="techChart"></canvas></div>
                </div>
            </div>

            {{-- 3. GANTT CHART --}}
            <div class="bg-white p-6 rounded-sm shadow-sm border-t-4 border-[#22C55E] mb-8">
                <h4 class="text-sm font-bold text-[#1E3A5F] uppercase mb-4">Work Timeline (Gantt Chart)</h4>

                <div style="border: 1px solid #e5e7eb; border-radius: 4px; overflow: hidden;">
                    @php
                        // 1. Ambil semua tanggal dari data
                        $allDates = collect($ganttData)->map(fn($g) => [$g['start'], $g['end']])->flatten();
                        $today = strtotime('today'); // Integer Timestamp hari ini (00:00:00)

                        // 2. Tentukan Min & Max awal berdasarkan Data Tiket
                        if ($allDates->isEmpty()) {
                            $minDate = $today;
                            $maxDate = strtotime('+7 days', $today);
                        } else {
                            $minDate = strtotime($allDates->min());
                            $maxDate = strtotime($allDates->max());
                        }

                        // [FIX UTAMA] Perluas rentang agar SELALU mencakup Hari Ini
                        if ($today < $minDate) {
                            $minDate = $today;
                        }
                        if ($today > $maxDate) {
                            $maxDate = $today;
                        }

                        // [OPTIONAL] Tambahkan Buffer (Jarak) 3 hari sebelum & sesudah agar lebih rapi
                        $minDate = strtotime('-3 days', $minDate);
                        $maxDate = strtotime('+3 days', $maxDate);

                        // 3. Hitung Durasi Total untuk Skala Grafik
                        $totalDuration = max(1, ($maxDate - $minDate) / 86400);
                        $daysDiff = (int) $totalDuration;

                        // 4. Hitung Posisi Persentase Hari Ini
                        $todayPercent = 0;

                        $todayPercent = (($today - $minDate) / ($maxDate - $minDate)) * 100;
                    @endphp

                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: #f3f4f6; border-bottom: 2px solid #d1d5db;">
                                <th
                                    style="padding: 10px 12px; text-align: left; min-width: 100px; font-weight: 600; color: #1E3A5F; border-right: 1px solid #e5e7eb; font-size: 13px;">
                                    Ticket</th>
                                <th
                                    style="padding: 10px 12px; text-align: left; min-width: 130px; font-weight: 600; color: #1E3A5F; border-right: 1px solid #e5e7eb; font-size: 13px;">
                                    Duration</th>
                                <th style="padding: 10px 12px; font-weight: 600; color: #1E3A5F; font-size: 13px;">
                                    <div
                                        style="position: relative; min-width: 900px; display: flex; justify-content: space-between; padding: 0 4px; font-size: 11px;">
                                        @for ($i = 0; $i <= $daysDiff; $i += max(1, intdiv($daysDiff, 12)))
                                            @php
                                                $dateAtI = $minDate + $i * 86400;
                                                $formatted = date('M d', $dateAtI);
                                            @endphp
                                            <span>{{ $formatted }}</span>
                                        @endfor

                                        {{-- TODAY MARKER (FULL HEIGHT) --}}
                                        @if ($today >= $minDate && $today <= $maxDate)
                                            <div
                                                style="
                                                position: absolute; 
                                                left: {{ $todayPercent }}%; 
                                                top: 20px; 
                                                height: 5000px; /* Panjangkan ke bawah */
                                                width: 2px; 
                                                background-color: rgba(239, 68, 68, 0.3); /* Merah Transparan */
                                                z-index: 0; 
                                                pointer-events: none; /* Agar bisa diklik tembus */
                                                border-left: 1px dashed rgba(239, 68, 68, 0.8);">

                                                {{-- Segitiga Penunjuk (Kepala) --}}
                                                <div
                                                    style="
                                                    position: absolute; 
                                                    bottom: 100%; 
                                                    left: 50%; 
                                                    transform: translateX(-50%); 
                                                    width: 0; 
                                                    height: 0; 
                                                    border-left: 5px solid transparent; 
                                                    border-right: 5px solid transparent; 
                                                    border-top: 6px solid #ef4444;">
                                                </div>

                                                {{-- Label 'TODAY' Kecil (Opsional) --}}
                                                <div
                                                    style="
                                                    position: absolute; 
                                                    bottom: 100%; 
                                                    left: 50%; 
                                                    transform: translate(-50%, -8px); 
                                                    font-size: 9px; 
                                                    font-weight: bold; 
                                                    color: #ef4444;
                                                    background: white;
                                                    padding: 0 2px;">
                                                    TODAY
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($ganttData as $index => $item)
                                @php
                                    $startDate = strtotime($item['start']);
                                    $endDate = strtotime($item['end']);
                                    // Cegah error division by zero jika daysDiff aneh
                                    $safeDaysDiff = $daysDiff > 0 ? $daysDiff : 1;

                                    $barStart = (($startDate - $minDate) / 86400 / $safeDaysDiff) * 100;
                                    $barWidth = ((($endDate - $startDate) / 86400 + 1) / $safeDaysDiff) * 100;
                                @endphp
                                <tr style="border-bottom: 1px solid #e5e7eb; height: 45px; position: relative;"
                                    class="gantt-row" onmouseover="showGanttTooltip(this)"
                                    onmouseout="hideGanttTooltip(this)">

                                    <td
                                        style="padding: 8px 12px; border-right: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 13px;">
                                        {{ $item['ticket'] }}
                                    </td>

                                    <td
                                        style="padding: 8px 12px; border-right: 1px solid #e5e7eb; font-size: 12px; color: #666;">
                                        {{ $item['start'] }}<br>
                                        <span style="font-size: 11px; color: #999;">{{ $item['duration'] }}d</span>
                                    </td>

                                    <td style="padding: 8px 12px; position: relative; min-width: 900px;">
                                        <div
                                            style="position: relative; height: 30px; background-color: #f9fafb; border-radius: 3px; overflow: hidden;">
                                            <div style="position: absolute; left: {{ $barStart }}%; width: {{ $barWidth }}%; height: 100%; background-color: {{ $ganttColors[$index] ?? '#999' }}; border-radius: 3px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: filter 0.2s; filter: brightness(1);"
                                                onmouseover="this.style.filter='brightness(1.15)'"
                                                onmouseout="this.style.filter='brightness(1)'">
                                                <span
                                                    style="color: white; font-size: 12px; font-weight: 600; text-shadow: 0 1px 2px rgba(0,0,0,0.2); white-space: nowrap; padding: 0 4px; overflow: hidden; text-overflow: ellipsis;">
                                                    {{ ucfirst(substr($item['status'], 0, 4)) }} |
                                                    {{ $item['start'] }}
                                                </span>
                                            </div>

                                            @if ($today >= $minDate && $today <= $maxDate)
                                                <div
                                                    style="position: absolute; left: {{ $todayPercent }}%; top: 0; width: 2px; height: 100%; background-color: rgba(239, 68, 68, 0.3); z-index: 5;">
                                                </div>
                                            @endif
                                        </div>

                                        <div class="gantt-tooltip"
                                            style="display: none; position: absolute; left: {{ $barStart + $barWidth / 2 }}%; bottom: 110%; transform: translateX(-50%); background-color: #1f2937; color: white; padding: 10px 12px; border-radius: 5px; font-size: 12px; z-index: 100; box-shadow: 0 4px 12px rgba(0,0,0,0.3); border: 1px solid #374151; pointer-events: none; white-space: nowrap;">
                                            <div style="font-weight: 700; margin-bottom: 5px;">{{ $item['ticket'] }}
                                            </div>
                                            <div style="font-size: 11px; color: #d1d5db; margin-bottom: 3px;">Status:
                                                {{ ucfirst(str_replace('_', ' ', $item['status'])) }}</div>
                                            <div style="font-size: 11px; color: #d1d5db; margin-bottom: 3px;">
                                                {{ $item['start'] }} → {{ $item['end'] }} ({{ $item['duration'] }}d)
                                            </div>
                                            <div style="font-size: 11px; color: #d1d5db; margin-bottom: 3px;">Plant:
                                                {{ $item['plant'] }}</div>
                                            <div style="font-size: 11px; color: #d1d5db;">Machine:
                                                {{ $item['machine_name'] }}</div>
                                            <div style="font-size: 11px; color: #d1d5db;">Category:
                                                {{ $item['category'] }}</div>
                                            <div
                                                style="position: absolute; top: 100%; left: 50%; transform: translateX(-50%); width: 0; height: 0; border-left: 6px solid transparent; border-right: 6px solid transparent; border-top: 6px solid #1f2937;">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3"
                                        style="padding: 20px; text-align: center; color: #999; font-size: 13px;">
                                        No timeline data available
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 16px; display: flex; gap: 24px; font-size: 12px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 14px; height: 14px; background-color: #10B981; border-radius: 2px;"></div>
                        <span>Completed</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 14px; height: 14px; background-color: #2563EB; border-radius: 2px;"></div>
                        <span>In Progress</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 14px; height: 14px; background-color: #F59E0B; border-radius: 2px;"></div>
                        <span>Pending</span>
                    </div>
                    @if ($today >= $minDate && $today <= $maxDate)
                        <div style="display: flex; align-items: center; gap: 8px; margin-left: auto;">
                            <div style="width: 2px; height: 14px; background-color: #ef4444;"></div>
                            <span style="color: #ef4444; font-weight: 600;">← TODAY</span>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- CHART SCRIPTS --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Chart.register(ChartDataLabels);
            // Debug: Log data dari server
            console.log('Chart Data:', {
                catLabels: @json($chartCatLabels ?? []),
                catValues: @json($chartCatValues ?? []),
                statusLabels: @json($chartStatusLabels ?? []),
                statusValues: @json($chartStatusValues ?? []),
                plantLabels: @json($chartPlantLabels ?? []),
                plantValues: @json($chartPlantValues ?? []),
                techLabels: @json($chartTechLabels ?? []),
                techValues: @json($chartTechValues ?? [])
            });

            // 1. Category Chart (Bar - Navy)
            if (@json($chartCatLabels ?? []).length > 0) {
                new Chart(document.getElementById('catChart'), {
                    type: 'bar',
                    data: {
                        labels: @json($chartCatLabels),
                        datasets: [{
                            label: 'Total',
                            data: @json($chartCatValues),
                            backgroundColor: '#1E3A5F',
                            borderRadius: 2
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }

            // 2. Status Chart (Doughnut) with explicit color mapping per status
            (function() {
                const statusLabels = @json($chartStatusLabels ?? []);
                const statusValues = @json($chartStatusValues ?? []);
                if (statusLabels.length === 0) return;

                const statusColorsMap = {
                    'pending': '#f59e0b', // yellow
                    'in_progress': '#2563EB', // blue
                    'completed': '#16A34A', // green
                    'cancelled': '#ef4444' // red
                };

                const backgroundColors = statusLabels.map(l => statusColorsMap[l] ?? '#94a3b8');

                new Chart(document.getElementById('statusChart'), {
                    type: 'doughnut',
                    data: {
                        labels: statusLabels,
                        datasets: [{
                            data: statusValues,
                            backgroundColor: backgroundColors,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            // [FITUR BARU] Konfigurasi Persentase
                            datalabels: {
                                color: '#ffffff',
                                font: {
                                    weight: 'bold',
                                    size: 12
                                },
                                formatter: (value, ctx) => {
                                    let sum = 0;
                                    let dataArr = ctx.chart.data.datasets[0].data;
                                    dataArr.map(data => {
                                        sum += data;
                                    });
                                    let percentage = (value * 100 / sum).toFixed(1) + "%";
                                    return percentage; // Tampilkan persentase
                                }
                            }
                        }
                    }
                });
            })();

            // 3. Plant Chart (Horizontal Bar)
            if (@json($chartPlantLabels ?? []).length > 0) {
                new Chart(document.getElementById('plantChart'), {
                    type: 'bar',
                    data: {
                        labels: @json($chartPlantLabels),
                        datasets: [{
                            label: 'Requests',
                            data: @json($chartPlantValues),
                            backgroundColor: '#2563EB',
                            borderRadius: 3
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }

            // 4. Technician PIC Chart (Horizontal Bar)
            if (@json($chartTechLabels ?? []).length > 0) {
                new Chart(document.getElementById('techChart'), {
                    type: 'bar',
                    data: {
                        labels: @json($chartTechLabels),
                        datasets: [{
                            label: 'Assignments',
                            data: @json($chartTechValues),
                            backgroundColor: '#a855f7',
                            borderRadius: 3
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }

            // Gantt Chart handled via Blade table rendering above
        });

        // Gantt Tooltip Handlers
        function showGanttTooltip(row) {
            const tooltip = row.querySelector('.gantt-tooltip');
            if (tooltip) {
                tooltip.style.display = 'block';
            }
        }

        function hideGanttTooltip(row) {
            const tooltip = row.querySelector('.gantt-tooltip');
            if (tooltip) {
                tooltip.style.display = 'none';
            }
        }

        // Toggle Export Menu
        function toggleExportMenu() {
            const menu = document.getElementById('exportMenu');
            menu.classList.toggle('hidden');
        }

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            const menu = document.getElementById('exportMenu');
            const button = e.target.closest('button');
            if (menu && !e.target.closest('.relative')) {
                menu.classList.add('hidden');
            }
        });

        // Export to PDF Function
        async function exportToPDF() {
            try {
                document.getElementById('exportMenu').classList.add('hidden');

                Swal.fire({
                    title: 'Generating PDF...',
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const element = document.getElementById('dashboard-content');

                // Capture with better settings
                const canvas = await html2canvas(element, {
                    scale: 1.2,
                    logging: false,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#F8FAFC',
                    windowHeight: element.scrollHeight,
                    margin: 0
                });

                const {
                    jsPDF
                } = window.jspdf;
                const imgData = canvas.toDataURL('image/png');

                // A4 landscape
                const pdf = new jsPDF({
                    orientation: 'landscape',
                    unit: 'mm',
                    format: 'a4'
                });

                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();

                // Title page
                pdf.setFontSize(28);
                pdf.setTextColor(30, 58, 95);
                pdf.text('Facilities Dashboard', 148, 40, {
                    align: 'center'
                });

                pdf.setFontSize(12);
                pdf.setTextColor(100, 100, 100);
                const month = document.querySelector('input[name="month"]').value || new Date().toISOString().slice(0,
                    7);
                pdf.text('Report Period: ' + month, 148, 60, {
                    align: 'center'
                });
                pdf.text('Generated: ' + new Date().toLocaleDateString('id-ID'), 148, 70, {
                    align: 'center'
                });

                // Calculate image dimensions for content
                const maxWidth = pageWidth - 8; // 4mm margin each side
                const maxHeight = pageHeight - 8;
                const imgWidth = maxWidth;
                const imgHeight = (canvas.height * imgWidth) / canvas.width;

                // Add content on new page
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 4, 4, imgWidth, imgHeight);

                // Handle multiple pages if needed
                let heightLeft = imgHeight - maxHeight;
                let position = 0;

                while (heightLeft > 0) {
                    position = heightLeft - imgHeight;
                    pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 4, 4 + position, imgWidth, imgHeight);
                    heightLeft -= maxHeight;
                }

                // Save
                const fileName = 'Facilities_Dashboard_' + month + '.pdf';
                pdf.save(fileName);

                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Report exported as PDF',
                    timer: 2000
                });

            } catch (error) {
                console.error('PDF Export error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Export Failed',
                    text: 'Failed to export PDF: ' + error.message
                });
            }
        }

        // Export to PowerPoint Function
    </script>
</x-app-layout>
