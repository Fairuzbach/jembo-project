import { Chart } from 'chart.js';

      // 1. Definisikan Global Variables
        var activePlantFilter = 'all';
        var activeZoomLevel = 'month';
        var ganttInitialized = false;

        function slugify(text) {
            return text.toString().toLowerCase()
                .replace(/\s+/g, '-')
                .replace(/[^\w\-]+/g, '')
                .replace(/\-\-+/g, '-')
                .replace(/^-+/, '')
                .replace(/-+$/, '');
        }

        document.addEventListener("DOMContentLoaded", function() {
            console.log("🚀 Init Gantt (Robust ID: gantt_chart_robust)...");

            const containerID = "gantt_chart_robust";
            const ganttContainer = document.getElementById(containerID);

            if (!ganttContainer) {
                console.error("Container gantt tidak ditemukan!");
                return;
            }

            if (typeof gantt === 'undefined') {
                ganttContainer.innerHTML = "<p class='text-red-500 p-4'>Error: Library DHTMLX gagal dimuat.</p>";
                return;
            }

            // 2. AMBIL & BERSIHKAN DATA
            let rawData = window.facilitiesData.gantt;
            let cleanTasks = [];

            if (rawData && rawData.data && Array.isArray(rawData.data)) {
                cleanTasks = rawData.data.map(task => {
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
                        open: true
                    };
                });
            }

            console.log("✅ Data Bersih:", cleanTasks);

            // 3. CONFIG GANTT
            gantt.clearAll();
            gantt.config.xml_date = "%Y-%m-%d";
            gantt.config.fit_tasks = true;
            gantt.config.readonly = true;
            gantt.config.bar_height = 24;
            gantt.config.row_height = 38;

            // Set default zoom ke month
            gantt.config.scale_unit = "month";
            gantt.config.date_scale = "%F %Y";
            gantt.config.subscales = [];

            // Kolom
            gantt.config.columns = [{
                    name: "text",
                    label: "Task",
                    tree: true,
                    width: 220,
                    resize: true
                },
                {
                    name: "start_date",
                    label: "Start",
                    align: "center",
                    width: 90
                },
                {
                    name: "plant",
                    label: "Loc",
                    align: "center",
                    width: 60
                }
            ];

            // Mapping Class Warna
            gantt.templates.task_class = function(start, end, task) {
                return task.status ? "gantt-task-" + task.status.toLowerCase() : "gantt-task-default";
            };

            // Logic Filter
            gantt.attachEvent("onBeforeTaskDisplay", function(id, task) {
                if (activePlantFilter === 'all') return true;
                const tPlant = (task.plant || '').toLowerCase();
                const fFilter = activePlantFilter.toLowerCase();
                return tPlant.includes(fFilter);
            });

            // 4. INIT & RENDER
            gantt.init(containerID);
            ganttInitialized = true;

            if (cleanTasks.length > 0) {
                gantt.parse({
                    data: cleanTasks,
                    links: []
                });
            } else {
                ganttContainer.innerHTML = "<p class='text-gray-500 p-4'>Tidak ada data untuk ditampilkan.</p>";
            }

            // Force Redraw setelah load
            setTimeout(function() {
                gantt.render();
            }, 300);
        });

        // ==========================================
        // WINDOW FUNCTIONS (GLOBAL)
        // ==========================================

        window.filterByPlant = function(plant) {
            console.log("🔍 Filter Plant:", plant);

            if (!ganttInitialized || typeof gantt === 'undefined') {
                console.error("Gantt belum diinisialisasi!");
                return;
            }

            // Update State
            activePlantFilter = plant;

            // Refresh Gantt
            gantt.refreshData();

            // Update UI Tombol - Reset semua
            document.querySelectorAll('.plant-filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Set active button
            let btnId = 'plant-filter-' + (plant === 'all' ? 'all' : slugify(plant));
            let activeBtn = document.getElementById(btnId);
            if (activeBtn) {
                activeBtn.classList.add('active');
            }

            console.log("✅ Filter diterapkan:", plant);
        };

        window.changeZoom = function(level) {
            console.log("🔎 Change Zoom:", level);

            if (!ganttInitialized || typeof gantt === 'undefined') {
                console.error("Gantt belum diinisialisasi!");
                return;
            }

            // Update state
            activeZoomLevel = level;

            // Update konfigurasi gantt
            switch (level) {
                case 'day':
                    gantt.config.scale_unit = "day";
                    gantt.config.date_scale = "%d %M";
                    gantt.config.subscales = [];
                    break;
                case 'week':
                    gantt.config.scale_unit = "week";
                    gantt.config.date_scale = "Week #%W";
                    gantt.config.subscales = [{
                        unit: "day",
                        step: 1,
                        date: "%D"
                    }];
                    break;
                case 'month':
                    gantt.config.scale_unit = "month";
                    gantt.config.date_scale = "%F %Y";
                    gantt.config.subscales = [];
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

        window.toggleExportMenu = function() {
            const menu = document.getElementById('exportMenu');
            if (menu) {
                menu.classList.toggle('hidden');
            }
        };

        window.exportToPDF = function() {
            const element = document.getElementById('dashboard-content');

            // Cek Library
            if (typeof html2canvas === 'undefined' || typeof window.jspdf === 'undefined') {
                alert("Library Export sedang dimuat... silakan coba sesaat lagi.");
                return;
            }

            // SweetAlert Loading
            Swal.fire({
                title: 'Exporting PDF...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            html2canvas(element, {
                scale: 1.5,
                logging: false,
                useCORS: true
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const {
                    jsPDF
                } = window.jspdf;
                const pdf = new jsPDF('l', 'mm', 'a4');

                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = pdf.internal.pageSize.getHeight();
                const imgProps = pdf.getImageProperties(imgData);
                const ratio = (imgProps.height * pdfWidth) / imgProps.width;

                pdf.addImage(imgData, 'PNG', 0, 10, pdfWidth, ratio);

                pdf.setFontSize(10);
                pdf.text("Export Date: " + new Date().toLocaleDateString(), 10, 7);

                pdf.save("Facility_Dashboard.pdf");
                Swal.close();
            }).catch(err => {
                console.error(err);
                Swal.fire('Error', 'Gagal export PDF', 'error');
            });
        };

        // Close export menu when clicking outside
        document.addEventListener('click', function(event) {
            const exportMenu = document.getElementById('exportMenu');
            const exportButton = event.target.closest('button[onclick*="toggleExportMenu"]');

            if (exportMenu && !exportMenu.contains(event.target) && !exportButton) {
                exportMenu.classList.add('hidden');
            }
        });