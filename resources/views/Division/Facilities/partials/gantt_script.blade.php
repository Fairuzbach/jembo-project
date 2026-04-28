<script>
    // Global Variables
    let activePlantFilter = 'all';
    let activeZoomLevel = 'month';
    let ganttInitialized = false;

    /**
     * Slugify text untuk ID HTML yang konsisten
     */
    function slugify(text) {
        return text.toString().toLowerCase()
            .replace(/\s+/g, '-') // Spasi -> dash
            .replace(/[^\w\-]+/g, '') // Hapus karakter aneh
            .replace(/\-\-+/g, '-') // Hapus dash ganda
            .replace(/^-+/, '') // Trim depan
            .replace(/-+$/, ''); // Trim belakang
    }

    /**
     * Force Wide Date Range untuk memastikan ada scrollbar horizontal
     */
    function forceWideDateRange() {
        const today = new Date();
        // Rentang: 1 Bulan ke Belakang s/d 6 Bulan ke Depan
        const start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        const end = new Date(today.getFullYear(), today.getMonth() + 6, 0);

        gantt.config.start_date = start;
        gantt.config.end_date = end;
    }

    /**
     * Format tanggal untuk display
     */
    function formatDate(date) {
        if (!date) return '-';
        const d = new Date(date);
        const options = {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        };
        return d.toLocaleDateString('en-US', options);
    }

    /**
     * Get status display name dan color
     */
    function getStatusInfo(status) {
        const statusConfig = {
            'completed': {
                label: 'Completed',
                color: '#10b981',
                icon: '✓'
            },
            'in_progress': {
                label: 'In Progress',
                color: '#3b82f6',
                icon: '⏳'
            },
            'pending': {
                label: 'Pending',
                color: '#f59e0b',
                icon: '⏸'
            },
            'waiting_approval': {
                label: 'Waiting Approval',
                color: '#f59e0b',
                icon: '⏳'
            },
            'waiting_facility_approval': {
                label: 'Waiting Facility',
                color: '#8b5cf6',
                icon: '⏳'
            },
            'rejected': {
                label: 'Rejected',
                color: '#ef4444',
                icon: '✗'
            },
            'cancelled': {
                label: 'Cancelled',
                color: '#64748b',
                icon: '⊘'
            }
        };
        return statusConfig[status] || {
            label: status,
            color: '#cbd5e1',
            icon: '•'
        };
    }

    document.addEventListener('DOMContentLoaded', function() {
        console.log("🚀 Dashboard JS Loaded (Enhanced Version)");

        // Safety Check Data
        if (!window.facilitiesData) {
            document.getElementById('gantt_chart_robust').innerHTML =
                "<p class='text-red-500 p-4'>Gagal memuat data. Silakan refresh halaman.</p>";
            return;
        }

        const data = window.facilitiesData.stats;
        const ganttData = window.facilitiesData.gantt;

        // 1. Initialize Charts
        if (data && data.catLabels) {
            initCharts(data);
        }

        // 2. Initialize Gantt
        if (ganttData && (ganttData.data || Array.isArray(ganttData))) {
            initGanttChart(ganttData);
        } else {
            console.warn("⚠️ Data Gantt Kosong/Format Salah:", ganttData);
        }
    });

    function initCharts(data) {
        try {
            if (typeof ChartDataLabels !== 'undefined') {
                Chart.register(ChartDataLabels);
            }
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
                        legend: {
                            display: showLegend,
                            position: 'bottom'
                        },
                        datalabels: {
                            display: showPercent,
                            color: '#fff',
                            font: {
                                weight: 'bold'
                            },
                            formatter: (val, ctx) => {
                                let sum = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                return sum === 0 ? '0%' : (val * 100 / sum).toFixed(1) + '%';
                            }
                        }
                    }
                }
            });
        };

        // Category Chart
        createChart('catChart', 'bar', data.catLabels || [], data.catValues || [], '#1E3A5F');

        // Status Chart with color mapping
        const statusMap = {
            'completed': '#10b981',
            'in_progress': '#3b82f6',
            'pending': '#f59e0b',
            'waiting_approval': '#f59e0b',
            'waiting_facility_approval': '#8b5cf6',
            'rejected': '#ef4444',
            'cancelled': '#64748b'
        };
        const statusColors = (data.statusLabels || []).map(l => statusMap[l] || '#cbd5e1');
        createChart('chartStatus', 'doughnut', data.statusLabels || [], data.statusValues || [], statusColors, true,
            true);

        // Plant Chart
        createChart('plantChart', 'bar', data.plantLabels || [], data.plantValues || [], '#2563EB');

        // Technology Chart
        createChart('techChart', 'bar', data.techLabels || [], data.techValues || [], '#a855f7');
    }

    function initGanttChart(ganttData) {
        console.log("🔧 Init Gantt Chart (Enhanced Version)...");

        const containerID = "gantt_chart_robust";
        const ganttContainer = document.getElementById(containerID);

        if (!ganttContainer) {
            console.error("❌ Container gantt tidak ditemukan!");
            return;
        }

        if (typeof gantt === 'undefined') {
            console.error("❌ Library DHTMLX Gantt belum dimuat (Cek CDN atau Import).");
            ganttContainer.innerHTML = "<p class='text-red-500 p-4'>Error: Library DHTMLX gagal dimuat.</p>";
            return;
        }

        let cleanTasks = [];

        if (ganttData && ganttData.data && Array.isArray(ganttData.data)) {
            cleanTasks = ganttData.data.map(task => {
                // Potong Jam agar format YYYY-MM-DD
                let cleanStart = String(task.start_date).substring(0, 10);
                return {
                    id: task.id,
                    text: task.text,
                    start_date: cleanStart,
                    duration: task.duration || 1,
                    plant: task.plant || '-',
                    status: task.status || 'pending',
                    progress: (task.status === 'completed' ? 1 : 0),
                    open: true,
                    // Data tambahan untuk tooltip
                    category: task.category || '-',
                    technician: task.technician || '-',
                    description: task.description || ''
                };
            });
        } else if (Array.isArray(ganttData)) {
            cleanTasks = ganttData.map(task => {
                let cleanStart = String(task.start_date).substring(0, 10);
                return {
                    id: task.id,
                    text: task.text,
                    start_date: cleanStart,
                    duration: task.duration || 1,
                    plant: task.plant || '-',
                    status: task.status || 'pending',
                    progress: (task.status === 'completed' ? 1 : 0),
                    open: true,
                    category: task.category || '-',
                    technology: task.technology || '-',
                    description: task.description || ''
                };
            });
        }

        console.log("✅ Data Bersih:", cleanTasks);

        gantt.clearAll();

        // Force wide date range untuk scrollbar
        forceWideDateRange();

        // Date format configuration
        gantt.config.xml_date = "%Y-%m-%d";

        // Display settings
        gantt.config.readonly = true;
        gantt.config.details_on_dblclick = false;
        gantt.config.fit_tasks = false;
        gantt.config.bar_height = 28;
        gantt.config.row_height = 42;


        gantt.config.tooltip_timeout = 30;
        gantt.config.tooltip_offset_x = 10;
        gantt.config.tooltip_offset_y = 20;

        // [ENHANCED] Styling configuration
        gantt.config.show_grid = true;
        gantt.config.show_task_cells = true;
        gantt.config.show_progress = true;
        gantt.config.drag_progress = false;
        gantt.config.drag_resize = false;
        gantt.config.drag_move = false;


        gantt.config.min_column_width = 150; // Lebar kolom bulan
        gantt.config.scale_height = 60; // Tinggi header double (increased)
        gantt.config.scales = [{
                unit: "year",
                step: 1,
                format: "%Y"
            }, // Atas: 2024
            {
                unit: "month",
                step: 1,
                format: "%F"
            } // Bawah: January
        ];

        // Column configuration
        gantt.config.columns = [{
                name: "text",
                label: "Task / Ticket",
                tree: true,
                width: 240,
                resize: true
            },
            {
                name: "start_date",
                label: "Start",
                align: "center",
                width: 100
            },
            {
                name: "plant",
                label: "Location",
                align: "center",
                width: 80
            }
        ];

        // Task class for color mapping
        gantt.templates.task_class = function(start, end, task) {
            if (task.status) {
                return "gantt-task-" + task.status.toLowerCase();
            }
            return "gantt-task-default";
        };


        gantt.templates.tooltip_text = function(start, end, task) {
            const statusInfo = getStatusInfo(task.status);
            const startFormatted = formatDate(task.start_date);
            const endDate = gantt.calculateEndDate(task.start_date, task.duration);
            const endFormatted = formatDate(endDate);

            return `
            <div style="
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; 
                min-width: 320px; 
                max-width: 380px;
                background: #ffffff;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                overflow: hidden;">
                
                <!-- Header -->
                <div style="
                    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); 
                    color: #ffffff; 
                    padding: 16px 20px; 
                    font-weight: 600;
                    font-size: 15px;
                    line-height: 1.4;">
                    ${task.text}
                </div>
                
                <!-- Content -->
                <div style="padding: 16px 20px;">
                    
                    <!-- Status Badge -->
                    <div style="margin-bottom: 16px;">
                        <span style="
                            display: inline-flex; 
                            align-items: center; 
                            padding: 6px 14px; 
                            background: ${statusInfo.color}; 
                            color: #ffffff; 
                            border-radius: 16px; 
                            font-size: 11px; 
                            font-weight: 700;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                            box-shadow: 0 2px 8px ${statusInfo.color}40;">
                            <span style="margin-right: 6px; font-size: 14px;">${statusInfo.icon}</span>
                            ${statusInfo.label}
                        </span>
                    </div>
                    
                    <!-- Info Table -->
                    <table style="
                        width: 100%; 
                        font-size: 13px; 
                        line-height: 1.6; 
                        border-collapse: collapse;">
                        
                        <tr>
                            <td style="
                                color: #64748b; 
                                padding: 8px 0; 
                                font-weight: 600; 
                                width: 45%;">
                                <span style="margin-right: 8px;">📍</span>Location
                            </td>
                            <td style="
                                color: #0f172a; 
                                font-weight: 700; 
                                text-align: right; 
                                padding: 8px 0;">
                                ${task.plant}
                            </td>
                        </tr>
                        
                        <tr style="background: #f8fafc;">
                            <td style="
                                color: #64748b; 
                                padding: 8px 0; 
                                font-weight: 600;">
                                <span style="margin-right: 8px;">📅</span>Start Date
                            </td>
                            <td style="
                                color: #0f172a; 
                                font-weight: 700; 
                                text-align: right; 
                                padding: 8px 0;">
                                ${startFormatted}
                            </td>
                        </tr>
                        
                        <tr>
                            <td style="
                                color: #64748b; 
                                padding: 8px 0; 
                                font-weight: 600;">
                                <span style="margin-right: 8px;">🏁</span>End Date
                            </td>
                            <td style="
                                color: #0f172a; 
                                font-weight: 700; 
                                text-align: right; 
                                padding: 8px 0;">
                                ${endFormatted}
                            </td>
                        </tr>
                        
                        <tr style="background: #f8fafc;">
                            <td style="
                                color: #64748b; 
                                padding: 8px 0; 
                                font-weight: 600;">
                                <span style="margin-right: 8px;">⏱️</span>Duration
                            </td>
                            <td style="
                                color: #0f172a; 
                                font-weight: 700; 
                                text-align: right; 
                                padding: 8px 0;">
                                ${task.duration} day${task.duration > 1 ? 's' : ''}
                            </td>
                        </tr>
                        
                        ${task.category && task.category !== '-' ? `
                        <tr>
                            <td style="
                                color: #64748b; 
                                padding: 8px 0; 
                                font-weight: 600;">
                                <span style="margin-right: 8px;">📂</span>Category
                            </td>
                            <td style="
                                color: #0f172a; 
                                font-weight: 700; 
                                text-align: right; 
                                padding: 8px 0;">
                                ${task.category}
                            </td>
                        </tr>
                        ` : ''}
                        
                        ${task.technician && task.technician !== '-' ? `
                        <tr style="background: #f8fafc;">
                            <td style="
                                color: #64748b; 
                                padding: 8px 0; 
                                font-weight: 600;">
                                <span style="margin-right: 8px;">🔧</span>Technology
                            </td>
                            <td style="
                                color: #0f172a; 
                                font-weight: 700; 
                                text-align: right; 
                                padding: 8px 0;">
                                ${task.technician}
                            </td>
                        </tr>
                        ` : ''}
                    </table>
                    
                    <!-- Description -->
                    ${task.description && task.description !== '' ? `
                    <div style="
                        margin-top: 16px; 
                        padding: 14px; 
                        background: #eff6ff;
                        border-radius: 8px;
                        border-left: 4px solid #3b82f6;">
                        <div style="
                            color: #1e40af; 
                            font-size: 11px; 
                            font-weight: 700; 
                            text-transform: uppercase; 
                            margin-bottom: 8px;
                            letter-spacing: 0.5px;">
                            📝 Description
                        </div>
                        <div style="
                            color: #0f172a; 
                            font-size: 13px; 
                            line-height: 1.6;
                            font-weight: 500;">
                            ${task.description}
                        </div>
                    </div>
                    ` : ''}
                    
                    <!-- Footer -->
                    <div style="
                        margin-top: 14px; 
                        padding-top: 12px; 
                        border-top: 2px solid #e2e8f0; 
                        color: #94a3b8; 
                        font-size: 11px;
                        text-align: center;
                        font-weight: 600;
                        letter-spacing: 0.3px;">
                        Task ID: #${task.id}
                    </div>
                </div>
            </div>
        `;
        };

        // [ENHANCED] Task text template (tampilan di bar)
        gantt.templates.task_text = function(start, end, task) {
            return `<span style="font-weight: 500; font-size: 12px;">${task.text}</span>`;
        };

        // [ENHANCED] Grid row class untuk alternating colors
        gantt.templates.grid_row_class = function(start, end, task) {
            return "gantt-grid-row";
        };

        // ========================================
        // FILTER EVENT
        // ========================================
        gantt.attachEvent("onBeforeTaskDisplay", function(id, task) {
            if (activePlantFilter === 'all') {
                return true;
            }

            // Safety check null
            const tPlant = (task.plant || '').toLowerCase();
            const fFilter = activePlantFilter.toLowerCase();

            // Logic "Contains" agar "Plant A - Autowire" tetap muncul saat filter "Plant A"
            return tPlant.includes(fFilter);
        });


        // =====================================================================
        // TODAY MARKER
        // Menampilkan garis vertikal merah di posisi tanggal hari ini
        // =====================================================================
        gantt.plugins({
            tooltip: true,
            marker: true // ← tambahkan marker plugin
        });

        gantt.addMarker({
            start_date: new Date(),
            css: "today-marker",
            text: "Today",
            title: "Today: " + new Date().toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            })
        });
        // ========================================
        // INITIALIZE & RENDER
        // ========================================
        gantt.init(containerID);
        ganttInitialized = true;

        if (cleanTasks.length > 0) {
            gantt.parse({
                data: cleanTasks,
                links: []
            });
        } else {
            gantt.message({
                text: "No data available to display",
                type: "info",
                expire: 3000
            });
        }

        // Force redraw after load
        setTimeout(function() {
            gantt.render();
            console.log("✅ Gantt Chart Rendered with Enhanced Tooltips");
        }, 500);
    }

    /**
     * Filter Gantt by Plant
     */
    window.robust_filterByPlant = function(plant) {
        console.log("🔍 Filter Plant:", plant);

        if (!ganttInitialized || typeof gantt === 'undefined') {
            console.error("❌ Gantt belum diinisialisasi!");
            return;
        }

        // Update State
        activePlantFilter = plant;

        // Refresh Gantt
        gantt.refreshData();

        // Update UI Tombol - Reset semua
        document.querySelectorAll('.plant-filter-btn').forEach(btn => {
            btn.className =
                "plant-filter-btn px-3 py-1.5 text-xs font-semibold rounded-md bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 transition-all duration-200";
        });

        // Set active button
        let btnId;
        if (plant === 'all') {
            btnId = 'plant-filter-all';
        } else {
            btnId = 'plant-filter-' + slugify(plant);
        }

        const activeBtn = document.getElementById(btnId);

        if (activeBtn) {
            activeBtn.className =
                "plant-filter-btn px-3 py-1.5 text-xs font-semibold rounded-md bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-sm transition-all duration-200";
        } else {
            console.warn("⚠️ Tombol filter tidak ketemu:", btnId);
        }

        console.log("✅ Filter diterapkan:", plant);
    };

    /**
     * Change Gantt Zoom Level dengan MULTI-SCALE HEADER
     */
    window.robust_changeZoom = function(level) {
        console.log("🔎 Change Zoom:", level);

        if (!ganttInitialized || typeof gantt === 'undefined') {
            console.error("❌ Gantt belum diinisialisasi!");
            return;
        }

        // Update state
        activeZoomLevel = level;

        // Disable auto-fit
        gantt.config.fit_tasks = false;

        // Force wide date range
        forceWideDateRange();

        // [FIX] Update konfigurasi dengan MULTI-SCALE
        switch (level) {
            case 'day':
                gantt.config.min_column_width = 45;
                gantt.config.scale_height = 60;
                gantt.config.scales = [{
                        unit: "month",
                        step: 1,
                        format: "%F %Y"
                    },
                    {
                        unit: "day",
                        step: 1,
                        format: "%d %D"
                    }
                ];
                break;

            case 'week':
                gantt.config.min_column_width = 60;
                gantt.config.scale_height = 60;
                gantt.config.scales = [{
                        unit: "month",
                        step: 1,
                        format: "%F %Y"
                    },
                    {
                        unit: "week",
                        step: 1,
                        format: "Week #%W"
                    }
                ];
                break;

            case 'month':
                gantt.config.min_column_width = 150;
                gantt.config.scale_height = 60;
                gantt.config.scales = [{
                        unit: "year",
                        step: 1,
                        format: "%Y"
                    },
                    {
                        unit: "month",
                        step: 1,
                        format: "%F"
                    }
                ];
                break;
        }

        // Render ulang gantt
        gantt.render();

        // Update UI Tombol - Reset semua
        document.querySelectorAll('.zoom-btn-fh').forEach(btn => {
            btn.classList.remove('active');
        });

        // Set active button
        let activeBtn = document.getElementById('zoom-' + level);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }

        console.log("✅ Zoom diterapkan:", level);
    };

    /**
     * Toggle Export Menu
     */
    window.toggleExportMenu = function() {
        const menu = document.getElementById('exportMenu');
        if (menu) {
            menu.classList.toggle('hidden');
        }
    };

    /**
     * Export Dashboard to PDF
     */
    window.exportToPDF = async function() {
        try {
            // Check library availability
            if (typeof Swal === 'undefined' || typeof html2canvas === 'undefined' || typeof window.jspdf ===
                'undefined') {
                alert("Library PDF belum siap. Tunggu sebentar atau refresh.");
                return;
            }

            // Hide export menu
            const menu = document.getElementById('exportMenu');
            if (menu) menu.classList.add('hidden');

            // Show loading
            Swal.fire({
                title: 'Generating PDF...',
                html: '<div style="margin-top: 20px;"><div class="spinner-border text-primary" role="status"></div></div>',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            // Get dashboard element
            const element = document.getElementById('dashboard-content');

            // Generate canvas
            const canvas = await html2canvas(element, {
                scale: 1.8,
                logging: false,
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#F8FAFC'
            });

            const imgData = canvas.toDataURL('image/png');
            const {
                jsPDF
            } = window.jspdf;
            const pdf = new jsPDF({
                orientation: 'landscape',
                unit: 'mm',
                format: 'a4'
            });

            const pageWidth = pdf.internal.pageSize.getWidth();
            const pageHeight = pdf.internal.pageSize.getHeight();

            // Add header with gradient background
            pdf.setFillColor(30, 58, 95);
            pdf.rect(0, 0, pageWidth, 35, 'F');

            // Add header text
            pdf.setFontSize(20);
            pdf.setTextColor(255, 255, 255);
            pdf.text('Facilities Dashboard Report', 14, 15);

            // Add period info
            pdf.setFontSize(10);
            pdf.setTextColor(200, 200, 200);
            const monthInput = document.querySelector('input[name="month"]');
            const period = monthInput ? monthInput.value : new Date().toISOString().slice(0, 7);
            pdf.text('Period: ' + period, 14, 23);
            pdf.text('Export Date: ' + new Date().toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }), 14, 29);

            // Calculate image dimensions
            const imgProps = pdf.getImageProperties(imgData);
            const pdfWidth = pageWidth - 20;
            const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

            let heightLeft = pdfHeight;
            let position = 40;

            // Add image to PDF (with pagination if needed)
            pdf.addImage(imgData, 'PNG', 10, position, pdfWidth, pdfHeight);
            heightLeft -= (pageHeight - position);

            while (heightLeft > 0) {
                position = heightLeft - pdfHeight;
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 10, -(pdfHeight - heightLeft) + 10, pdfWidth, pdfHeight);
                heightLeft -= pageHeight;
            }

            // Save PDF
            pdf.save('Facilities_Dashboard_' + period + '.pdf');

            // Success notification
            Swal.fire({
                icon: 'success',
                title: 'Export Successful!',
                text: 'Your dashboard has been exported to PDF',
                timer: 2500,
                showConfirmButton: false
            });

        } catch (error) {
            console.error('PDF Export error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Export Failed',
                text: error.message || 'An error occurred while generating PDF',
                confirmButtonColor: '#3b82f6'
            });
        }
    };

    // ============================================================================
    // EVENT LISTENERS
    // ============================================================================

    /**
     * Close export menu when clicking outside
     */
    document.addEventListener('click', function(event) {
        const exportMenu = document.getElementById('exportMenu');
        const exportButton = event.target.closest('button[onclick*="toggleExportMenu"]');

        if (exportMenu && !exportMenu.contains(event.target) && !exportButton) {
            exportMenu.classList.add('hidden');
        }
    });

    // ============================================================================
    // CONSOLE STYLING
    // ============================================================================
    console.log(
        '%c🎨 Facilities Dashboard Enhanced%c\n' +
        '%cVersion: 2.0%c\n' +
        '%cFeatures: Multi-scale Gantt, Rich Tooltips, PDF Export',
        'color: #3b82f6; font-size: 20px; font-weight: bold;',
        '',
        'color: #10b981; font-size: 12px;',
        '',
        'color: #64748b; font-size: 11px;'
    );
</script>
