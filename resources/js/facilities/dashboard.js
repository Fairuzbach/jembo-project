import { Chart } from 'chart.js';
import { gantt } from 'dhtmlx-gantt';
import 'dhtmlx-gantt/codebase/dhtmlxgantt.css';
let activePlantFilter = 'all';
document.addEventListener('DOMContentLoaded', function() {
    console.log("Dashboard JS Loaded"); // Debugging Log

    // 1. Safety Check: Pastikan Data Global Ada
    if (!window.facilitiesData) {
        console.error("CRITICAL: window.facilitiesData tidak ditemukan. Cek Controller & Blade Anda.");
        return; 
    }

    const data = window.facilitiesData.stats;
    const ganttData = window.facilitiesData.gantt;

    // 2. Init Charts (Hanya jika data stats ada)
    // Kita cek apakah 'data' ada, DAN apakah 'data.catLabels' ada (tidak undefined)
    if (data && data.catLabels) {
        initCharts(data);
    } else {
        console.warn("WARNING: Data 'stats' kosong. Grafik tidak akan dirender.", data);
    }

    // 3. Init Gantt (Hanya jika data gantt ada)
    if (ganttData && (ganttData.data || Array.isArray(ganttData))) {
        initDHTMLX(ganttData);
    } else {
        console.warn("⚠️ Data Gantt kosong atau format salah", ganttData);
    }
});

function initCharts(data) {
    // Registrasi Plugin (Safe Mode)
    try {
        if (typeof ChartDataLabels !== 'undefined') Chart.register(ChartDataLabels);
    } catch (e) {
        console.warn("ChartDataLabels plugin missing");
    }

    // Fungsi Pembantu Pembuat Chart
    // Fungsi Pembantu Pembuat Chart
const createChart = (id, type, labels, datasetData, colors, showLegend = false, showPercent = false) => {
    const ctx = document.getElementById(id);
    
    // 1. Cek apakah elemen canvas ada
    if (!ctx) {
        return;
    }

    // 2. [FIX] Hancurkan Chart Lama Jika Ada
    // Chart.getChart(id) akan mencari instance chart yang sedang aktif di canvas tersebut
    const existingChart = Chart.getChart(id);
    if (existingChart) {
        existingChart.destroy();
    }
    
    // 3. Cek data label kosong (Safety Check)
    if (!labels || labels.length === 0) {
        return;
    }

    // 4. Buat Chart Baru
    new Chart(ctx, {
        type: type,
        data: {
            labels: labels,
            datasets: [{
                data: datasetData || [],
                backgroundColor: colors,
                borderWidth: 1,
                borderRadius: type === 'bar' ? 4 : 0
            }]
        },
        options: {
            indexAxis: type === 'bar' && id !== 'catChart' ? 'y' : 'x',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: showLegend, position: 'bottom' },
                datalabels: {
                    display: showPercent,
                    color: '#fff',
                    font: { weight: 'bold' },
                    formatter: (val, ctx) => {
                        let sum = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                        return sum === 0 ? '0%' : (val * 100 / sum).toFixed(1) + '%';
                    }
                }
            }
        }
    });
};

    // --- EKSEKUSI PEMBUATAN CHART ---
    // Menggunakan || [] sebagai fallback agar tidak error 'undefined'
    
    createChart('catChart', 'bar', data.catLabels || [], data.catValues || [], '#1E3A5F');

    const statusMap = {
        'completed': '#10b981', 'in_progress': '#3b82f6', 'pending': '#f59e0b',
        'waiting_approval': '#f59e0b', 'waiting_facility_approval': '#8b5cf6',
        'rejected': '#ef4444', 'cancelled': '#64748b'
    };
    const statusColors = (data.statusLabels || []).map(l => statusMap[l] || '#cbd5e1');
    createChart('chartStatus', 'doughnut', data.statusLabels || [], data.statusValues || [], statusColors, true, true);

    createChart('plantChart', 'bar', data.plantLabels || [], data.plantValues || [], '#2563EB');
    createChart('techChart', 'bar', data.techLabels || [], data.techValues || [], '#a855f7');
}

