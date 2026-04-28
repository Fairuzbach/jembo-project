@section('browser_title', 'System Monitor')

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center py-2">
            <div class="flex items-center gap-4">
                <div
                    class="w-12 h-12 rounded-2xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white shadow-lg shadow-cyan-500/30">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 tracking-tight">System Monitor</h2>
                    <div class="flex items-center gap-2 mt-0.5">
                        <div id="pulse-dot" class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                        <span id="last-update" class="text-xs text-slate-400 font-mono">Initializing...</span>
                    </div>
                </div>
            </div>
            <div class="text-right">
                <div id="live-clock" class="text-2xl font-mono font-bold text-slate-700 tabular-nums"></div>
                <div id="live-date" class="text-xs text-slate-400 font-mono"></div>
            </div>
        </div>
    </x-slot>

    <style>
        .monitor-bg {
            background-color: #0f172a;
            background-image:
                linear-gradient(rgba(6, 182, 212, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(6, 182, 212, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        .glow-card {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(6, 182, 212, 0.2);
            backdrop-filter: blur(12px);
            transition: all 0.3s ease;
        }

        .glow-card:hover {
            border-color: rgba(6, 182, 212, 0.5);
            box-shadow: 0 0 20px rgba(6, 182, 212, 0.1);
        }

        .stat-number {
            text-shadow: 0 0 20px currentColor;
        }

        /* Table styles */
        .monitor-table {
            width: 100%;
            border-collapse: collapse;
        }

        .monitor-table th {
            padding: 10px 16px;
            text-align: left;
            font-size: 10px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-family: monospace;
            border-bottom: 1px solid rgba(6, 182, 212, 0.1);
        }

        .monitor-table td {
            padding: 14px 16px;
            font-family: monospace;
            font-size: 12px;
            border-bottom: 1px solid rgba(6, 182, 212, 0.05);
        }

        .monitor-table tbody tr:hover {
            background: rgba(6, 182, 212, 0.04);
        }

        .monitor-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Online dot */
        .online-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 6px #10b981;
            margin-right: 8px;
            flex-shrink: 0;
        }

        /* Role badge */
        .role-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            background: rgba(59, 130, 246, 0.1);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.2);
            padding: 2px 8px;
            border-radius: 4px;
            font-family: monospace;
        }

        /* Scanline */
        @keyframes scanline {
            0% {
                transform: translateY(-100%);
            }

            100% {
                transform: translateY(100vh);
            }
        }

        .scanline {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(6, 182, 212, 0.3), transparent);
            animation: scanline 4s linear infinite;
            pointer-events: none;
            z-index: 9999;
        }

        /* Progress bar */
        @keyframes progress-fill {
            from {
                width: 0;
            }
        }

        .progress-bar {
            animation: progress-fill 0.5s ease-out;
        }
    </style>

    <div class="scanline"></div>

    <div class="monitor-bg min-h-screen py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- STAT CARDS --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                {{-- Active Sessions --}}
                <div class="glow-card rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <p
                            style="font-size:10px;font-weight:700;color:#22d3ee;text-transform:uppercase;letter-spacing:0.2em;font-family:monospace;">
                            Active Sessions
                        </p>
                        <div class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></div>
                    </div>
                    <h3 id="stat-total"
                        style="font-size:3rem;font-weight:800;color:#22d3ee;font-family:monospace;text-shadow:0 0 20px #22d3ee;">
                        {{ count($activeUsers) }}
                    </h3>
                    <p style="font-size:11px;color:#475569;margin-top:8px;font-family:monospace;">/ 5 min window</p>
                    <div style="margin-top:16px;display:flex;align-items:flex-end;gap:3px;height:32px;">
                        @for ($i = 0; $i < 14; $i++)
                            <div
                                style="flex:1;background:rgba(34,211,238,0.15);border-radius:2px;height:{{ rand(20, 100) }}%">
                            </div>
                        @endfor
                    </div>
                </div>

                {{-- Top Page --}}
                <div class="glow-card rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <p
                            style="font-size:10px;font-weight:700;color:#a78bfa;text-transform:uppercase;letter-spacing:0.2em;font-family:monospace;">
                            Top Page
                        </p>
                        <svg style="width:16px;height:16px;color:#a78bfa;" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <h3 id="stat-top-page"
                        style="font-size:1.1rem;font-weight:800;color:#a78bfa;font-family:monospace;text-shadow:0 0 20px #a78bfa;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        -
                    </h3>
                    <p style="font-size:11px;color:#475569;margin-top:8px;font-family:monospace;">most visited route</p>
                    <div id="stat-top-page-bar"
                        style="margin-top:16px;height:4px;background:#1e293b;border-radius:999px;overflow:hidden;">
                        <div class="progress-bar"
                            style="height:100%;background:linear-gradient(90deg,#7c3aed,#a78bfa);border-radius:999px;width:0%;">
                        </div>
                    </div>
                </div>

                {{-- Top Division --}}
                <div class="glow-card rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <p
                            style="font-size:10px;font-weight:700;color:#34d399;text-transform:uppercase;letter-spacing:0.2em;font-family:monospace;">
                            Top Division
                        </p>
                        <svg style="width:16px;height:16px;color:#34d399;" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0" />
                        </svg>
                    </div>
                    <h3 id="stat-top-divisi"
                        style="font-size:1.1rem;font-weight:800;color:#34d399;font-family:monospace;text-shadow:0 0 20px #34d399;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        -
                    </h3>
                    <p style="font-size:11px;color:#475569;margin-top:8px;font-family:monospace;">most active division
                    </p>
                    <div id="stat-top-divisi-bar"
                        style="margin-top:16px;height:4px;background:#1e293b;border-radius:999px;overflow:hidden;">
                        <div class="progress-bar"
                            style="height:100%;background:linear-gradient(90deg,#059669,#34d399);border-radius:999px;width:0%;">
                        </div>
                    </div>
                </div>
            </div>

            {{-- LIVE SESSIONS TABLE --}}
            <div class="glow-card rounded-2xl overflow-hidden">
                <div
                    style="padding:16px 24px;border-bottom:1px solid rgba(6,182,212,0.1);display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <h3
                            style="font-weight:700;color:#e2e8f0;font-family:monospace;font-size:13px;text-transform:uppercase;letter-spacing:0.15em;">
                            Live Sessions
                        </h3>
                        <span
                            style="font-size:10px;font-family:monospace;color:#22d3ee;background:rgba(34,211,238,0.1);border:1px solid rgba(34,211,238,0.2);padding:2px 8px;border-radius:4px;">
                            REALTIME
                        </span>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <span id="table-count"
                            style="font-size:12px;font-weight:700;font-family:monospace;color:#22d3ee;background:rgba(34,211,238,0.1);border:1px solid rgba(34,211,238,0.2);padding:4px 12px;border-radius:999px;">
                            {{ count($activeUsers) }} online
                        </span>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <span style="font-size:10px;color:#475569;font-family:monospace;">next refresh</span>
                            <span id="countdown"
                                style="font-size:10px;font-weight:700;color:#22d3ee;font-family:monospace;min-width:12px;">5</span>
                            <span style="font-size:10px;color:#475569;font-family:monospace;">s</span>
                        </div>
                    </div>
                </div>

                {{-- Table --}}
                <div style="overflow-x:auto;">
                    <table class="monitor-table">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>User</th>
                                <th>Division</th>
                                <th>Role</th>
                                <th>Current Page</th>
                                <th>Last Seen</th>
                            </tr>
                        </thead>
                        <tbody id="user-table-body">
                            @forelse($activeUsers as $index => $u)
                                <tr>
                                    <td style="color:#475569;">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                    <td>
                                        <div style="display:flex;align-items:center;">
                                            <span class="online-dot"></span>
                                            <span style="color:#e2e8f0;font-weight:600;">{{ $u['name'] }}</span>
                                        </div>
                                    </td>
                                    <td style="color:#94a3b8;">{{ $u['divisi'] }}</td>
                                    <td><span class="role-badge">{{ $u['role'] }}</span></td>
                                    <td style="color:#22d3ee;">/{{ $u['current_url'] }}</td>
                                    <td style="color:#475569;font-size:11px;">
                                        {{ \Carbon\Carbon::parse($u['last_activity'])->diffForHumans() }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align:center;padding:48px;color:#334155;">
                                        <div style="font-family:monospace;font-size:13px;">NO ACTIVE SESSIONS</div>
                                        <div style="font-family:monospace;font-size:11px;margin-top:8px;opacity:0.5;">
                                            Waiting for connections...</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- DIVISION BREAKDOWN --}}
            <div class="glow-card rounded-2xl p-6">
                <p
                    style="font-size:10px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.2em;font-family:monospace;margin-bottom:16px;">
                    Division Breakdown
                </p>
                <div id="division-breakdown" style="display:flex;flex-direction:column;gap:12px;">
                    <p style="color:#334155;font-family:monospace;font-size:12px;">Loading...</p>
                </div>
            </div>

        </div>
    </div>

    <script>
        // =====================================================================
        // LIVE CLOCK — gunakan timezone WIB (Asia/Jakarta)
        // =====================================================================
        function updateClock() {
            const now = new Date();
            const tzOptions = {
                timeZone: 'Asia/Jakarta'
            };

            document.getElementById('live-clock').textContent =
                now.toLocaleTimeString('id-ID', {
                    ...tzOptions,
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                });

            document.getElementById('live-date').textContent =
                now.toLocaleDateString('id-ID', {
                    ...tzOptions,
                    weekday: 'long',
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric'
                });
        }
        updateClock();
        setInterval(updateClock, 1000);

        // =====================================================================
        // COUNTDOWN TIMER
        // =====================================================================
        let countdown = 5;
        setInterval(() => {
            countdown--;
            if (countdown <= 0) countdown = 5;
            const el = document.getElementById('countdown');
            if (el) el.textContent = countdown;
        }, 1000);

        // =====================================================================
        // POLLING: Fetch data setiap 5 detik
        // =====================================================================
        function fetchActiveUsers() {
            fetch('{{ route('superadmin.monitor.data') }}')
                .then(res => res.json())
                .then(data => {
                    updateTable(data.active_users);
                    updateStats(data.active_users);
                    updateDivisionBreakdown(data.active_users);
                    document.getElementById('last-update').textContent = 'LIVE · ' + data.timestamp;
                    document.getElementById('pulse-dot').className =
                        'w-2 h-2 rounded-full bg-emerald-500 animate-pulse';
                })
                .catch(() => {
                    document.getElementById('pulse-dot').style.cssText =
                        'width:8px;height:8px;border-radius:50%;background:#ef4444;';
                    document.getElementById('last-update').textContent = 'CONNECTION LOST · Retrying...';
                });
        }

        // =====================================================================
        // UPDATE TABLE — pakai innerHTML dengan inline style (tidak perlu Tailwind)
        // =====================================================================
        function updateTable(users) {
            document.getElementById('table-count').textContent = users.length + ' online';
            document.getElementById('stat-total').textContent = users.length;

            const tbody = document.getElementById('user-table-body');

            if (users.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align:center;padding:48px;color:#334155;">
                            <div style="font-family:monospace;font-size:13px;">NO ACTIVE SESSIONS</div>
                            <div style="font-family:monospace;font-size:11px;margin-top:8px;opacity:0.5;">Waiting for connections...</div>
                        </td>
                    </tr>`;
                return;
            }

            tbody.innerHTML = users.map((u, i) => `
                <tr style="border-bottom:1px solid rgba(6,182,212,0.05);">
                    <td style="padding:14px 16px;font-family:monospace;font-size:12px;color:#475569;">
                        ${String(i + 1).padStart(2, '0')}
                    </td>
                    <td style="padding:14px 16px;">
                        <div style="display:flex;align-items:center;">
                            <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#10b981;box-shadow:0 0 6px #10b981;margin-right:10px;flex-shrink:0;"></span>
                            <span style="color:#e2e8f0;font-weight:600;font-family:monospace;font-size:13px;">${u.name}</span>
                        </div>
                    </td>
                    <td style="padding:14px 16px;font-family:monospace;font-size:12px;color:#94a3b8;">${u.divisi}</td>
                    <td style="padding:14px 16px;">
                        <span style="display:inline-block;font-size:10px;font-weight:700;background:rgba(59,130,246,0.1);color:#60a5fa;border:1px solid rgba(59,130,246,0.2);padding:2px 8px;border-radius:4px;font-family:monospace;">
                            ${u.role}
                        </span>
                    </td>
                    <td style="padding:14px 16px;font-family:monospace;font-size:12px;color:#22d3ee;">/${u.current_url}</td>
                    <td style="padding:14px 16px;font-family:monospace;font-size:11px;color:#475569;">
                        ${timeAgo(u.last_activity)}
                    </td>
                </tr>
            `).join('');
        }

        // =====================================================================
        // UPDATE STAT CARDS
        // =====================================================================
        function updateStats(users) {
            if (users.length === 0) {
                document.getElementById('stat-top-page').textContent = 'N/A';
                document.getElementById('stat-top-divisi').textContent = 'N/A';
                return;
            }

            // Top page
            const pageCounts = {};
            users.forEach(u => pageCounts[u.current_url] = (pageCounts[u.current_url] || 0) + 1);
            const topPage = Object.entries(pageCounts).sort((a, b) => b[1] - a[1])[0];
            document.getElementById('stat-top-page').textContent = '/' + topPage[0];

            // Top divisi
            const divisiCounts = {};
            users.forEach(u => divisiCounts[u.divisi] = (divisiCounts[u.divisi] || 0) + 1);
            const topDivisi = Object.entries(divisiCounts).sort((a, b) => b[1] - a[1])[0];
            document.getElementById('stat-top-divisi').textContent = topDivisi[0];

            // Progress bars
            const pageBar = document.querySelector('#stat-top-page-bar .progress-bar');
            const divisiBar = document.querySelector('#stat-top-divisi-bar .progress-bar');
            if (pageBar) pageBar.style.width = ((topPage[1] / users.length) * 100) + '%';
            if (divisiBar) divisiBar.style.width = ((topDivisi[1] / users.length) * 100) + '%';
        }

        // =====================================================================
        // DIVISION BREAKDOWN
        // =====================================================================
        function updateDivisionBreakdown(users) {
            const container = document.getElementById('division-breakdown');

            if (users.length === 0) {
                container.innerHTML = '<p style="color:#334155;font-family:monospace;font-size:12px;">No data.</p>';
                return;
            }

            const counts = {};
            users.forEach(u => counts[u.divisi] = (counts[u.divisi] || 0) + 1);
            const sorted = Object.entries(counts).sort((a, b) => b[1] - a[1]);
            const max = sorted[0][1];

            container.innerHTML = sorted.map(([divisi, count]) => `
                <div style="display:flex;align-items:center;gap:16px;">
                    <div style="width:140px;font-size:12px;color:#94a3b8;font-family:monospace;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex-shrink:0;">
                        ${divisi}
                    </div>
                    <div style="flex:1;height:4px;background:#1e293b;border-radius:999px;overflow:hidden;">
                        <div style="height:100%;background:linear-gradient(90deg,#0891b2,#22d3ee);border-radius:999px;width:${(count / max) * 100}%;transition:width 0.5s ease;">
                        </div>
                    </div>
                    <div style="font-size:12px;color:#22d3ee;font-family:monospace;font-weight:700;min-width:20px;text-align:right;flex-shrink:0;">
                        ${count}
                    </div>
                </div>
            `).join('');
        }

        // =====================================================================
        // HELPER: Time ago — WIB aware
        // =====================================================================
        function timeAgo(dateStr) {
            const diff = Math.floor((new Date() - new Date(dateStr)) / 1000);
            if (diff < 10) return 'just now';
            if (diff < 60) return diff + 's ago';
            if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
            return Math.floor(diff / 3600) + 'h ago';
        }

        // =====================================================================
        // START
        // =====================================================================
        fetchActiveUsers();
        setInterval(fetchActiveUsers, 5000);
    </script>
</x-app-layout>
