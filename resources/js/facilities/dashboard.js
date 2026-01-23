import { Chart } from 'chart.js';

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
    if (ganttData && ganttData.data) {
        initDHTMLX(ganttData);
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

    // --- CONFIGURATION ---
    gantt.config.xml_date = "%Y-%m-%d"; // Format tanggal dari PHP (Y-m-d)
    gantt.config.readonly = true;
    gantt.config.details_on_dblclick = false;
    
    // Fitur agar chart pas di layar
    gantt.config.fit_tasks = true; 

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

    // --- INITIALIZE ---
    gantt.init("gantt_here");
    gantt.clearAll();

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