function initDHTMLX(ganttData) {
    const ganttContainer = document.getElementById("gantt_here");
    if (!ganttContainer) return;

    // Pastikan library DHTMLX ada
    if (typeof gantt === 'undefined') {
        console.error("Library DHTMLX Gantt belum dimuat.");
        return;
    }

    console.log("Memulai Render Gantt dengan Data:", ganttData);

    // --- ZOOM CONFIGURATION ---
    const zoomConfig = {
        levels: [{
                name: "day",
                scale_height: 54,
                min_column_width: 80,
                scales: [{
                        unit: "month",
                        step: 1,
                        format: "%F %Y"
                    },
                    {
                        unit: "day",
                        step: 1,
                        format: "%d %M"
                    }
                ]
            },
            {
                name: "week",
                scale_height: 54,
                min_column_width: 60,
                scales: [{
                        unit: "month",
                        step: 1,
                        format: "%F %Y"
                    },
                    {
                        unit: "week",
                        step: 1,
                        format: "Week #%W"
                    }
                ]
            },
            {
                name: "month",
                scale_height: 54,
                min_column_width: 120,
                scales: [{
                        unit: "year",
                        step: 1,
                        format: "%Y"
                    },
                    {
                        unit: "month",
                        step: 1,
                        format: "%M"
                    }
                ]
            }
        ]
    };

    gantt.ext.zoom.init(zoomConfig);
    gantt.ext.zoom.setLevel("month");

    // --- CONFIGURATION ---
    gantt.config.xml_date = "%Y-%m-%d %H:%i:%s"; // Format tanggal dari PHP (Y-m-d)
    gantt.config.readonly = true;
    gantt.config.details_on_dblclick = false;
    
    // Fitur agar chart pas di layar
    gantt.config.fit_tasks = true; 
    gantt.config.bar_height = 28;
    gantt.config.row_height = 40;
    gantt.config.scale_height = 54;

    // Kolom Tabel Kiri
    gantt.config.columns = [
        { name: "text", label: "Task / Ticket", tree: true, width: 200, resize: true },
        { name: "start_date", label: "Start", align: "center", width: 90 },
        { name: "plant", label: "Loc", align: "center", width: 60 }
    ];

    // Header Waktu
    gantt.config.scale_unit = "month";
    gantt.config.date_scale = "%F, %Y";
    gantt.config.subscales = [{ unit: "day", step: 1, date: "%j" }];

    // Warna Bar
    gantt.templates.task_class = function(start, end, task) {
        switch (task.status) {
            case "completed": return "gantt-task-completed";
            case "in_progress": return "gantt-task-progress";
            case "rejected": return "gantt-task-rejected";
            case "pending": case "waiting_approval": return "gantt-task-pending";
            default: return "gantt-task-default";
        }
    };

    // Tooltip
    gantt.templates.tooltip_text = function(start, end, task){
        return `<b>Ticket:</b> ${task.text}<br/><b>Status:</b> ${task.status ? task.status.toUpperCase() : '-'}<br/><b>Plant:</b> ${task.plant || '-'}`;
    };
    gantt.attachEvent("onBeforeTaskDisplay", function(id, task){
        if(activePlantFilter === 'all'){
            return true;
        }

        if(task.plant && task.plant.toLowerCase() === activePlantFilter.toLowerCase()){
            return true;
        }

        return false;
    });
    // --- INITIALIZE ---
    gantt.init("gantt_here");
    gantt.plugins({
        marker: true,
    });


    // Add CSS for better tooltip styling
    const style = document.createElement('style');
    style.textContent = `
        .gantt_tooltip {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%) !important;
            color: #f3f4f6 !important;
            border: 1px solid #374151 !important;
            border-radius: 6px !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3) !important;
            padding: 10px 12px !important;
            font-size: 13px !important;
        }
        .gantt_tooltip b {
            color: #fbbf24 !important;
            font-weight: 700;
        }
    `;
    document.head.appendChild(style);
    gantt.clearAll();

    // --- TODAY MARKER ---
    const today = new Date();
     gantt.addMarker({
        start_date: today,
        css: "gantt_marker",
        text: "TODAY",
        title: "Today: " + gantt.date.date_to_str("%d %M %Y")(today)
    });


    // --- PARSING DATA (FIXED LOGIC) ---
    
    // Kasus 1: Data adalah Array langsung (Sesuai data Anda sekarang)
    if (Array.isArray(ganttData)) {
        // Kita bungkus jadi object {data: [...]} agar DHTMLX paham
        gantt.parse({ data: ganttData, links: [] });
    } 
    // Kasus 2: Data adalah Object {data: [...]} (Standard DHTMLX)
    else if (ganttData.data) {
        gantt.parse(ganttData);
    } 
    else {
        console.warn("Format data Gantt tidak dikenali atau kosong.");
    }
}

