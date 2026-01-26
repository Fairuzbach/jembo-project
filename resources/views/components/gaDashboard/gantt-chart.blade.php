@props(['tasks'])

<div class="bg-gradient-to-br from-white to-slate-50 p-6 rounded-lg shadow-xl border border-slate-200 mb-8">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 mb-6 border-b-2 border-gradient-to-r from-yellow-400 to-orange-400 pb-4">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            {{-- Title --}}
            <div class="flex items-center gap-3">
                <div class="bg-gradient-to-br from-yellow-400 to-orange-500 p-2.5 rounded-lg shadow-md">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                </div>
                <div>
                    <h4 class="text-lg font-black text-slate-900 tracking-tight">
                        Timeline & Workload
                    </h4>
                    <p class="text-xs text-slate-500 font-medium">Gantt Chart Visualization</p>
                </div>
            </div>

            {{-- Controls Group --}}
            <div class="flex flex-wrap gap-3 items-center">
                {{-- Zoom Controls --}}
                <div class="flex gap-2 bg-white rounded-lg p-1 shadow-sm border border-slate-200">
                    <button type="button" onclick="changeZoom('day')" id="zoom-day"
                        class="zoom-btn px-4 py-2 text-xs font-semibold rounded-md transition-all duration-200 hover:bg-slate-50">
                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Hari
                    </button>
                    <button type="button" onclick="changeZoom('week')" id="zoom-week"
                        class="zoom-btn px-4 py-2 text-xs font-semibold rounded-md transition-all duration-200 hover:bg-slate-50">
                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Minggu
                    </button>
                    <button type="button" onclick="changeZoom('month')" id="zoom-month"
                        class="zoom-btn px-4 py-2 text-xs font-semibold bg-gradient-to-r from-yellow-400 to-orange-400 text-white rounded-md transition-all duration-200 shadow-sm">
                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Bulan
                    </button>
                </div>

                {{-- Action Buttons --}}
                <button type="button" onclick="gantt.exportToPDF()"
                    class="px-4 py-2 text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 rounded-lg border border-slate-200 transition-all duration-200 shadow-sm hover:shadow">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export PDF
                </button>

                <button type="button" onclick="gantt.render()"
                    class="px-4 py-2 text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 rounded-lg border border-slate-200 transition-all duration-200 shadow-sm hover:shadow">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
            </div>
        </div>

        {{-- Stats Bar --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-2">
            <div class="bg-white rounded-lg p-3 border border-slate-100 shadow-sm">
                <div class="text-xs text-slate-500 font-medium">Total Tasks</div>
                <div class="text-xl font-bold text-slate-900" id="total-tasks">0</div>
            </div>
            <div class="bg-white rounded-lg p-3 border border-slate-100 shadow-sm">
                <div class="text-xs text-slate-500 font-medium">In Progress</div>
                <div class="text-xl font-bold text-blue-600" id="progress-tasks">0</div>
            </div>
            <div class="bg-white rounded-lg p-3 border border-slate-100 shadow-sm">
                <div class="text-xs text-slate-500 font-medium">Completed</div>
                <div class="text-xl font-bold text-green-600" id="completed-tasks">0</div>
            </div>
            <div class="bg-white rounded-lg p-3 border border-slate-100 shadow-sm">
                <div class="text-xs text-slate-500 font-medium">Delayed</div>
                <div class="text-xl font-bold text-red-600" id="delayed-tasks">0</div>
            </div>
        </div>
    </div>

    {{-- CHART CONTAINER --}}
    <div class="bg-white rounded-lg shadow-inner border border-slate-200 overflow-hidden">
        <div id="gantt_here" style='width:100%; height:550px;'></div>
    </div>

    {{-- Legend --}}
    <div class="mt-4 flex flex-wrap gap-4 items-center justify-center text-xs">
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded" style="background-color: #17a2b8;"></div>
            <span class="text-slate-600 font-medium">Approved</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded" style="background-color: #ffc107;"></div>
            <span class="text-slate-600 font-medium">In Progress</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded" style="background-color: #28a745;"></div>
            <span class="text-slate-600 font-medium">Completed</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded bg-red-500"></div>
            <span class="text-slate-600 font-medium">Delayed</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-1 h-6 bg-gradient-to-b from-yellow-400 to-orange-500 rounded"></div>
            <span class="text-slate-600 font-medium">Today</span>
        </div>
    </div>

</div>

<style>
    /* Enhanced Task Styling */
    .gantt_task_line {
        border-radius: 6px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
    }

    .gantt_task_line:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        transform: translateY(-1px);
    }

    /* Grid & Scale Styling */
    .gantt_grid_scale,
    .gantt_task_scale {
        background: linear-gradient(to bottom, #f8fafc 0%, #f1f5f9 100%);
        color: #334155;
        font-weight: 700;
        border-bottom: 2px solid #e2e8f0;
    }

    .gantt_grid_head_cell {
        border-right: 1px solid #e2e8f0;
    }

    /* Row Styling */
    .gantt_row {
        border-bottom: 1px solid #f1f5f9;
    }

    .gantt_row:hover {
        background-color: #fefce8 !important;
    }

    /* Today Marker */
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

    /* Context Menu Styling */
    .gantt_menu {
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .gantt_menu_item {
        padding: 10px 16px;
        transition: all 0.15s ease;
        font-size: 13px;
        font-weight: 500;
    }

    .gantt_menu_item:hover {
        background: linear-gradient(to right, #fef3c7, #fed7aa);
        color: #92400e;
    }

    /* Scrollbar Styling */
    .gantt_layout_content::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    .gantt_layout_content::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 5px;
    }

    .gantt_layout_content::-webkit-scrollbar-thumb {
        background: linear-gradient(to bottom, #fbbf24, #f97316);
        border-radius: 5px;
    }

    .gantt_layout_content::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(to bottom, #f59e0b, #ea580c);
    }

    /* Tooltip Enhancement - FORCE OVERRIDE */
    .gantt_tooltip {
        background: white !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 10px !important;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
        padding: 14px !important;
        max-width: 320px !important;
        font-family: system-ui, -apple-system, sans-serif !important;
        opacity: 1 !important;
    }

    .gantt_tooltip * {
        background: transparent !important;
    }

    .gantt_tooltip .font-bold {
        color: #1e293b !important;
        font-size: 14px !important;
    }

    .gantt_tooltip .text-slate-800 {
        color: #1e293b !important;
    }

    .gantt_tooltip .text-slate-700 {
        color: #334155 !important;
    }

    .gantt_tooltip .text-slate-600 {
        color: #475569 !important;
    }

    .gantt_tooltip .text-blue-700 {
        color: #1d4ed8 !important;
    }

    .gantt_tooltip .text-green-700 {
        color: #15803d !important;
    }

    .gantt_tooltip .text-orange-700 {
        color: #c2410c !important;
    }

    .gantt_tooltip .border-gray-200 {
        border-color: #e5e7eb !important;
    }

    /* Zoom Button Active State */
    .zoom-btn.active {
        background: linear-gradient(to right, #fbbf24, #f97316);
        color: white;
        box-shadow: 0 2px 4px rgba(251, 191, 36, 0.3);
    }

    /* Animation for loading */
    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    .loading-pulse {
        animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    /* Custom Context Menu Styles */
    .custom-context-menu {
        background: white;
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        border: 1px solid #e2e8f0;
        overflow: hidden;
        min-width: 220px;
        font-family: system-ui, -apple-system, sans-serif;
    }

    .menu-header {
        padding: 12px 16px;
        background: linear-gradient(135deg, #fbbf24 0%, #f97316 100%);
        color: white;
        font-weight: 700;
        font-size: 13px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
    }

    .status-badge {
        font-size: 10px;
        padding: 3px 8px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.3);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .menu-divider {
        height: 1px;
        background: #e2e8f0;
        margin: 4px 0;
    }

    .menu-item {
        padding: 10px 16px;
        cursor: pointer;
        transition: all 0.15s ease;
        font-size: 13px;
        font-weight: 500;
        color: #334155;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .menu-item:hover {
        background: linear-gradient(to right, #fef3c7, #fed7aa);
        color: #92400e;
    }

    .menu-item svg {
        flex-shrink: 0;
    }

    /* Copy Toast Notification */
    .copy-toast {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        font-weight: 600;
        font-size: 14px;
        z-index: 10001;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.3s ease;
    }

    .copy-toast.show {
        opacity: 1;
        transform: translateY(0);
    }
</style>

<script>
    let currentZoom = 'month';

    // Fungsi Zoom dengan visual feedback
    function changeZoom(level) {
        if (window.gantt) {
            gantt.ext.zoom.setLevel(level);
            currentZoom = level;

            // Update button states
            document.querySelectorAll('.zoom-btn').forEach(btn => {
                btn.classList.remove('active', 'bg-gradient-to-r', 'from-yellow-400', 'to-orange-400',
                    'text-white', 'shadow-sm');
                btn.classList.add('hover:bg-slate-50');
            });

            const activeBtn = document.getElementById('zoom-' + level);
            if (activeBtn) {
                activeBtn.classList.add('active', 'bg-gradient-to-r', 'from-yellow-400', 'to-orange-400', 'text-white',
                    'shadow-sm');
                activeBtn.classList.remove('hover:bg-slate-50');
            }
        }
    }

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

                const endDate = gantt.date.parseDate(task.end_date, "xml_date");

                if (task.progress >= 1) {
                    completed++;
                } else if (endDate < today && task.progress < 1) {
                    delayed++;
                } else {
                    inProgress++;
                }
            }
        });

        document.getElementById('total-tasks').textContent = total;
        document.getElementById('progress-tasks').textContent = inProgress;
        document.getElementById('completed-tasks').textContent = completed;
        document.getElementById('delayed-tasks').textContent = delayed;
    }

    document.addEventListener("DOMContentLoaded", function() {

        // Cek library
        if (typeof gantt === "undefined") {
            console.error("Library DHTMLX Gantt belum terload! Cek 'npm run dev'");
            return;
        }

        // --- KONFIGURASI ZOOM ---
        var zoomConfig = {
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

        // --- KONFIGURASI DASAR ---
        gantt.config.date_format = "%Y-%m-%d";
        gantt.config.readonly = true;
        gantt.config.bar_height = 28;
        gantt.config.row_height = 40;
        gantt.config.scale_height = 54;
        gantt.config.autosize = true;
        gantt.config.show_progress = true;

        // Kolom Grid
        // --- AKTIFKAN PLUGINS DULU ---
        gantt.plugins({
            marker: true,
            tooltip: true,
            quick_info: true
        });

        // --- CONTEXT MENU CONFIGURATION ---
        gantt.attachEvent("onContextMenu", function(taskId, linkId, event) {
            event.preventDefault();

            if (taskId) {
                const task = gantt.getTask(taskId);
                showCustomContextMenu(event, task);
            }
            return false;
        });

        function showCustomContextMenu(event, task) {
            // Remove existing menu if any
            const existingMenu = document.getElementById('custom-gantt-menu');
            if (existingMenu) {
                existingMenu.remove();
            }

            // Create menu
            const menu = document.createElement('div');
            menu.id = 'custom-gantt-menu';
            menu.className = 'custom-context-menu';
            menu.style.position = 'fixed';
            menu.style.left = event.clientX + 'px';
            menu.style.top = event.clientY + 'px';
            menu.style.zIndex = '10000';

            const progress = Math.round(task.progress * 100);
            const status = progress >= 100 ? 'Completed' :
                (new Date(task.end_date) < new Date() && progress < 100 ? 'Delayed' : 'In Progress');

            menu.innerHTML = `
                <div class="menu-header">
                    <strong>${task.text}</strong>
                    <span class="status-badge status-${status.toLowerCase().replace(' ', '-')}">${status}</span>
                </div>
                <div class="menu-divider"></div>
                <div class="menu-item" onclick="viewTaskDetails('${task.id}')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    View Details
                </div>
                <div class="menu-item" onclick="scrollToTask('${task.id}')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Focus on Task
                </div>
                <div class="menu-item" onclick="showTaskDependencies('${task.id}')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Show Dependencies
                </div>
                <div class="menu-divider"></div>
                <div class="menu-item" onclick="exportTask('${task.id}')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export Task Info
                </div>
                <div class="menu-item" onclick="copyTaskDetails('${task.id}')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Copy Details
                </div>
            `;

            document.body.appendChild(menu);

            // Close menu when clicking outside
            setTimeout(() => {
                document.addEventListener('click', function closeMenu() {
                    menu.remove();
                    document.removeEventListener('click', closeMenu);
                });
            }, 100);
        }

        // Context Menu Functions
        window.viewTaskDetails = function(taskId) {
            const task = gantt.getTask(taskId);

            // Skip if empty data
            if (task.$no_data) {
                return;
            }

            const progress = Math.round(task.progress * 100);
            const startDate = gantt.date.date_to_str("%d %F %Y")(task.start_date);
            const endDate = gantt.date.date_to_str("%d %F %Y")(task.end_date);

            // Get division name
            let divisionName = "-";
            if (task.parent) {
                try {
                    const parentTask = gantt.getTask(task.parent);
                    divisionName = parentTask ? parentTask.text : "-";
                } catch (e) {
                    divisionName = "-";
                }
            }

            alert(`📋 Task Details:\n\n` +
                `🏢 Divisi: ${divisionName}\n` +
                `📝 Name: ${task.text}\n` +
                `📅 Start Date: ${startDate}\n` +
                `🏁 End Date: ${endDate}\n` +
                `⏱️ Duration: ${task.duration} days\n` +
                `📈 Progress: ${progress}%\n` +
                `🆔 ID: ${task.id}`);
        };

        window.scrollToTask = function(taskId) {
            gantt.showTask(taskId);
            gantt.selectTask(taskId);

            // Visual feedback
            const taskElement = document.querySelector(`[task_id="${taskId}"]`);
            if (taskElement) {
                taskElement.style.transition = 'all 0.3s ease';
                taskElement.style.transform = 'scale(1.05)';
                setTimeout(() => {
                    taskElement.style.transform = 'scale(1)';
                }, 300);
            }
        };

        window.showTaskDependencies = function(taskId) {
            const links = gantt.getLinks();
            const dependencies = links.filter(link =>
                link.source == taskId || link.target == taskId
            );

            if (dependencies.length === 0) {
                alert('ℹ️ This task has no dependencies.');
                return;
            }

            let depInfo = `🔗 Dependencies for this task:\n\n`;
            dependencies.forEach(link => {
                const sourceTask = gantt.getTask(link.source);
                const targetTask = gantt.getTask(link.target);
                if (link.source == taskId) {
                    depInfo += `→ Blocks: ${targetTask.text}\n`;
                } else {
                    depInfo += `← Depends on: ${sourceTask.text}\n`;
                }
            });

            alert(depInfo);
        };

        window.exportTask = function(taskId) {
            const task = gantt.getTask(taskId);
            const progress = Math.round(task.progress * 100);
            const startDate = gantt.date.date_to_str("%d-%m-%Y")(task.start_date);
            const endDate = gantt.date.date_to_str("%d-%m-%Y")(task.end_date);

            const taskData = {
                id: task.id,
                name: task.text,
                start_date: startDate,
                end_date: endDate,
                duration: task.duration,
                progress: progress + '%'
            };

            const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(taskData,
                null, 2));
            const downloadAnchor = document.createElement('a');
            downloadAnchor.setAttribute("href", dataStr);
            downloadAnchor.setAttribute("download", `task_${task.id}.json`);
            document.body.appendChild(downloadAnchor);
            downloadAnchor.click();
            downloadAnchor.remove();
        };

        window.copyTaskDetails = function(taskId) {
            const task = gantt.getTask(taskId);

            // Skip if empty data
            if (task.$no_data) {
                return;
            }

            const progress = Math.round(task.progress * 100);
            const startDate = gantt.date.date_to_str("%d %F %Y")(task.start_date);
            const endDate = gantt.date.date_to_str("%d %F %Y")(task.end_date);

            // Get division name
            let divisionName = "-";
            if (task.parent) {
                try {
                    const parentTask = gantt.getTask(task.parent);
                    divisionName = parentTask ? parentTask.text : "-";
                } catch (e) {
                    divisionName = "-";
                }
            }

            const details = `Divisi: ${divisionName}\n` +
                `Task: ${task.text}\n` +
                `Start: ${startDate}\n` +
                `End: ${endDate}\n` +
                `Duration: ${task.duration} days\n` +
                `Progress: ${progress}%`;

            navigator.clipboard.writeText(details).then(() => {
                // Show success message
                const toast = document.createElement('div');
                toast.className = 'copy-toast';
                toast.textContent = '✓ Copied to clipboard!';
                document.body.appendChild(toast);

                setTimeout(() => {
                    toast.classList.add('show');
                }, 10);

                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                }, 2000);
            });
        };

        // Kolom Grid
        gantt.config.columns = [{
                name: "text",
                label: "Divisi",
                tree: true,
                width: 280,
                resize: true,
                template: function(task) {
                    if (task.type === "project") {
                        // Hitung jumlah child tasks (reports)
                        const children = gantt.getChildren(task.id);
                        const reportCount = children.length;

                        return `<div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-900">${task.text}</span>
                                    <span class="report-badge">${reportCount} Report${reportCount !== 1 ? 's' : ''}</span>
                                </div>`;
                    }
                    return `<span class="text-slate-700">${task.text}</span>`;
                }
            },
            {
                name: "start_date",
                label: "Start",
                align: "center",
                width: 90,
                resize: true
            },
            {
                name: "duration",
                label: "Days",
                align: "center",
                width: 60
            },
            {
                name: "progress",
                label: "Progress",
                align: "center",
                width: 80,
                template: function(task) {
                    if (task.type === "project") {
                        return "-";
                    }
                    return Math.round(task.progress * 100) + "%";
                }
            }
        ];

        // Template untuk menampilkan "Tidak ada data" jika divisi kosong
        gantt.templates.grid_row_class = function(start, end, task) {
            if (task.type === "project") {
                return "divisi-row";
            }
            return "";
        };

        // Custom template untuk task text dengan handling empty division
        gantt.attachEvent("onTaskLoading", function(task) {
            if (task.type === "project" && task.$no_start) {
                task.start_date = new Date();
                task.end_date = new Date();
                task.duration = 0;
            }
            return true;
        });

        // Event setelah data di-parse
        gantt.attachEvent("onParse", function() {
            // Check untuk divisi yang tidak punya children
            gantt.eachTask(function(task) {
                if (task.type === "project") {
                    const children = gantt.getChildren(task.id);
                    if (children.length === 0) {
                        // Tambah placeholder "Tidak ada data"
                        gantt.addTask({
                            id: task.id + "_empty",
                            text: "Tidak ada data",
                            start_date: task.start_date || new Date(),
                            duration: 0,
                            parent: task.id,
                            type: "task",
                            readonly: true,
                            $no_data: true
                        });
                    }
                }
            });
        });

        // Enhanced Tooltip
        gantt.templates.tooltip_text = function(start, end, task) {
            // Skip tooltip for project/divisi
            if (task.type === "project") {
                const children = gantt.getChildren(task.id);
                return `<div class="font-semibold text-sm mb-2 text-slate-800">📁 ${task.text}</div>
                        <div class="text-xs text-slate-700">
                            <div><strong>Total Reports:</strong> ${children.length}</div>
                        </div>`;
            }

            // Skip tooltip for empty data
            if (task.$no_data) {
                return `<div class="text-xs text-gray-500 italic">Tidak ada data</div>`;
            }

            const progress = Math.round(task.progress * 100);
            const duration = task.duration;
            const status = progress >= 100 ? "✅ Completed" :
                (end < new Date() && progress < 100 ? "⚠️ Delayed" : "🔄 In Progress");

            // Get parent division name
            let divisionName = "-";

            // Cek apakah ada parent dan parent bukan 0
            if (task.parent && task.parent !== 0 && task.parent !== '0') {
                try {
                    const parentTask = gantt.getTask(task.parent);
                    if (parentTask && parentTask.text) {
                        divisionName = parentTask.text;
                    }
                } catch (e) {
                    // Jika parent tidak ditemukan, cari manual di data
                    gantt.eachTask(function(t) {
                        if (t.type === 'project' && t.id === task.parent) {
                            divisionName = t.text;
                            return false; // stop iteration
                        }
                    });
                }
            }

            return `<div class="font-bold text-sm mb-2 pb-2 border-b border-gray-200 text-slate-800">${task.text}</div>
                    <div class="text-xs space-y-1.5">
                        <div class="flex items-center gap-2">
                            <span class="text-slate-600">🏢 Divisi:</span>
                            <span class="font-semibold text-blue-700">${divisionName}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-600">📊 Status:</span>
                            <span class="font-semibold text-slate-800">${status}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-600">📅 Start:</span>
                            <span class="font-medium text-slate-700">${gantt.date.date_to_str("%d %F %Y")(start)}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-600">🏁 End:</span>
                            <span class="font-medium text-slate-700">${gantt.date.date_to_str("%d %F %Y")(end)}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-600">⏱️ Duration:</span>
                            <span class="font-medium text-slate-700">${duration} days</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-600">📈 Progress:</span>
                            <span class="font-semibold ${progress >= 100 ? 'text-green-700' : progress >= 50 ? 'text-blue-700' : 'text-orange-700'}">${progress}%</span>
                        </div>
                    </div>`;
        };

        // Task Colors based on Status
        gantt.templates.task_class = function(start, end, task) {
            // Jangan tampilkan bar untuk "Tidak ada data"
            if (task.$no_data) {
                return "no-data-task";
            }

            // Jika task punya custom color dari backend, gunakan class khusus
            if (task.color) {
                return "custom-color-task";
            }

            const progress = task.progress || 0;
            const today = new Date();

            if (progress >= 1) {
                return "completed-task";
            } else if (end < today && progress < 1) {
                return "delayed-task";
            } else {
                return "progress-task";
            }
        };

        // Apply custom color from backend if exists
        gantt.templates.task_style = function(start, end, task) {
            if (task.color) {
                return `background: ${task.color}; border: 2px solid ${task.color};`;
            }
            return "";
        };

        // Custom Task Colors
        gantt.templates.task_text = function(start, end, task) {
            return "";
        };

        // Grid Row Class untuk empty data
        gantt.templates.grid_row_class = function(start, end, task) {
            if (task.type === "project") {
                return "divisi-row";
            }
            if (task.$no_data) {
                return "empty-data-row";
            }
            return "";
        };

        // --- INISIALISASI ---
        gantt.init("gantt_here");

        // --- TODAY MARKER (Setelah Init) ---
        const today = new Date();
        gantt.addMarker({
            start_date: today,
            css: "gantt_marker",
            text: "TODAY",
            title: "Today: " + gantt.date.date_to_str("%d %M %Y")(today)
        });

        // --- LOAD DATA ---
        const tasks = @json($tasks ?? ['data' => [], 'links' => []]);

        if (tasks.data && tasks.data.length > 0) {
            gantt.parse(tasks);
            updateStats(tasks);
        } else {
            document.getElementById('gantt_here').innerHTML =
                '<div class="flex items-center justify-center h-full text-slate-500">' +
                '<div class="text-center loading-pulse">' +
                '<svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>' +
                '</svg>' +
                '<p class="font-semibold text-lg">No Timeline Data Available</p>' +
                '<p class="text-sm mt-2">Add tasks to see the Gantt chart</p>' +
                '</div></div>';
        }

        // Custom CSS for task types
        const style = document.createElement('style');
        style.textContent = `
            .completed-task .gantt_task_progress {
                background: linear-gradient(to right, #10b981, #059669) !important;
            }
            .completed-task {
                background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
                border: 2px solid #047857 !important;
            }
            .delayed-task .gantt_task_progress {
                background: linear-gradient(to right, #ef4444, #dc2626) !important;
            }
            .delayed-task {
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
                border: 2px solid #b91c1c !important;
            }
            .progress-task .gantt_task_progress {
                background: linear-gradient(to right, #3b82f6, #2563eb) !important;
            }
            .progress-task {
                background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
                border: 2px solid #1d4ed8 !important;
            }
            .no-data-task {
                display: none !important;
            }
            .empty-data-row {
                background-color: #fef2f2 !important;
                font-style: italic;
            }
            .empty-data-row .gantt_cell {
                color: #9ca3af !important;
            }
            /* Custom color dari backend */
            .custom-color-task {
                border-radius: 6px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
        `;
        document.head.appendChild(style);
    });
</script>
