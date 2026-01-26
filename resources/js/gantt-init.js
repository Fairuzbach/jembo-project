import gantt from 'dhtmlx-gantt';
import 'dhtmlx-gantt/codebase/dhtmlxgantt.css';

// Make gantt available globally
window.gantt = gantt;

let currentZoom = 'month';

// Fungsi Zoom dengan visual feedback
window.changeZoom = function(level) {
    if (window.gantt) {
        gantt.ext.zoom.setLevel(level);
        currentZoom = level;
        
        // Update button states
        document.querySelectorAll('.zoom-btn').forEach(btn => {
            btn.classList.remove('active', 'bg-gradient-to-r', 'from-yellow-400', 'to-orange-400', 'text-white', 'shadow-sm');
            btn.classList.add('hover:bg-slate-50');
        });
        
        const activeBtn = document.getElementById('zoom-' + level);
        if (activeBtn) {
            activeBtn.classList.add('active', 'bg-gradient-to-r', 'from-yellow-400', 'to-orange-400', 'text-white', 'shadow-sm');
            activeBtn.classList.remove('hover:bg-slate-50');
        }
    }
};

// Update Statistics
function updateStats(tasks) {
    const taskData = tasks.data || [];
    const today = new Date();
    
    let total = 0;
    let inProgress = 0;
    let completed = 0;
    let delayed = 0;

    taskData.forEach(task => {
        if (task.type !== 'project') {
            total++;
            
            const endDate = gantt.date.parseDate(task.end_date || task.start_date, "xml_date");
            
            // Normalize progress to 0-1 range
            let progress = parseFloat(task.progress) || 0;
            if (progress > 1) progress = progress / 100; // Convert if it's 0-100 format
            
            // Get task status from metadata (if available)
            const taskStatus = task.status || '';
            
            // Task is completed if progress >= 100% (1.0)
            if (progress >= 1) {
                completed++;
            } else if (endDate < today && taskStatus === 'in_progress') {
                // Only count as delayed if overdue AND status is in_progress
                // Logika: Jika overdue DAN in_progress → MASUKKAN ke overdue
                // Jika belum overdue TETAPI in_progress → TIDAK overdue
                delayed++;
            } else if (taskStatus === 'in_progress' || (progress > 0 && progress < 1)) {
                // Count as in progress if status is in_progress or has progress but not completed
                inProgress++;
            } else {
                // Other statuses (approved, pending, etc.)
                inProgress++;
            }
        }
    });

    document.getElementById('total-tasks').textContent = total;
    document.getElementById('progress-tasks').textContent = inProgress;
    document.getElementById('completed-tasks').textContent = completed;
    document.getElementById('delayed-tasks').textContent = delayed;
}

