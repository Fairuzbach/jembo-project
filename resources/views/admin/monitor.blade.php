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
            {{-- MODULE VISIT STATS --}}
            <div class="glow-card rounded-2xl p-6">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
                    <div>
                        <p
                            style="font-size:10px;font-weight:700;color:#f59e0b;text-transform:uppercase;letter-spacing:0.2em;font-family:monospace;">
                            Module Visit Stats
                        </p>
                        <p id="visit-date" style="font-size:11px;color:#475569;font-family:monospace;margin-top:4px;">
                            {{ $visitStats['date'] }} · Today
                        </p>
                    </div>
                    <div style="text-align:right;">
                        <p style="font-size:10px;color:#475569;font-family:monospace;">Total Visits</p>
                        <p id="visit-total"
                            style="font-size:1.5rem;font-weight:800;color:#f59e0b;font-family:monospace;text-shadow:0 0 20px #f59e0b;">
                            {{ $visitStats['total'] }}
                        </p>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:16px;" id="visit-stats-container">

                    {{-- FH --}}
                    <div style="display:flex;align-items:center;gap:16px;">
                        <div
                            style="width:36px;height:36px;border-radius:8px;background:rgba(59,130,246,0.1);border:1px solid rgba(59,130,246,0.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <span style="font-size:10px;font-weight:700;color:#60a5fa;font-family:monospace;">FH</span>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div
                                style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                                <span style="font-size:12px;color:#94a3b8;font-family:monospace;">WO Facility</span>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <span id="visit-fh-count"
                                        style="font-size:13px;font-weight:700;color:#60a5fa;font-family:monospace;">
                                        {{ $visitStats['fh']['count'] }}
                                    </span>
                                    <span id="visit-fh-pct" style="font-size:10px;color:#475569;font-family:monospace;">
                                        {{ $visitStats['fh']['percent'] }}%
                                    </span>
                                </div>
                            </div>
                            <div style="height:4px;background:#1e293b;border-radius:999px;overflow:hidden;">
                                <div id="visit-fh-bar" class="progress-bar"
                                    style="height:100%;background:linear-gradient(90deg,#1d4ed8,#60a5fa);border-radius:999px;width:{{ $visitStats['fh']['percent'] }}%;transition:width 0.6s ease;">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- GA --}}
                    <div style="display:flex;align-items:center;gap:16px;">
                        <div
                            style="width:36px;height:36px;border-radius:8px;background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <span style="font-size:10px;font-weight:700;color:#34d399;font-family:monospace;">GA</span>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div
                                style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                                <span style="font-size:12px;color:#94a3b8;font-family:monospace;">General Affair</span>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <span id="visit-ga-count"
                                        style="font-size:13px;font-weight:700;color:#34d399;font-family:monospace;">
                                        {{ $visitStats['ga']['count'] }}
                                    </span>
                                    <span id="visit-ga-pct"
                                        style="font-size:10px;color:#475569;font-family:monospace;">
                                        {{ $visitStats['ga']['percent'] }}%
                                    </span>
                                </div>
                            </div>
                            <div style="height:4px;background:#1e293b;border-radius:999px;overflow:hidden;">
                                <div id="visit-ga-bar" class="progress-bar"
                                    style="height:100%;background:linear-gradient(90deg,#059669,#34d399);border-radius:999px;width:{{ $visitStats['ga']['percent'] }}%;transition:width 0.6s ease;">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ENG --}}
                    <div style="display:flex;align-items:center;gap:16px;">
                        <div
                            style="width:36px;height:36px;border-radius:8px;background:rgba(168,85,247,0.1);border:1px solid rgba(168,85,247,0.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <span
                                style="font-size:10px;font-weight:700;color:#c084fc;font-family:monospace;">ENG</span>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div
                                style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                                <span style="font-size:12px;color:#94a3b8;font-family:monospace;">Engineering</span>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <span id="visit-eng-count"
                                        style="font-size:13px;font-weight:700;color:#c084fc;font-family:monospace;">
                                        {{ $visitStats['eng']['count'] }}
                                    </span>
                                    <span id="visit-eng-pct"
                                        style="font-size:10px;color:#475569;font-family:monospace;">
                                        {{ $visitStats['eng']['percent'] }}%
                                    </span>
                                </div>
                            </div>
                            <div style="height:4px;background:#1e293b;border-radius:999px;overflow:hidden;">
                                <div id="visit-eng-bar" class="progress-bar"
                                    style="height:100%;background:linear-gradient(90deg,#7e22ce,#c084fc);border-radius:999px;width:{{ $visitStats['eng']['percent'] }}%;transition:width 0.6s ease;">
                                </div>
                            </div>
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
            {{-- SESSION HISTORY --}}
            <div class="glow-card rounded-2xl overflow-hidden">
                <div
                    style="padding:16px 24px;border-bottom:1px solid rgba(6,182,212,0.1);display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <h3
                            style="font-weight:700;color:#e2e8f0;font-family:monospace;font-size:13px;text-transform:uppercase;letter-spacing:0.15em;">
                            Session History
                        </h3>
                        <span
                            style="font-size:10px;font-family:monospace;color:#f59e0b;background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.2);padding:2px 8px;border-radius:4px;">
                            TODAY
                        </span>
                    </div>

                    {{-- Sound Alert Toggle --}}
                    <button id="sound-toggle" onclick="toggleSound()"
                        title="Toggle sound alert saat ada login/logout baru"
                        style="display:flex;align-items:center;gap:8px;padding:6px 14px;border-radius:8px;background:rgba(34,211,238,0.08);border:1px solid rgba(34,211,238,0.15);cursor:pointer;transition:all 0.2s ease;">
                        <svg id="sound-icon-on" style="width:14px;height:14px;color:#22d3ee;" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.536 8.464a5 5 0 010 7.072M12 6v12m0 0l-3-3m3 3l3-3M9 9H4.5A1.5 1.5 0 003 10.5v3A1.5 1.5 0 004.5 15H9l3 3V6L9 9z" />
                        </svg>
                        <svg id="sound-icon-off" style="width:14px;height:14px;color:#475569;display:none;"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15zM17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                        </svg>
                        <span id="sound-label"
                            style="font-size:10px;font-weight:700;color:#22d3ee;font-family:monospace;">
                            SOUND ON
                        </span>
                    </button>
                </div>

                {{-- History List --}}
                <div id="session-history-list" style="max-height:320px;overflow-y:auto;">
                    @forelse($sessionHistory as $event)
                        <div
                            style="display:flex;align-items:center;gap:16px;padding:12px 24px;border-bottom:1px solid rgba(6,182,212,0.05);">
                            {{-- Badge type --}}
                            <div style="flex-shrink:0;">
                                @if ($event['type'] === 'login')
                                    <span
                                        style="display:inline-block;font-size:10px;font-weight:700;font-family:monospace;color:#34d399;background:rgba(52,211,153,0.1);border:1px solid rgba(52,211,153,0.2);padding:2px 8px;border-radius:4px;width:56px;text-align:center;">
                                        LOGIN
                                    </span>
                                @else
                                    <span
                                        style="display:inline-block;font-size:10px;font-weight:700;font-family:monospace;color:#f87171;background:rgba(248,113,113,0.1);border:1px solid rgba(248,113,113,0.2);padding:2px 8px;border-radius:4px;width:56px;text-align:center;">
                                        LOGOUT
                                    </span>
                                @endif
                            </div>

                            {{-- Time --}}
                            <span style="font-size:11px;color:#475569;font-family:monospace;flex-shrink:0;width:56px;">
                                {{ $event['time'] }}
                            </span>

                            {{-- Name --}}
                            <span
                                style="font-size:13px;font-weight:600;color:#e2e8f0;font-family:monospace;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                {{ $event['name'] }}
                            </span>

                            {{-- Divisi --}}
                            <span style="font-size:11px;color:#64748b;font-family:monospace;flex-shrink:0;">
                                {{ $event['divisi'] }}
                            </span>
                        </div>
                    @empty
                        <div
                            style="padding:32px;text-align:center;color:#334155;font-family:monospace;font-size:12px;">
                            Belum ada aktivitas login/logout hari ini.
                        </div>
                    @endforelse
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
        // SOUND ALERT
        // Menggunakan Web Audio API — tidak butuh file audio eksternal
        // =====================================================================
        let soundEnabled = true;
        let prevUserCount = {{ count($activeUsers) }};

        /**
         * Generate beep menggunakan Web Audio API.
         * @param {number} freq     - Frekuensi Hz (pitch)
         * @param {number} duration - Durasi ms
         * @param {string} type     - 'sine' | 'square' | 'sawtooth'
         * @param {number} volume   - 0.0 - 1.0
         */
        function playBeep(freq = 880, duration = 150, type = 'sine', volume = 0.3) {
            try {
                const ctx = new(window.AudioContext || window.webkitAudioContext)();
                const oscillator = ctx.createOscillator();
                const gainNode = ctx.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(ctx.destination);

                oscillator.type = type;
                oscillator.frequency.value = freq;
                gainNode.gain.value = volume;

                // Fade out agar tidak terdengar kasar
                gainNode.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duration / 1000);

                oscillator.start(ctx.currentTime);
                oscillator.stop(ctx.currentTime + duration / 1000);
            } catch (e) {
                // Browser tidak support AudioContext — diam saja
            }
        }

        /**
         * Suara untuk event login — nada naik (cheerful)
         * Dua beep: rendah → tinggi
         */
        function playLoginSound() {
            playBeep(600, 120, 'sine', 0.25);
            setTimeout(() => playBeep(900, 180, 'sine', 0.2), 130);
        }

        /**
         * Suara untuk event logout — nada turun (muted)
         * Dua beep: tinggi → rendah
         */
        function playLogoutSound() {
            playBeep(700, 120, 'sine', 0.2);
            setTimeout(() => playBeep(450, 200, 'sine', 0.15), 130);
        }

        /**
         * Toggle sound on/off
         */
        function toggleSound() {
            soundEnabled = !soundEnabled;

            document.getElementById('sound-icon-on').style.display = soundEnabled ? 'block' : 'none';
            document.getElementById('sound-icon-off').style.display = soundEnabled ? 'none' : 'block';
            document.getElementById('sound-label').textContent = soundEnabled ? 'SOUND ON' : 'SOUND OFF';
            document.getElementById('sound-label').style.color = soundEnabled ? '#22d3ee' : '#475569';
            document.getElementById('sound-toggle').style.borderColor = soundEnabled ?
                'rgba(34,211,238,0.15)' : 'rgba(71,85,105,0.3)';
        }

        // =====================================================================
        // UPDATE SESSION HISTORY
        // =====================================================================
        let prevHistoryLength = {{ count($sessionHistory) }};

        function updateSessionHistory(history) {
            if (!history || history.length === 0) return;

            /**
             * Deteksi event baru — bandingkan panjang array
             * Jika bertambah, berarti ada login/logout baru
             */
            if (history.length > prevHistoryLength && soundEnabled) {
                const latestEvent = history[0]; // event terbaru selalu di index 0
                if (latestEvent.type === 'login') {
                    playLoginSound();
                } else {
                    playLogoutSound();
                }
            }
            prevHistoryLength = history.length;

            const container = document.getElementById('session-history-list');

            if (history.length === 0) {
                container.innerHTML = `
            <div style="padding:32px;text-align:center;color:#334155;font-family:monospace;font-size:12px;">
                Belum ada aktivitas login/logout hari ini.
            </div>`;
                return;
            }

            container.innerHTML = history.map(event => `
        <div style="display:flex;align-items:center;gap:16px;padding:12px 24px;border-bottom:1px solid rgba(6,182,212,0.05);">
            <div style="flex-shrink:0;">
                ${event.type === 'login'
                    ? '<span style="display:inline-block;font-size:10px;font-weight:700;font-family:monospace;color:#34d399;background:rgba(52,211,153,0.1);border:1px solid rgba(52,211,153,0.2);padding:2px 8px;border-radius:4px;width:56px;text-align:center;">LOGIN</span>'
                    : '<span style="display:inline-block;font-size:10px;font-weight:700;font-family:monospace;color:#f87171;background:rgba(248,113,113,0.1);border:1px solid rgba(248,113,113,0.2);padding:2px 8px;border-radius:4px;width:56px;text-align:center;">LOGOUT</span>'
                }
            </div>
            <span style="font-size:11px;color:#475569;font-family:monospace;flex-shrink:0;width:56px;">
                ${event.time}
            </span>
            <span style="font-size:13px;font-weight:600;color:#e2e8f0;font-family:monospace;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                ${event.name}
            </span>
            <span style="font-size:11px;color:#64748b;font-family:monospace;flex-shrink:0;">
                ${event.divisi}
            </span>
        </div>
    `).join('');
        }

        // =====================================================================
        // UPDATE fetchActiveUsers — tambah session_history
        // =====================================================================
        function fetchActiveUsers() {
            fetch('{{ route('superadmin.monitor.data') }}', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                })
                .then(res => res.json())
                .then(data => {
                    updateTable(data.active_users);
                    updateStats(data.active_users);
                    updateDivisionBreakdown(data.active_users);
                    updateVisitStats(data.visit_stats);
                    updateSessionHistory(data.session_history); // ← tambah ini
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