// Global Plant Filter Function
window.filterByPlant = function(plant) {
    if (typeof gantt === 'undefined') return;
    
    // 1. Update Variabel Global
    activePlantFilter = plant;
    
    // 2. Refresh Gantt (Ini akan memicu event onBeforeTaskDisplay di atas)
    gantt.refreshData();
    
    // 3. Update Button Styles (Copy-paste styling Anda tadi sudah bagus)
    document.querySelectorAll('.plant-filter-btn').forEach(btn => {
        btn.classList.remove('bg-gradient-to-r', 'from-emerald-500', 'to-emerald-600', 'text-white', 'shadow-sm');
        btn.classList.add('bg-white', 'text-slate-700', 'border', 'border-slate-200', 'hover:bg-slate-50');
    });
    
    // Cari tombol aktif berdasarkan ID (Pastikan di HTML id buttonnya: plant-filter-namaPlant)
    // Contoh ID: plant-filter-all, plant-filter-plant a
    // Karena ID tidak boleh spasi, pastikan saat render blade ID-nya diganti spasi jadi dash/underscore jika perlu, 
    // atau gunakan querySelector berdasarkan atribut data.
    
    // Asumsi ID aman (misal plant 'all', 'PlantA'):
    const cleanId = plant.replace(/\s+/g, '_'); // Ganti spasi dengan _ jaga-jaga
    const activeBtn = document.getElementById('plant-filter-' + cleanId) || document.getElementById('plant-filter-' + plant);
    
    if (activeBtn) {
        activeBtn.classList.remove('bg-white', 'text-slate-700', 'border', 'border-slate-200', 'hover:bg-slate-50');
        activeBtn.classList.add('bg-gradient-to-r', 'from-emerald-500', 'to-emerald-600', 'text-white', 'shadow-sm');
    }
};

// Global Zoom Control Function
window.changeZoom = function(level) {
    if (window.gantt) {
        gantt.ext.zoom.setLevel(level);
        
        // Update button states
        document.querySelectorAll('.zoom-btn-fh').forEach(btn => {
            btn.classList.remove('active', 'bg-gradient-to-r', 'from-blue-500', 'to-blue-600', 'text-white', 'shadow-sm');
            btn.classList.add('bg-white', 'text-slate-700', 'border', 'border-slate-200', 'hover:bg-slate-50');
        });

        const activeBtn = document.getElementById('zoom-fh-' + level);
        if (activeBtn) {
            activeBtn.classList.remove('bg-white', 'text-slate-700', 'border', 'border-slate-200', 'hover:bg-slate-50');
            activeBtn.classList.add('active', 'bg-gradient-to-r', 'from-blue-500', 'to-blue-600', 'text-white', 'shadow-sm');
        }
    }
};

// Global Export Function
window.exportToPDF = async function() {
    try {
        if(typeof Swal === 'undefined' || typeof html2canvas === 'undefined' || typeof window.jspdf === 'undefined') {
            alert("Library PDF belum siap. Tunggu sebentar atau refresh.");
            return;
        }
        
        const menu = document.getElementById('exportMenu');
        if (menu) menu.classList.add('hidden');

        Swal.fire({ title: 'Generating PDF...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        const element = document.getElementById('dashboard-content');
        const canvas = await html2canvas(element, { scale: 1.5, logging: false, useCORS: true, allowTaint: true, backgroundColor: '#F8FAFC' });

        const imgData = canvas.toDataURL('image/png');
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();
        
        pdf.setFontSize(18);
        pdf.setTextColor(30, 58, 95);
        pdf.text('Facilities Dashboard Report', 14, 15);
        
        pdf.setFontSize(10);
        pdf.setTextColor(100, 100, 100);
        const monthInput = document.querySelector('input[name="month"]');
        const period = monthInput ? monthInput.value : new Date().toISOString().slice(0, 7);
        pdf.text('Period: ' + period, 14, 22);

        const imgProps = pdf.getImageProperties(imgData);
        const pdfWidth = pageWidth - 20;
        const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

        let heightLeft = pdfHeight;
        let position = 30;

        pdf.addImage(imgData, 'PNG', 10, position, pdfWidth, pdfHeight);
        heightLeft -= (pageHeight - position);

        while (heightLeft > 0) {
            position = heightLeft - pdfHeight;
            pdf.addPage();
            pdf.addImage(imgData, 'PNG', 10, -(pdfHeight - heightLeft) + 10, pdfWidth, pdfHeight);
            heightLeft -= pageHeight;
        }

        pdf.save('Facilities_Dashboard_' + period + '.pdf');
        Swal.fire({ icon: 'success', title: 'Export Success!', timer: 2000, showConfirmButton: false });

    } catch (error) {
        console.error('PDF Export error:', error);
        Swal.fire({ icon: 'error', title: 'Export Failed', text: error.message });
    }
};