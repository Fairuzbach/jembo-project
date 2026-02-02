import { Chart } from 'chart.js';

// JIKA ANDA SUDAH 'npm install dhtmlx-gantt', UNCOMMENT 2 BARIS INI:
// import { gantt } from 'dhtmlx-gantt';
// import 'dhtmlx-gantt/codebase/dhtmlxgantt.css';

// Jika belum install npm, pastikan CDN DHTMLX ada di Blade (seperti sebelumnya).

let activePlantFilter = 'all';

// --- 1. HELPER PENTING: SLUGIFY ---
// Ini wajib ada agar ID tombol filter ("Plant A - Autowire") cocok dengan logic JS
function slugify(text) {
    return text.toString().toLowerCase()
        .replace(/\s+/g, '-')           // Spasi -> dash
        .replace(/[^\w\-]+/g, '')       // Hapus karakter aneh
        .replace(/\-\-+/g, '-')         // Hapus dash ganda
        .replace(/^-+/, '')             // Trim depan
        .replace(/-+$/, '');            // Trim belakang
}

document.addEventListener('DOMContentLoaded', function() {
    console.log("Dashboard JS Loaded"); 

    // Safety Check Data
    if (!window.facilitiesData) {
        console.error("CRITICAL: window.facilitiesData missing.");
        return; 
    }

    const data = window.facilitiesData.stats;
    const ganttData = window.facilitiesData.gantt;

    // 1. Init Charts
    if (data && data.catLabels) {
        initCharts(data);
    }

    // 2. Init Gantt
    // Cek format data (Array atau Object)
    if (ganttData && (ganttData.data || Array.isArray(ganttData))) {
        initDHTMLX(ganttData);
    } else {
        console.warn("⚠️ Data Gantt Kosong/Format Salah:", ganttData);
    }
});

// --- FUNGSI CHART.JS (LOGIC LAMA ANDA, SUDAH OK) ---
function initCharts(data) {
    try {
        if (typeof ChartDataLabels !== 'undefined') Chart.register(ChartDataLabels);
    } catch (e) {
        console.warn("ChartDataLabels missing");
    }

    const createChart = (id, type, labels, datasetData, colors, showLegend = false, showPercent = false) => {
        const ctx = document.getElementById(id);
        if (!ctx) return;

        const existingChart = Chart.getChart(id);
        if (existingChart) existingChart.destroy();
        
        if (!labels || labels.length === 0) return;

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

// --- FUNGSI GANTT CHART (INI YANG KITA PERBAIKI) ---
function initDHTMLX(ganttData) {
    const ganttContainer = document.getElementById("gantt_here");
    if (!ganttContainer) return;

    if (typeof gantt === 'undefined') {
        console.error("Library DHTMLX Gantt belum dimuat (Cek CDN atau Import).");
        return;
    }

    console.log("Memulai Render Gantt...", ganttData);

    // [FIX 1] CONFIG TANGGAL (Wajib sama dengan PHP: Y-m-d H:i:s)
    gantt.config.xml_date = "%Y-%m-%d %H:%i:%s"; 
    
    gantt.config.readonly = true;
    gantt.config.details_on_dblclick = false;
    gantt.config.fit_tasks = true; // Auto Zoom
    gantt.config.bar_height = 24;
    gantt.config.row_height = 38;

    // Kolom
    gantt.config.columns = [
        { name: "text", label: "Task / Ticket", tree: true, width: 250, resize: true },
        { name: "start_date", label: "Start", align: "center", width: 90 },
        { name: "plant", label: "Loc", align: "center", width: 70 }
    ];

    // [FIX 2] CLASS WARNA
    // Menggunakan CSS class di Blade agar warna status muncul
    gantt.templates.task_class = function(start, end, task) {
        if (task.status) {
            return "gantt-task-" + task.status.toLowerCase();
        }
        return "gantt-task-default";
    };

    // Tooltip
    gantt.templates.tooltip_text = function(start, end, task){
        return `<b>Ticket:</b> ${task.text}<br/>
                <b>Status:</b> ${task.status ? task.status.toUpperCase() : '-'}<br/>
                <b>Plant:</b> ${task.plant || '-'}`;
    };

    // [FIX 3] LOGIC FILTER LEBIH KUAT
    gantt.attachEvent("onBeforeTaskDisplay", function(id, task){
        if(activePlantFilter === 'all'){
            return true;
        }
        
        // Safety check null
        const tPlant = (task.plant || '').toLowerCase();
        const fFilter = activePlantFilter.toLowerCase();

        // Logic "Contains" agar "Plant A - Autowire" tetap muncul saat filter "Plant A"
        if(tPlant.includes(fFilter)){
            return true;
        }
        return false;
    });

    // Initialize
    gantt.init("gantt_here");
    gantt.clearAll();

    // Parse Data
    if (Array.isArray(ganttData)) {
        gantt.parse({ data: ganttData, links: [] });
    } else if (ganttData.data) {
        gantt.parse(ganttData);
    }
}

// --- GLOBAL FUNCTIONS (Agar bisa diakses onclick di Blade) ---

window.filterByPlant = function(plant) {
    if (typeof gantt === 'undefined') return;
    
    activePlantFilter = plant;
    gantt.refreshData();
    
    // Reset Button Styles
    document.querySelectorAll('.plant-filter-btn').forEach(btn => {
        btn.classList.remove('bg-gradient-to-r', 'from-emerald-500', 'to-emerald-600', 'text-white', 'shadow-sm');
        btn.classList.add('bg-white', 'text-slate-700', 'border', 'border-slate-200', 'hover:bg-slate-50');
    });
    
    // [FIX 4] Highlight Button Aktif Menggunakan Slugify
    let btnId;
    if (plant === 'all') {
        btnId = 'plant-filter-all';
    } else {
        btnId = 'plant-filter-' + slugify(plant);
    }

    const activeBtn = document.getElementById(btnId);
    
    if (activeBtn) {
        activeBtn.classList.remove('bg-white', 'text-slate-700', 'border', 'border-slate-200', 'hover:bg-slate-50');
        activeBtn.classList.add('bg-gradient-to-r', 'from-emerald-500', 'to-emerald-600', 'text-white', 'shadow-sm');
    } else {
        console.warn("Tombol filter tidak ketemu:", btnId);
    }
};

window.changeZoom = function(level) {
    if (typeof gantt !== 'undefined') {
        // Logic Zoom Manual Sederhana
        switch(level){
            case 'day':
                gantt.config.scale_unit = "day"; gantt.config.date_scale = "%d %M"; break;
            case 'week':
                gantt.config.scale_unit = "week"; gantt.config.date_scale = "Week #%W"; break;
            case 'month':
                gantt.config.scale_unit = "month"; gantt.config.date_scale = "%F %Y"; break;
        }
        gantt.render();
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