@section('browser_title', 'User Monitor')

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center py-2">
            <div class="flex items-center gap-4">
                <div
                    class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#1E3A5F] to-[#2d5285] flex items-center justify-center text-white shadow-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 tracking-tight">User Activity Monitor</h2>
                    <p class="text-sm text-slate-500">Realtime — update setiap 5 detik</p>
                </div>
            </div>

            {{-- Status indicator --}}
            <div class="flex items-center gap-2">
                <div id="pulse-dot" class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></div>
                <span id="last-update" class="text-sm text-slate-500">Menghubungkan...</span>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8 space-y-6">

        {{-- STAT CARDS --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

            {{-- Total Aktif --}}
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">User Aktif Sekarang</p>
                <h3 id="stat-total" class="text-4xl font-extrabold text-emerald-600">
                    {{ count($activeUsers) }}
                </h3>
                <p class="text-sm text-slate-400 mt-1">Dalam 5 menit terakhir</p>
            </div>

            {{-- Halaman Terbanyak --}}
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Halaman Terbanyak</p>
                <h3 id="stat-top-page" class="text-xl font-extrabold text-slate-700 truncate">-</h3>
                <p class="text-sm text-slate-400 mt-1">Paling banyak dikunjungi</p>
            </div>

            {{-- Divisi Terbanyak --}}
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Divisi Terbanyak</p>
                <h3 id="stat-top-divisi" class="text-xl font-extrabold text-slate-700 truncate">-</h3>
                <p class="text-sm text-slate-400 mt-1">Divisi paling aktif</p>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-800">Daftar User Aktif</h3>
                <span id="table-count" class="text-xs font-bold bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full">
                    {{ count($activeUsers) }} user
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="text-left px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Nama</th>
                            <th class="text-left px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Divisi</th>
                            <th class="text-left px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Role</th>
                            <th class="text-left px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Halaman</th>
                            <th class="text-left px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Terakhir Aktif</th>
                        </tr>
                    </thead>
                    <tbody id="user-table-body">
                        @forelse($activeUsers as $u)
                            <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></div>
                                        <span class="font-semibold text-slate-800">{{ $u['name'] }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $u['divisi'] }}</td>
                                <td class="px-6 py-4">
                                    <span class="text-xs font-bold bg-blue-100 text-blue-700 px-2 py-1 rounded-full">
                                        {{ $u['role'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-500 font-mono text-xs">/{{ $u['current_url'] }}</td>
                                <td class="px-6 py-4 text-slate-500">{{ $u['last_activity'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                    Tidak ada user aktif saat ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        /**
         * POLLING: Ambil data user aktif setiap 5 detik
         * via endpoint GET /admin/monitor/data
         */
        function fetchActiveUsers() {
            fetch('{{ route('superadmin.monitor.data') }}')
                .then(res => res.json())
                .then(data => {
                    updateTable(data.active_users);
                    updateStats(data.active_users);
                    document.getElementById('last-update').textContent = 'Update: ' + data.timestamp;

                    // Pulse hijau = online
                    const dot = document.getElementById('pulse-dot');
                    dot.className = 'w-3 h-3 rounded-full bg-emerald-500 animate-pulse';
                })
                .catch(() => {
                    // Pulse merah = gagal fetch
                    const dot = document.getElementById('pulse-dot');
                    dot.className = 'w-3 h-3 rounded-full bg-red-500';
                    document.getElementById('last-update').textContent = 'Koneksi terputus...';
                });
        }

        /**
         * Update isi tabel berdasarkan data terbaru
         */
        function updateTable(users) {
            const tbody = document.getElementById('user-table-body');
            const count = document.getElementById('table-count');

            count.textContent = users.length + ' user';
            document.getElementById('stat-total').textContent = users.length;

            if (users.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                            Tidak ada user aktif saat ini.
                        </td>
                    </tr>`;
                return;
            }

            tbody.innerHTML = users.map(u => `
                <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></div>
                            <span class="font-semibold text-slate-800">${u.name}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-slate-600">${u.divisi}</td>
                    <td class="px-6 py-4">
                        <span class="text-xs font-bold bg-blue-100 text-blue-700 px-2 py-1 rounded-full">
                            ${u.role}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-slate-500 font-mono text-xs">/${u.current_url}</td>
                    <td class="px-6 py-4 text-slate-500">${u.last_activity}</td>
                </tr>
            `).join('');
        }

        /**
         * Update stat cards
         */
        function updateStats(users) {
            if (users.length === 0) {
                document.getElementById('stat-top-page').textContent = '-';
                document.getElementById('stat-top-divisi').textContent = '-';
                return;
            }

            // Hitung halaman terbanyak
            const pageCounts = {};
            users.forEach(u => {
                pageCounts[u.current_url] = (pageCounts[u.current_url] || 0) + 1;
            });
            const topPage = Object.entries(pageCounts).sort((a, b) => b[1] - a[1])[0];
            document.getElementById('stat-top-page').textContent = '/' + topPage[0] + ' (' + topPage[1] + 'x)';

            // Hitung divisi terbanyak
            const divisiCounts = {};
            users.forEach(u => {
                divisiCounts[u.divisi] = (divisiCounts[u.divisi] || 0) + 1;
            });
            const topDivisi = Object.entries(divisiCounts).sort((a, b) => b[1] - a[1])[0];
            document.getElementById('stat-top-divisi').textContent = topDivisi[0] + ' (' + topDivisi[1] + ' user)';
        }

        // Jalankan polling setiap 5 detik
        fetchActiveUsers();
        setInterval(fetchActiveUsers, 5000);
    </script>
</x-app-layout>