// Initialize Gantt Chart
document.addEventListener("DOMContentLoaded", function() {
    
    // Cek library
    if (typeof gantt === "undefined") {
        console.error("Library DHTMLX Gantt belum terload! Pastikan sudah npm install & npm run dev");
        return;
    }

    // Cek apakah element gantt ada
    if (!document.getElementById('gantt_here')) {
        console.warn("Element #gantt_here tidak ditemukan di halaman");
        return;
    }

    // --- AKTIFKAN PLUGINS DULU ---
    gantt.plugins({
        marker: true,
        tooltip: true,
        quick_info: false  // Disable quick info dialog on left click
    });
    
    // Prevent default click behavior untuk task biasa, tapi izinkan collapse/expand untuk project
    gantt.attachEvent("onTaskClick", function(id, e) {
        // --- PERBAIKAN UTAMA DI SINI ---
        // Cek apakah 'e' ada? Jika tidak ada, hentikan fungsi agar tidak error.
        if (!e) {
            return true; 
        }
        // -------------------------------
    
        const task = gantt.getTask(id);
        const target = e.target || e.srcElement; // Sekarang aman diakses
    
        // Izinkan collapse/expand untuk project (divisi)
        if (task && task.type === "project") {
            
            // Cek jika klik pada icon expand/collapse - biarkan default behavior
            // Menggunakan optional chaining (?.) untuk keamanan tambahan
            if (target && (target.classList.contains('gantt_tree_icon') || 
                target.closest('.gantt_tree_icon'))) {
                return true; 
            }
    
            // Cek apakah klik pada area indentasi atau icon
            const isIconClick = target && (
                target.classList.contains('gantt_tree_icon') || 
                target.closest('.gantt_tree_icon') ||
                target.classList.contains('gantt_tree_indent')
            );
    
            if (!isIconClick) {
                // Toggle collapse/expand saat klik pada baris teks divisi
                if (gantt.isTaskOpen(id)) {
                    gantt.close(id);
                } else {
                    gantt.open(id);
                }
            }
            
            return false; // Prevent default quick info dialog
        }
    
        // Prevent default left-click behavior untuk task biasa (agar tidak muncul popup detail/edit)
        return false;  
    });

    // --- KONFIGURASI ZOOM ---
    const zoomConfig = {
        levels: [
            {
                name: "day",
                scale_height: 54,
                min_column_width: 80,
                scales: [
                    { unit: "month", step: 1, format: "%F %Y" },
                    { unit: "day", step: 1, format: "%d %M" }
                ]
            },
            {
                name: "week",
                scale_height: 54,
                min_column_width: 60,
                scales: [
                    { unit: "month", step: 1, format: "%F %Y" },
                    { unit: "week", step: 1, format: "Week #%W" }
                ]
            },
            {
                name: "month",
                scale_height: 54,
                min_column_width: 120,
                scales: [
                    { unit: "year", step: 1, format: "%Y" },
                    { unit: "month", step: 1, format: "%M" }
                ]
            }
        ]
    };

    gantt.ext.zoom.init(zoomConfig);
    gantt.ext.zoom.setLevel("month");

    // --- KONFIGURASI DASAR ---
    gantt.config.date_format = "%Y-%m-%d";
    gantt.config.readonly = true;
    gantt.config.bar_height = 28;
    gantt.config.row_height = 40;
    gantt.config.scale_height = 54;
    gantt.config.autosize = true;
    gantt.config.show_progress = true;

    // --- KOLOM GRID ---
    gantt.config.columns = [
        { 
            name: "text", 
            label: "Divisi / Ticket", 
            tree: true, 
            width: 320, 
            resize: true,
            template: function(task) {
                if (task.type === "project") {
                    const children = gantt.getChildren(task.id);
                    const reportCount = children.length;
                    
                    return `<div style="display: flex; align-items: center; gap: 8px; width: 100%;">
                                <span style="font-weight: bold; color: #1e293b; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${task.text}</span>
                                <span style="display: inline-flex; align-items: center; padding: 3px 10px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; font-size: 10px; font-weight: 700; border-radius: 12px; white-space: nowrap; flex-shrink: 0;">${reportCount} Ticket${reportCount !== 1 ? 's' : ''}</span>
                            </div>`;
                }
                return `<span style="color: #334155; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${task.text}">${task.text}</span>`;
            }
        },
        { 
            name: "start_date", 
            label: "Start", 
            align: "center", 
            width: 100, 
            resize: true 
        },
        { 
            name: "duration", 
            label: "Days", 
            align: "center", 
            width: 70,
            template: function(task) {
                if (task.type === "project") return "-";
                return task.duration || 0;
            }
        },
        { 
            name: "progress", 
            label: "Progress", 
            align: "center", 
            width: 90, 
            template: function(task) {
                if (task.type === "project") return "-";
                // Ensure progress is a number between 0-1
                let progress = parseFloat(task.progress) || 0;
                if (progress > 1) progress = progress / 100; // Convert if it's 0-100 format
                const progressPercent = Math.round(progress * 100);
                return `<span style="font-weight: 600; color: ${progressPercent >= 100 ? '#15803d' : progressPercent >= 50 ? '#1d4ed8' : '#ea580c'};">${progressPercent}%</span>`;
            }
        }
    ];

    // --- TEMPLATES ---
    
    // Row Class
    gantt.templates.grid_row_class = function(start, end, task) {
        if (task.type === "project") return "divisi-row";
        if (task.$no_data) return "empty-data-row";
        return "";
    };

    // Task Class
    gantt.templates.task_class = function(start, end, task) {
        if (task.$no_data) return "no-data-task";
        if (task.color) return "custom-color-task";
        
        // Normalize progress to 0-1 range
        let progress = parseFloat(task.progress) || 0;
        if (progress > 1) progress = progress / 100; // Convert if it's 0-100 format
        
        const today = new Date();
        
        // Get task status from metadata (if available)
        const taskStatus = task.status || '';
        
        // Task is completed if progress >= 100% (1.0)
        // Completed tasks should never be marked as delayed, even if overdue
        if (progress >= 1) return "completed-task";
        // Only mark as delayed if overdue AND status is in_progress
        // Logika: Jika overdue DAN in_progress → MASUKKAN ke overdue
        // Jika belum overdue TETAPI in_progress → TIDAK overdue
        else if (end < today && taskStatus === 'in_progress') return "delayed-task";
        else return "progress-task";
    };

    // Apply custom color from backend
    gantt.templates.task_style = function(start, end, task) {
        if (task.color) {
            return `background: ${task.color}; border: 2px solid ${task.color};`;
        }
        return "";
    };

    // Enhanced Tooltip dengan inline styles yang kuat
    gantt.templates.tooltip_text = function(start, end, task) {
        if (task.type === "project") {
            const children = gantt.getChildren(task.id);
            return `<div style="font-weight: 600; font-size: 14px; margin-bottom: 8px; color: #1e293b; background: transparent;">📁 ${task.text}</div>
                    <div style="font-size: 12px; color: #334155; background: transparent;">
                        <div style="background: transparent;"><strong>Total Tickets:</strong> ${children.length}</div>
                    </div>`;
        }

        if (task.$no_data) {
            return `<div style="font-size: 12px; color: #9ca3af; font-style: italic; background: transparent;">Tidak ada data</div>`;
        }

        // Normalize progress to 0-1 range, then convert to percentage
        let progress = parseFloat(task.progress) || 0;
        if (progress > 1) progress = progress / 100; // Convert if it's 0-100 format
        const progressPercent = Math.round(progress * 100);
        
        const duration = task.duration;
        const today = new Date();
        
        // Get task status from metadata
        const taskStatus = task.status || '';
        
        // Status: Completed tasks should never be marked as delayed, even if overdue
        // Only mark as delayed if overdue AND status is in_progress
        // Logika: Jika overdue DAN in_progress → MASUKKAN ke overdue
        // Jika belum overdue TETAPI in_progress → TIDAK overdue
        const status = progress >= 1 ? "✅ Completed" : 
                      (end < today && taskStatus === 'in_progress' ? "⚠️ Delayed" : "🔄 In Progress");
        
        let divisionName = task.division || "-";
        if (!divisionName || divisionName === "-") {
            if (task.parent && task.parent !== 0 && task.parent !== '0') {
                try {
                    const parentTask = gantt.getTask(task.parent);
                    if (parentTask && parentTask.text) {
                        divisionName = parentTask.text;
                    }
                } catch (e) {
                    // console.log("Could not get parent", e);
                }
            }
        }
        
        return `<div style="font-weight: bold; font-size: 14px; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb; color: #1e293b; background: transparent;">${task.text}</div>
                <div style="font-size: 12px; line-height: 1.8; background: transparent;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px; background: transparent;">
                        <span style="color: #475569; background: transparent;">🏢 Divisi:</span>
                        <span style="font-weight: 600; color: #1d4ed8; background: transparent;">${divisionName}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px; background: transparent;">
                        <span style="color: #475569; background: transparent;">📊 Status:</span>
                        <span style="font-weight: 600; color: #1e293b; background: transparent;">${status}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px; background: transparent;">
                        <span style="color: #475569; background: transparent;">📅 Start:</span>
                        <span style="font-weight: 500; color: #334155; background: transparent;">${gantt.date.date_to_str("%d %F %Y")(start)}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px; background: transparent;">
                        <span style="color: #475569; background: transparent;">🏁 End:</span>
                        <span style="font-weight: 500; color: #334155; background: transparent;">${gantt.date.date_to_str("%d %F %Y")(end)}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px; background: transparent;">
                        <span style="color: #475569; background: transparent;">⏱️ Duration:</span>
                        <span style="font-weight: 500; color: #334155; background: transparent;">${duration} days</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; background: transparent;">
                        <span style="color: #475569; background: transparent;">📈 Progress:</span>
                        <span style="font-weight: 600; color: ${progressPercent >= 100 ? '#15803d' : progressPercent >= 50 ? '#1d4ed8' : '#c2410c'}; background: transparent;">${progressPercent}%</span>
                    </div>
                </div>`;
    };

    // --- INISIALISASI ---
    gantt.plugins({
        export_api: true
    });
    gantt.init("gantt_here");

    // Fix tooltip flickering - Add styles before initialization
    const tooltipStyle = document.createElement('style');
    tooltipStyle.textContent = `
        .gantt_tooltip {
            background: white !important;
            border: 2px solid #e2e8f0 !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
            padding: 16px !important;
            border-radius: 10px !important;
            opacity: 1 !important;
            z-index: 99999 !important;
            pointer-events: auto !important;
            transition: opacity 0.2s ease !important;
        }
        .gantt_tooltip_box {
            pointer-events: auto !important;
        }
    `;
    document.head.appendChild(tooltipStyle);

    // Configure tooltip behavior to prevent flickering
    gantt.config.tooltip_offset_x = 10;
    gantt.config.tooltip_offset_y = 10;
    
    // Prevent tooltip from hiding on mouse movement
    let tooltipTimeout;
    gantt.attachEvent("onMouseMove", function(id, e) {
    // Safety check: pastikan e ada (kadang undefined di edge case tertentu)
    if (!e) return;

    // Don't close tooltip when mouse is over it
    const tooltip = document.querySelector('.gantt_tooltip');
    
    // Gunakan 'e.target', BUKAN 'id.target'
    if (tooltip && e.target && tooltip.contains(e.target)) {
        if (typeof tooltipTimeout !== 'undefined') {
             clearTimeout(tooltipTimeout);
        }
        return true;
    }
});

    // Delay hide event to prevent flickering
    gantt.attachEvent("onMouseLeave", function(event) {
        const tooltip = document.querySelector('.gantt_tooltip');
        if (tooltip && !tooltip.contains(event.target)) {
            tooltipTimeout = setTimeout(function() {
                if (tooltip && tooltip.parentNode) {
                    tooltip.remove();
                }
            }, 100);
        }
    });

    // --- TODAY MARKER ---
    const today = new Date();
    gantt.addMarker({
        start_date: today,
        css: "gantt_marker",
        text: "TODAY",
        title: "Today: " + gantt.date.date_to_str("%d %M %Y")(today)
    });

    // --- LOAD DATA dari PHP ---
    // Data sudah di-inject via Blade ke window variable
    const tasks = window.gaGanttData || { data: [], links: [] };

    // console.log("Loading Gantt Data:", tasks);

    if (tasks.data && tasks.data.length > 0) {
        gantt.parse(tasks);
        updateStats(tasks);
        
        // Pastikan collapse/expand berfungsi dengan baik
        // DHTMLX Gantt dengan tree:true sudah menyediakan collapse/expand secara default
        // Tapi kita perlu memastikan event handler tidak menghalangi
        setTimeout(function() {
            // Tambahkan event listener untuk klik pada baris divisi
            const gridContainer = document.querySelector('.gantt_grid_data');
            if (gridContainer) {
                gridContainer.addEventListener('click', function(e) {
                    const row = e.target.closest('.gantt_row.divisi-row');
                    if (row) {
                        const taskId = row.getAttribute('data-id');
                        if (taskId) {
                            const task = gantt.getTask(taskId);
                            if (task && task.type === "project") {
                                // Toggle collapse/expand
                                if (gantt.isTaskOpen(taskId)) {
                                    gantt.close(taskId);
                                } else {
                                    gantt.open(taskId);
                                }
                            }
                        }
                    }
                }, true); // Use capture phase untuk memastikan event terdeteksi
            }
        }, 200);
    } else {
        document.getElementById('gantt_here').innerHTML = 
            '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #64748b;">' +
            '<div style="text-align: center;">' +
            '<svg style="width: 64px; height: 64px; margin: 0 auto 16px; color: #cbd5e1;" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>' +
            '</svg>' +
            '<p style="font-weight: 600; font-size: 18px;">No Timeline Data Available</p>' +
            '<p style="font-size: 14px; margin-top: 8px;">Pilih rentang tanggal atau pastikan ada data dengan target completion date</p>' +
            '</div></div>';
    }

    // Apply custom styles
    const style = document.createElement('style');
    style.textContent = `
        .divisi-row {
            background: linear-gradient(to right, #fef3c7, #fefce8) !important;
            font-weight: 700;
            cursor: pointer;
        }
        .divisi-row:hover {
            background: linear-gradient(to right, #fde68a, #fed7aa) !important;
        }
        /* Styling untuk icon expand/collapse */
        .gantt_tree_icon {
            cursor: pointer !important;
            width: 20px !important;
            height: 20px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 4px !important;
            transition: all 0.2s ease !important;
            margin-right: 6px !important;
            flex-shrink: 0 !important;
        }
        .gantt_tree_icon:hover {
            background-color: rgba(0, 0, 0, 0.1) !important;
            transform: scale(1.1);
        }
        /* Pastikan icon terlihat jelas */
        .gantt_tree_icon:before {
            font-size: 14px !important;
            font-weight: bold !important;
            color: #475569 !important;
        }
        /* Styling untuk row divisi agar seluruh baris bisa diklik */
        .divisi-row .gantt_cell {
            cursor: pointer !important;
        }
        .custom-color-task {
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .no-data-task {
            display: none !important;
        }
        .gantt_marker {
            background: linear-gradient(to bottom, #fbbf24 0%, #f97316 100%);
            opacity: 0.9;
            z-index: 1;
        }
        .gantt_marker_content {
            background: linear-gradient(135deg, #fbbf24 0%, #f97316 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 11px;
            box-shadow: 0 2px 8px rgba(251, 191, 36, 0.4);
            letter-spacing: 0.5px;
        }
        /* Prevent tooltip flickering */
        .gantt_tooltip {
            pointer-events: auto !important;
            will-change: transform, opacity;
        }
        /* Completed Task */
        .completed-task .gantt_task_progress {
            background: linear-gradient(to right, #10b981, #059669) !important;
        }
        .completed-task {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            border: 2px solid #047857 !important;
        }
        /* Delayed Task - Red */
        .delayed-task .gantt_task_progress {
            background: linear-gradient(to right, #ef4444, #dc2626) !important;
        }
        .delayed-task {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            border: 2px solid #991b1b !important;
            box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3) !important;
        }
        /* Progress Task */
        .progress-task .gantt_task_progress {
            background: linear-gradient(to right, #3b82f6, #2563eb) !important;
        }
        .progress-task {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
            border: 2px solid #1d4ed8 !important;
        }
    `;
    document.head.appendChild(style);
});

// Export untuk digunakan di tempat lain jika perlu
export { updateStats };