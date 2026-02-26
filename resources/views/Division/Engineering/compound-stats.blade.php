<x-app-layout>
    {{-- CSS tambahan untuk menyembunyikan scrollbar tapi tetap bisa di-swipe --}}
    <style>
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>

    <div class="py-4 sm:py-8">
        <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">

            {{-- HEADER MOBILE-FRIENDLY --}}
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-3">
                <div class="w-full">
                    <h2 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight leading-tight">Statistik
                        Compound</h2>
                    <p class="text-[11px] sm:text-sm text-slate-500 mt-1">Pantau tren pH, Konsentrasi, & Suhu.</p>
                </div>

                <a href="{{ route('eng.index') }}"
                    class="w-full sm:w-auto text-center px-4 py-2 sm:py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-xs sm:text-sm font-bold shadow-sm transition-colors">
                    &larr; Kembali <span class="hidden sm:inline">ke Dashboard</span>
                </a>
            </div>

            {{-- PANEL KONTROL (SUPER RESPONSIVE) --}}
            <div
                class="bg-white p-3 sm:p-5 rounded-xl border border-slate-200 shadow-sm mb-4 sm:mb-6 flex flex-col gap-4">

                {{-- BARIS 1: Pilih Area / Plant (Grid 2 Kolom di HP) --}}
                <div class="border-b border-slate-100 pb-3">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Area
                        Mesin:</span>
                    <div class="grid grid-cols-2 bg-slate-100 rounded-lg p-1 border border-slate-200">
                        <a href="{{ route('eng.compound.stats', ['plant' => 'Plant A', 'machine' => 'all', 'filter' => $filter, 'mode' => $mode]) }}"
                            class="text-center px-2 py-2 rounded-md text-xs sm:text-sm font-black transition-all {{ $plant == 'Plant A' ? 'bg-blue-600 shadow-sm text-white' : 'text-slate-500 hover:text-slate-800' }}">
                            PLANT A
                        </a>
                        <a href="{{ route('eng.compound.stats', ['plant' => 'Autowire', 'machine' => 'all', 'filter' => $filter, 'mode' => $mode]) }}"
                            class="text-center px-2 py-2 rounded-md text-xs sm:text-sm font-black transition-all {{ $plant == 'Autowire' ? 'bg-blue-600 shadow-sm text-white' : 'text-slate-500 hover:text-slate-800' }}">
                            AUTOWIRE
                        </a>
                    </div>
                </div>
                @if ($plant === 'Plant A')
                    <div class="border-b border-slate-100 pb-3">
                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Pilih
                            Detail Mesin (Plant A):</span>
                        <div class="flex overflow-x-auto hide-scrollbar gap-2 pb-1 snap-x">
                            @foreach ($plantAMachines as $id => $name)
                                <a href="{{ route('eng.compound.stats', ['plant' => $plant, 'machine' => $id, 'filter' => $filter, 'mode' => $mode]) }}"
                                    class="snap-start whitespace-nowrap px-3 py-1.5 rounded-lg text-[11px] sm:text-xs font-bold border transition-all 
                               {{ $machineId == $id ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100' }}">
                                    {{ $name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
                {{-- BARIS 2: Mode & Filter Waktu --}}
                <div class="flex flex-col md:flex-row gap-4 sm:gap-6">

                    {{-- Toggle Mode (Grid 2 Kolom di HP) --}}
                    <div class="w-full md:w-auto">
                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Mode
                            Tampilan:</span>
                        <div class="grid grid-cols-2 md:flex bg-slate-100 rounded-lg p-1 border border-slate-200">
                            <a href="{{ route('eng.compound.stats', ['plant' => $plant, 'filter' => $filter, 'mode' => 'avg']) }}"
                                class="text-center px-3 py-1.5 rounded-md text-[11px] sm:text-xs font-bold transition-all {{ $mode == 'avg' ? 'bg-white shadow-sm text-blue-700' : 'text-slate-500 hover:text-slate-800' }}">
                                Rata-rata (AVG)
                            </a>
                            <a href="{{ route('eng.compound.stats', ['plant' => $plant, 'filter' => $filter, 'mode' => 'raw']) }}"
                                class="text-center px-3 py-1.5 rounded-md text-[11px] sm:text-xs font-bold transition-all {{ $mode == 'raw' ? 'bg-white shadow-sm text-blue-700' : 'text-slate-500 hover:text-slate-800' }}">
                                Per Report (RAW)
                            </a>
                        </div>
                    </div>

                    {{-- Filter Waktu (Swipe Horizontal di HP) --}}
                    @if ($mode === 'avg')
                        <div
                            class="w-full border-t md:border-t-0 md:border-l-2 border-slate-100 pt-3 md:pt-0 md:pl-6 overflow-hidden">
                            <span
                                class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Filter
                                Rentang Waktu:</span>
                            {{-- Container yang bisa di-swipe --}}
                            <div class="flex overflow-x-auto hide-scrollbar gap-2 pb-1 snap-x">
                                @php
                                    $filters = [
                                        'weekly' => 'Mingguan',
                                        'monthly' => 'Bulanan',
                                        'quarterly' => 'Kuartalan',
                                        'semester' => 'Semesteran',
                                        'yearly' => 'Tahunan',
                                    ];
                                @endphp

                                @foreach ($filters as $key => $label)
                                    <a href="{{ route('eng.compound.stats', ['plant' => $plant, 'filter' => $key, 'mode' => $mode]) }}"
                                        class="snap-start whitespace-nowrap px-4 py-1.5 rounded-lg text-[11px] sm:text-xs font-bold border transition-all 
                                   {{ $filter == $key ? 'bg-slate-800 text-white border-slate-800 shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100' }}">
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="w-full md:border-l-2 border-slate-100 md:pl-6 flex items-center mt-1 md:mt-0">
                            <span
                                class="w-full text-[11px] text-amber-700 bg-amber-50 px-3 py-2 rounded-lg border border-amber-200 leading-tight">
                                <i class="fas fa-info-circle mr-1"></i> Mode <b>RAW</b> menampilkan semua data titik
                                harian murni (tanpa dirata-rata).
                            </span>
                        </div>
                    @endif

                </div>
            </div>

            {{-- GRID CHART KONTEN --}}
            <div class="grid grid-cols-1 gap-4 sm:gap-6">

                {{-- Chart 1: pH Level --}}
                <div class="bg-white p-3 sm:p-5 rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-sm sm:text-base font-bold text-slate-800 mb-1">Tren pH Level</h3>
                    {{-- Tinggi canvas dikurangi di HP agar pas di layar --}}
                    <div class="relative w-full h-[250px] sm:h-[350px]">
                        <canvas id="phChart"></canvas>
                    </div>
                </div>

                {{-- Chart 2: Konsentrasi (%) --}}
                <div class="bg-white p-3 sm:p-5 rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-sm sm:text-base font-bold text-slate-800 mb-1">Tren Konsentrasi (%)</h3>
                    <div class="relative w-full h-[250px] sm:h-[350px]">
                        <canvas id="konsChart"></canvas>
                    </div>
                </div>

                {{-- Chart 3: Temperatur (°C) --}}
                <div class="bg-white p-3 sm:p-5 rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-sm sm:text-base font-bold text-slate-800 mb-1">Tren Temperatur (°C)</h3>
                    <div class="relative w-full h-[250px] sm:h-[350px]">
                        <canvas id="tempChart"></canvas>
                    </div>
                </div>

            </div>

        </div>
    </div>

    {{-- Script Chart JS (Tanpa Perubahan) --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const labels = @json($labels);
            const stdValues = @json($stdValues);
            const machineId = @json($machineId); // Ambil ID mesin yang sedang dipilih

            // Data Aktual
            const drawPhData = @json($drawPhData);
            const annPhData = @json($annPhData);
            const annPhData2 = @json($annPhData2); // Data Bak B

            const drawKonsData = @json($drawKonsData);
            const annKonsData = @json($annKonsData);
            const annKonsData2 = @json($annKonsData2); // Data Bak B

            const drawTempData = @json($drawTempData);
            const annTempData = @json($annTempData);
            const annTempData2 = @json($annTempData2); // Data Bak B

            const lineConfig = (labelName, dataArray, borderColor, bgColor, borderDash = []) => ({
                label: labelName,
                data: dataArray,
                borderColor: borderColor,
                backgroundColor: bgColor,
                borderWidth: 2,
                borderDash: borderDash, // Tambahan untuk membedakan gaya garis (opsional)
                tension: 0.3,
                fill: false,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: borderColor,
                pointRadius: 4,
                pointHoverRadius: 6
            });

            const stdLineConfigs = (labelName, stdObj, color) => {
                if (!stdObj || stdObj.min === null) return []; // Kosongkan jika tidak ada standar
                let configs = [];

                // 1. Buat Garis Standar Bawah (Min)
                configs.push({
                    label: 'Min Std ' + labelName + ' (' + stdObj.min + ')',
                    data: labels.map(() => stdObj.min),
                    borderColor: color,
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false,
                    pointRadius: 0,
                    pointHitRadius: 0,
                    tension: 0
                });

                // 2. Buat Garis Standar Atas (Max) Jika ada 2 angka di database
                if (stdObj.max !== null && stdObj.max !== stdObj.min) {
                    configs.push({
                        label: 'Max Std ' + labelName + ' (' + stdObj.max + ')',
                        data: labels.map(() => stdObj.max),
                        borderColor: color,
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        pointRadius: 0,
                        pointHitRadius: 0,
                        tension: 0
                    });
                }
                return configs;
            };

            const commonOptions = (yAxisLabel, suggestMin, suggestMax) => ({
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 10,
                            font: {
                                size: 11,
                                weight: 'bold'
                            },
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        suggestedMin: suggestMin,
                        suggestedMax: suggestMax,
                        grid: {
                            borderDash: [4, 4],
                            color: '#f1f5f9'
                        },
                        title: {
                            display: true,
                            text: yAxisLabel,
                            font: {
                                size: 10,
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            },
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            });

            // 1. GRAFIK pH
            let phDatasets = [
                lineConfig('Actual Draw', drawPhData, '#2563eb', 'transparent'),
                lineConfig('Actual Ann (Bak A)', annPhData, '#059669', 'transparent'),
                ...stdLineConfigs('Draw', stdValues.draw_ph, '#93c5fd'),
                ...stdLineConfigs('Ann', stdValues.ann_ph, '#6ee7b7')
            ];
            // Tambahkan Garis Bak B hanya jika mesin = all atau mesin = 2 (Twin RBD)
            if (machineId === 'all' || machineId == 2) {
                phDatasets.splice(2, 0, lineConfig('Actual Ann (Bak B)', annPhData2, '#4f46e5', 'transparent', [3,
                    3]));
                // Warnanya indigo dan garisnya sedikit putus-putus [3,3] agar mudah dibedakan dengan Bak A
            }
            new Chart(document.getElementById('phChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: phDatasets
                },
                options: commonOptions('Nilai pH', 6, 10)
            });

            // 2. GRAFIK KONSENTRASI
            let konsDatasets = [
                lineConfig('Actual Draw', drawKonsData, '#8b5cf6', 'transparent'),
                lineConfig('Actual Ann (Bak A)', annKonsData, '#d97706', 'transparent'),
                ...stdLineConfigs('Draw', stdValues.draw_kons, '#c4b5fd'),
                ...stdLineConfigs('Ann', stdValues.ann_kons, '#fcd34d')
            ];
            if (machineId === 'all' || machineId == 2) {
                konsDatasets.splice(2, 0, lineConfig('Actual Ann (Bak B)', annKonsData2, '#be185d', 'transparent', [
                    3, 3
                ]));
                // Warna pink gelap
            }
            new Chart(document.getElementById('konsChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: konsDatasets
                },
                options: commonOptions('Konsentrasi (%)', 0, 15)
            });

            // 3. GRAFIK TEMPERATUR
            let tempDatasets = [
                lineConfig('Actual Draw', drawTempData, '#dc2626', 'transparent'),
                lineConfig('Actual Ann (Bak A)', annTempData, '#ea580c', 'transparent'),
                ...stdLineConfigs('Draw', stdValues.draw_temp, '#fca5a5'),
                ...stdLineConfigs('Ann', stdValues.ann_temp, '#fdba74')
            ];
            if (machineId === 'all' || machineId == 2) {
                tempDatasets.splice(2, 0, lineConfig('Actual Ann (Bak B)', annTempData2, '#0369a1', 'transparent', [
                    3, 3
                ]));
                // Warna biru gelap
            }
            new Chart(document.getElementById('tempChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: tempDatasets
                },
                options: commonOptions('Temperatur (°C)', 30, 60)
            });
        });
    </script>
</x-app-layout>
