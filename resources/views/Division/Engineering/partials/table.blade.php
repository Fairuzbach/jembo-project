{{-- Bungkus semuanya dengan x-data activeTab --}}
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg transition-colors" x-data="{ activeTab: 'work_order' }">
    <div class="p-6 text-slate-900">

        {{-- ============================== --}}
        {{-- HEADER: TOMBOL AKSI GLOBAL     --}}
        {{-- ============================== --}}
        <div class="flex flex-wrap justify-end gap-3 mb-6">
            <button @click="handleExportClick()" type="button"
                class="w-full md:w-auto bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium flex justify-center items-center gap-2 transition shadow-sm">
                <svg class="w-5 h-5 text-emerald-100 group-hover:text-white transition-colors" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                <span
                    x-text="selectedTickets.length > 0 ? 'Export (' + selectedTickets.length + ') Terpilih' : 'Export Data'"></span>
            </button>

            <button @click="showCompoundModal = true" type="button"
                class="group bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-5 rounded-lg text-sm transition-all shadow-md hover:shadow-lg flex items-center gap-2 w-full md:w-auto justify-center focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <svg class="w-5 h-5 text-blue-100 group-hover:text-white transition-colors" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                    </path>
                </svg>
                Compound Parameter Checking
            </button>
            {{-- <button @click="showSpkModal = true" type="button"
                class="group bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-5 rounded-lg text-sm transition-all shadow-md hover:shadow-lg flex items-center gap-2 w-full md:w-auto justify-center focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <svg class="w-5 h-5 text-blue-100 group-hover:text-white transition-colors" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                Buat SPK (Form Lengkap)
            </button>
            <button @click="showCreateModal = true" type="button"
                class="group bg-gradient-to-r from-indigo-600 to-indigo-600 hover:from-indigo-700 hover:to-indigo-700 text-white font-semibold py-2.5 px-5 rounded-lg text-sm transition-all shadow-md hover:shadow-lg flex items-center gap-2 w-full md:w-auto justify-center focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg class="w-5 h-5 text-indigo-100 group-hover:text-white transition-colors" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Buat Laporan Baru
            </button> --}}
        </div>

        {{-- ============================== --}}
        {{-- MENU NAVIGASI TAB              --}}
        {{-- ============================== --}}
        <div class="flex space-x-4 mb-6 border-b border-slate-200">
            <button @click="activeTab = 'work_order'"
                :class="activeTab === 'work_order' ? 'border-indigo-500 text-indigo-600' :
                    'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm transition-colors outline-none focus:outline-none">
                Laporan Work Order
            </button>

            <button @click="activeTab = 'compound'"
                :class="activeTab === 'compound' ? 'border-blue-500 text-blue-600' :
                    'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm transition-colors outline-none focus:outline-none">
                Riwayat Compound Parameter Checking
            </button>
        </div>

        {{-- ============================== --}}
        {{-- KONTEN TAB 1: WORK ORDER       --}}
        {{-- ============================== --}}
        <div x-show="activeTab === 'work_order'" x-transition.opacity>

            {{-- Form Pencarian Work Order --}}
            <div class="mb-4 w-full md:w-2/3">
                <form action="{{ route('eng.index') }}" method="GET" class="flex flex-col md:flex-row gap-3">
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-slate-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 20 20">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}"
                            class="block w-full p-2.5 pl-10 text-sm text-slate-900 border border-slate-300 rounded-lg bg-slate-50 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Cari Tiket, Plant, Mesin...">
                    </div>
                    <div class="w-full md:w-48">
                        <select name="improvement_status" onchange="this.form.submit()"
                            class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5">
                            <option value="">Filter Status</option>
                            <option value="OPEN" {{ request('improvement_status') == 'OPEN' ? 'selected' : '' }}>Open
                            </option>
                            <option value="WIP" {{ request('improvement_status') == 'WIP' ? 'selected' : '' }}>WIP
                            </option>
                            <option value="CLOSED" {{ request('improvement_status') == 'CLOSED' ? 'selected' : '' }}>
                                Closed</option>
                            <option value="cancelled"
                                {{ request('improvement_status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    @if (request('search') || request('improvement_status'))
                        <a href="{{ route('eng.index') }}"
                            class="p-2.5 text-sm font-medium text-slate-900 bg-white rounded-lg border border-slate-200 hover:bg-slate-100 hover:text-red-700 focus:z-10 focus:ring-2 focus:ring-indigo-700 focus:text-indigo-700 flex items-center justify-center gap-2 px-4 whitespace-nowrap">Reset</a>
                    @endif
                </form>
            </div>

            {{-- Data Tabel Work Order --}}
            <div class="overflow-x-auto border border-slate-200 rounded-lg">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left w-10">
                                <input type="checkbox" @click="toggleSelectAll()"
                                    :checked="pageIds.length > 0 && pageIds.every(id => selectedTickets.includes(id))"
                                    class="w-4 h-4 text-indigo-600 bg-slate-100 border-slate-300 rounded focus:ring-indigo-500 cursor-pointer">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Tiket / Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Mesin & Plant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Judul dan Uraian</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @forelse ($workOrders as $wo)
                            <tr class="hover:bg-slate-50 transition-colors"
                                :class="selectedTickets.includes({{ $wo->id }}) ? 'bg-indigo-50' : ''">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" value="{{ $wo->id }}" x-model="selectedTickets"
                                        class="w-4 h-4 text-indigo-600 bg-slate-100 border-slate-300 rounded focus:ring-indigo-500 cursor-pointer">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-indigo-600 font-mono">{{ $wo->ticket_num }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ \Carbon\Carbon::parse($wo->report_date)->format('d M Y') }} -
                                        {{ \Carbon\Carbon::parse($wo->report_time)->format('H:i') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-slate-900">{{ $wo->machine_name ?? '-' }}
                                    </div>
                                    <div class="text-xs text-slate-500">{{ $wo->plant ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-slate-900">
                                        {{ $wo->damaged_part ?? $wo->kerusakan }}</div>
                                    <div class="text-xs text-slate-500 truncate w-48">
                                        {{ Str::limit($wo->kerusakan_detail, 50) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusClass = match ($wo->improvement_status) {
                                            'OPEN' => 'bg-blue-100 text-blue-800 ring-1 ring-inset ring-blue-600/20',
                                            'WIP' => 'bg-amber-100 text-amber-800 ring-1 ring-inset ring-amber-600/20',
                                            'CLOSED'
                                                => 'bg-emerald-100 text-emerald-800 ring-1 ring-inset ring-emerald-600/20',
                                            'CANCELLED'
                                                => 'bg-rose-100 text-rose-800 ring-1 ring-inset ring-rose-600/20',
                                            default => 'bg-slate-100 text-slate-800',
                                        };
                                    @endphp
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full {{ $statusClass }}">
                                        {{ strtoupper(str_replace('_', ' ', $wo->improvement_status)) }}
                                        <div
                                            class="text-xs text-slate-400 ml-1 uppercase border-l pl-1 border-slate-300">
                                            {{ $wo->priority }}
                                        </div>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button type="button"
                                        @click="openDetailModal({{ Js::from($wo) }}, '{{ $wo->requester->name ?? '-' }}')"
                                        class="text-indigo-600 hover:text-indigo-900 mr-3">Detail</button>

                                    @if (auth()->user()->id === $wo->requester_id && auth()->user()->role !== 'eng.admin')
                                        <button type="button"
                                            @click="openEditModal({{ Js::from($wo) }}, '{{ $wo->requester->name ?? '-' }}')"
                                            class="text-amber-600 hover:text-amber-900 font-bold ml-2">Edit
                                            Status</button>
                                    @endif

                                    @if (auth()->user()->role === 'eng.admin')
                                        <button type="button"
                                            @click="openEditModal({{ Js::from($wo) }}, '{{ $wo->requester->name ?? '-' }}')"
                                            class="text-slate-600 hover:text-slate-900 font-bold">Edit</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">Data Tidak Ditemukan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Work Order --}}
            <div class="mt-4">
                {{-- Gunakan appends untuk mencegah bentrok pagination antar tab --}}
                {{ $workOrders->appends(request()->except('wo_page'))->links() }}
            </div>
        </div>

        {{-- ============================== --}}
        {{-- KONTEN TAB 2: COMPOUND         --}}
        {{-- ============================== --}}
        <div x-show="activeTab === 'compound'" style="display: none;" x-transition.opacity>

            {{-- Tabel Riwayat Pengecekan Compound (Dikelompokkan per Laporan) --}}
            <div class="overflow-x-auto border border-slate-200 rounded-lg">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold border-b">
                        <tr>
                            <th class="p-4">Tanggal & Plant</th>
                            <th class="p-4">Mesin</th>
                            <th class="p-4">Operator</th>
                            <th class="p-4 text-center">Foreman</th>
                            <th class="p-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($compoundChecks as $check)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                {{-- Kolom Tanggal & Plant --}}
                                <td class="p-4">
                                    <div class="font-bold text-slate-800">
                                        {{ \Carbon\Carbon::parse($check->tanggal_cek)->format('d M Y') }}</div>
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-600 uppercase">
                                        {{ $check->plant->name ?? 'Unknown Plant' }}
                                    </span>
                                </td>

                                {{-- Kolom Mesin (Perbaikan N/A) --}}
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-8 h-8 rounded bg-blue-50 flex items-center justify-center text-blue-600 font-black text-xs border border-blue-100">
                                            {{ $check->jumlah_mesin }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-700">Mesin/Bak Dicek</div>
                                            <div class="text-[10px] text-slate-400">Total Pengecekan Area</div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Kolom Petugas --}}
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div>
                                            <div class="text-xs font-bold text-slate-800 uppercase">
                                                {{ $check->diperiksa_oleh ?? 'Tanpa Nama' }}</div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Kolom Foreman --}}
                                <td class="p-4">
                                    <div class="flex flex-col items-center justify-center text-center">
                                        <span
                                            class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Diketahui
                                            Oleh:</span>
                                        <span
                                            class="text-xs font-bold text-slate-800 uppercase">{{ $check->diketahui_oleh ?? '-' }}</span>
                                        <span
                                            class="text-[9px] font-medium text-indigo-600 bg-indigo-50 px-2.5 py-0.5 rounded-full mt-1 border border-indigo-100">
                                            {{ auth()->user()->job_level }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Kolom Aksi --}}
                                <td class="p-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('eng.compound.edit', [$check->plant_id, $check->tanggal_cek]) }}"
                                            class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all border border-transparent hover:border-indigo-100"
                                            title="Edit Data">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                                    stroke-width="2" />
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination Compound --}}
            @if (isset($compoundChecks) && $compoundChecks->hasPages())
                <div class="mt-4">
                    {{ $compoundChecks->appends(request()->except('comp_page'))->links() }}
                </div>
            @endif
        </div>

    </div>
</div>
