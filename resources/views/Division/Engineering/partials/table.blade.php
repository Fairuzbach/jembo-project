{{-- Bungkus semuanya dengan x-data activeTab --}}
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg transition-colors" x-data="{
    activeTab: 'work_order',

    {{-- STATE UNTUK MODAL EDIT COMPOUND --}}
    showEditModal: false,
    editHtml: '',

    async openCompoundEdit(url) {
        // Munculkan layar loading sementara
        this.showEditModal = true;
        this.editHtml = '<div class=\'fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm\'><div class=\'animate-spin w-10 h-10 border-4 border-white border-t-transparent rounded-full\'></div></div>';

        try {
            // Fetch template modal dari server
            let response = await fetch(url);
            if (!response.ok) throw new Error('Gagal memuat data');

            // Inject HTML ke dalam DOM (Alpine akan otomatis membacanya)
            this.editHtml = await response.text();
        } catch (error) {
            this.editHtml = '<div class=\'fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm\'><div class=\'bg-white p-6 rounded-xl text-center shadow-xl\'><p class=\'text-rose-600 font-bold mb-4\'>Gagal Memuat Data dari Server</p><button @click=\'showEditModal = false\' class=\'px-5 py-2 bg-slate-100 rounded-lg hover:bg-slate-200 text-sm font-bold text-slate-700\'>Tutup</button></div></div>';
        }
    }
}">
    <div class="p-4 sm:p-6 text-slate-900">

        {{-- HEADER: TOMBOL AKSI GLOBAL --}}
        <div class="flex flex-col sm:flex-row sm:flex-wrap sm:justify-end gap-2 sm:gap-3 mb-6">

            {{-- Export Compound Data --}}
            <button @click="showExportCompoundModal = true" type="button"
                class="group bg-slate-50 hover:bg-slate-100 text-slate-700 font-semibold py-2.5 px-4 rounded-lg text-sm transition-all border border-slate-300 hover:border-slate-400 flex items-center gap-2 justify-center focus:ring-2 focus:ring-slate-400 focus:ring-offset-2">
                <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-600 transition-colors shrink-0" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                <span>Export Compound Data</span>
            </button>

            {{-- Export Data / Export Terpilih --}}
            <button @click="handleExportClick()" type="button"
                class="group bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-lg text-sm font-semibold flex justify-center items-center gap-2 transition-all shadow-sm hover:shadow-md focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                <svg class="w-4 h-4 text-emerald-100 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                <span
                    x-text="selectedTickets.length > 0 ? 'Export (' + selectedTickets.length + ') Terpilih' : 'Export Data'"></span>
            </button>

            {{-- Compound Parameter Checking --}}
            <button @click="showCompoundModal = true" type="button"
                class="group bg-violet-600 hover:bg-violet-700 text-white font-semibold py-2.5 px-4 rounded-lg text-sm transition-all shadow-md hover:shadow-lg flex items-center gap-2 justify-center focus:ring-2 focus:ring-violet-500 focus:ring-offset-2">
                <svg class="w-4 h-4 text-violet-100 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                    </path>
                </svg>
                <span>Compound Parameter Checking</span>
            </button>

        </div>

        {{-- MENU NAVIGASI TAB --}}
        <div class="flex overflow-x-auto mb-6 border-b border-slate-200 gap-0 -mx-1 px-1 scrollbar-hide">
            <button @click="activeTab = 'work_order'"
                :class="activeTab === 'work_order' ? 'border-indigo-500 text-indigo-600' :
                    'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                class="flex-none whitespace-nowrap pb-3 px-3 sm:px-4 border-b-2 font-medium text-sm transition-colors outline-none focus:outline-none">
                Laporan Work Order
            </button>

            <button @click="activeTab = 'compound'"
                :class="activeTab === 'compound' ? 'border-blue-500 text-blue-600' :
                    'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                class="flex-none whitespace-nowrap pb-3 px-3 sm:px-4 border-b-2 font-medium text-sm transition-colors outline-none focus:outline-none">
                Riwayat Compound
            </button>
        </div>


        {{-- KONTEN TAB 1: WORK ORDER --}}
        <div x-show="activeTab === 'work_order'" x-transition.opacity>

            {{-- Form Pencarian Work Order --}}
            <div class="mb-4">
                <form action="{{ route('eng.index') }}" method="GET" class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                    <div class="relative flex-1">
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
                    <div class="flex gap-2">
                        <select name="improvement_status" onchange="this.form.submit()"
                            class="flex-1 sm:w-40 bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5">
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
                        <button type="submit"
                            class="px-4 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition-colors">
                            Cari
                        </button>
                        @if (request('search') || request('improvement_status'))
                            <a href="{{ route('eng.index') }}"
                                class="px-3 py-2.5 text-sm font-medium text-slate-700 bg-white rounded-lg border border-slate-200 hover:bg-slate-100 hover:text-red-700 flex items-center justify-center whitespace-nowrap">
                                ✕
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- ============================================================ --}}
            {{-- DATA TABEL WORK ORDER — Desktop View (sm ke atas)             --}}
            {{-- ============================================================ --}}
            <div class="hidden sm:block overflow-x-auto border border-slate-200 rounded-lg">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left w-10">
                                <input type="checkbox" @click="toggleSelectAll()"
                                    :checked="pageIds.length > 0 && pageIds.every(id => selectedTickets.includes(id))"
                                    class="w-4 h-4 text-indigo-600 bg-slate-100 border-slate-300 rounded focus:ring-indigo-500 cursor-pointer">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Tiket / Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Mesin & Plant</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Judul dan Uraian</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @forelse ($workOrders as $wo)
                            <tr class="hover:bg-slate-50 transition-colors"
                                :class="selectedTickets.includes({{ $wo->id }}) ? 'bg-indigo-50' : ''">
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <input type="checkbox" value="{{ $wo->id }}" x-model="selectedTickets"
                                        class="w-4 h-4 text-indigo-600 bg-slate-100 border-slate-300 rounded focus:ring-indigo-500 cursor-pointer">
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-indigo-600 font-mono">{{ $wo->ticket_num }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ \Carbon\Carbon::parse($wo->report_date)->format('d M Y') }} -
                                        {{ \Carbon\Carbon::parse($wo->report_time)->format('H:i') }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-slate-900">{{ $wo->machine_name ?? '-' }}
                                    </div>
                                    <div class="text-xs text-slate-500">{{ $wo->plant ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-sm font-medium text-slate-900">
                                        {{ $wo->damaged_part ?? $wo->kerusakan }}</div>
                                    <div class="text-xs text-slate-500 truncate w-48">
                                        {{ Str::limit($wo->kerusakan_detail, 50) }}</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
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
                                        class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full {{ $statusClass }}">
                                        {{ strtoupper(str_replace('_', ' ', $wo->improvement_status)) }}
                                        <div
                                            class="text-xs text-slate-400 ml-1 uppercase border-l pl-1 border-slate-300">
                                            {{ $wo->priority }}
                                        </div>
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                    <button type="button"
                                        @click="openDetailModal({{ Js::from($wo) }}, '{{ $wo->requester->name ?? '-' }}')"
                                        class="text-indigo-600 hover:text-indigo-900 mr-2">Detail</button>

                                    @if (auth()->user()->id === $wo->requester_id && auth()->user()->role !== 'eng.admin')
                                        <button type="button"
                                            @click="openEditModal({{ Js::from($wo) }}, '{{ $wo->requester->name ?? '-' }}')"
                                            class="text-amber-600 hover:text-amber-900 font-bold ml-1">Edit
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

            {{-- ============================================================ --}}
            {{-- DATA CARD WORK ORDER — Mobile View (di bawah sm)             --}}
            {{-- ============================================================ --}}
            <div class="sm:hidden space-y-3">
                {{-- Pilih Semua (Mobile) --}}
                @if ($workOrders->count() > 0)
                    <div class="flex items-center gap-2 px-1 pb-1 border-b border-slate-200">
                        <input type="checkbox" @click="toggleSelectAll()"
                            :checked="pageIds.length > 0 && pageIds.every(id => selectedTickets.includes(id))"
                            class="w-4 h-4 text-indigo-600 bg-slate-100 border-slate-300 rounded focus:ring-indigo-500 cursor-pointer">
                        <span class="text-xs text-slate-500 font-medium">Pilih Semua</span>
                    </div>
                @endif

                @forelse ($workOrders as $wo)
                    @php
                        $statusClass = match ($wo->improvement_status) {
                            'OPEN' => 'bg-blue-100 text-blue-800 ring-1 ring-inset ring-blue-600/20',
                            'WIP' => 'bg-amber-100 text-amber-800 ring-1 ring-inset ring-amber-600/20',
                            'CLOSED' => 'bg-emerald-100 text-emerald-800 ring-1 ring-inset ring-emerald-600/20',
                            'CANCELLED' => 'bg-rose-100 text-rose-800 ring-1 ring-inset ring-rose-600/20',
                            default => 'bg-slate-100 text-slate-800',
                        };
                    @endphp
                    <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm transition-colors"
                        :class="selectedTickets.includes({{ $wo->id }}) ? 'border-indigo-300 bg-indigo-50/50' : ''">

                        {{-- Row 1: Checkbox + Ticket + Status --}}
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex items-start gap-2.5">
                                <input type="checkbox" value="{{ $wo->id }}" x-model="selectedTickets"
                                    class="mt-0.5 w-4 h-4 text-indigo-600 bg-slate-100 border-slate-300 rounded focus:ring-indigo-500 cursor-pointer shrink-0">
                                <div>
                                    <div class="text-sm font-bold text-indigo-600 font-mono leading-tight">
                                        {{ $wo->ticket_num }}</div>
                                    <div class="text-xs text-slate-400 mt-0.5">
                                        {{ \Carbon\Carbon::parse($wo->report_date)->format('d M Y') }}
                                        · {{ \Carbon\Carbon::parse($wo->report_time)->format('H:i') }}
                                    </div>
                                </div>
                            </div>
                            <span
                                class="shrink-0 px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full {{ $statusClass }}">
                                {{ strtoupper(str_replace('_', ' ', $wo->improvement_status)) }}
                            </span>
                        </div>

                        {{-- Row 2: Machine & Plant --}}
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="text-sm font-semibold text-slate-800">{{ $wo->machine_name ?? '-' }}</span>
                            <span class="text-xs text-slate-400">·</span>
                            <span
                                class="text-xs text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded font-medium">{{ $wo->plant ?? '-' }}</span>
                        </div>

                        {{-- Row 3: Title --}}
                        <div class="mb-3">
                            <div class="text-sm font-medium text-slate-900 leading-snug">
                                {{ $wo->damaged_part ?? $wo->kerusakan }}</div>
                            @if ($wo->kerusakan_detail)
                                <div class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                                    {{ Str::limit($wo->kerusakan_detail, 80) }}</div>
                            @endif
                        </div>

                        {{-- Row 4: Priority + Actions --}}
                        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                            <span
                                class="text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ $wo->priority }}</span>
                            <div class="flex items-center gap-3">
                                <button type="button"
                                    @click="openDetailModal({{ Js::from($wo) }}, '{{ $wo->requester->name ?? '-' }}')"
                                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 transition-colors">
                                    Detail
                                </button>

                                @if (auth()->user()->id === $wo->requester_id && auth()->user()->role !== 'eng.admin')
                                    <button type="button"
                                        @click="openEditModal({{ Js::from($wo) }}, '{{ $wo->requester->name ?? '-' }}')"
                                        class="text-xs font-semibold text-amber-600 hover:text-amber-800 px-3 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 transition-colors">
                                        Edit Status
                                    </button>
                                @endif

                                @if (auth()->user()->role === 'eng.admin')
                                    <button type="button"
                                        @click="openEditModal({{ Js::from($wo) }}, '{{ $wo->requester->name ?? '-' }}')"
                                        class="text-xs font-semibold text-slate-600 hover:text-slate-800 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 transition-colors">
                                        Edit
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-slate-500 bg-white border border-slate-200 rounded-xl">
                        <svg class="w-10 h-10 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-sm font-medium">Data Tidak Ditemukan</p>
                    </div>
                @endforelse
            </div>

            {{-- Pagination Work Order --}}
            <div class="mt-4">
                {{ $workOrders->appends(request()->except('wo_page'))->links() }}
            </div>
        </div>

        {{-- KONTEN TAB 2: COMPOUND --}}
        <div x-show="activeTab === 'compound'" style="display: none;" x-transition.opacity>

            {{-- ============================================================ --}}
            {{-- TABEL COMPOUND — Desktop View (sm ke atas)                   --}}
            {{-- ============================================================ --}}
            <div class="hidden sm:block overflow-x-auto border border-slate-200 rounded-lg">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold border-b">
                        <tr>
                            <th class="p-4">Tanggal & Plant</th>
                            <th class="p-4">Mesin</th>
                            <th class="p-4">Operator</th>
                            <th class="p-4 text-center">Pengawas</th>
                            <th class="p-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($compoundChecks as $check)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="p-4">
                                    <div class="font-bold text-slate-800">
                                        {{ \Carbon\Carbon::parse($check->tanggal_cek)->format('d M Y') }}</div>
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-600 uppercase">
                                        {{ $check->plant->name ?? 'Unknown Plant' }}
                                    </span>
                                </td>
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
                                <td class="p-4">
                                    <div class="text-xs font-bold text-slate-800 uppercase">
                                        {{ $check->diperiksa_oleh ?? 'Tanpa Nama' }}</div>
                                </td>
                                <td class="p-4">
                                    <div class="flex flex-col items-center justify-center text-center">
                                        <span
                                            class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Diketahui
                                            Oleh:</span>
                                        <span
                                            class="text-xs font-bold text-slate-800 uppercase">{{ $check->diketahui_oleh ?? '-' }}</span>
                                        <span
                                            class="text-[9px] font-medium text-indigo-600 bg-indigo-50 px-2.5 py-0.5 rounded-full mt-1 border border-indigo-100">
                                            {{ \App\Models\User::where('name', $check->diketahui_oleh)->value('job_level') ?? 'Foreman' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="p-4 text-right">
                                    <button type="button"
                                        @click="openCompoundEdit('{{ route('eng.compound.edit', [$check->plant_id, $check->tanggal_cek]) }}')"
                                        class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all border border-transparent hover:border-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        title="Edit Data">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                                stroke-width="2" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ============================================================ --}}
            {{-- CARD COMPOUND — Mobile View (di bawah sm)                    --}}
            {{-- ============================================================ --}}
            <div class="sm:hidden space-y-3">
                @foreach ($compoundChecks as $check)
                    <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                        {{-- Header: Tanggal + Plant --}}
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <div class="text-sm font-bold text-slate-800">
                                    {{ \Carbon\Carbon::parse($check->tanggal_cek)->format('d M Y') }}
                                </div>
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-600 uppercase mt-1">
                                    {{ $check->plant->name ?? 'Unknown Plant' }}
                                </span>
                            </div>
                            <button type="button"
                                @click="openCompoundEdit('{{ route('eng.compound.edit', [$check->plant_id, $check->tanggal_cek]) }}')"
                                class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors border border-indigo-100">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                        stroke-width="2" />
                                </svg>
                                Edit
                            </button>
                        </div>

                        {{-- Info Grid --}}
                        <div class="grid grid-cols-3 gap-3 pt-3 border-t border-slate-100">
                            <div class="text-center">
                                <div
                                    class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 font-black text-sm border border-blue-100 mx-auto mb-1">
                                    {{ $check->jumlah_mesin }}
                                </div>
                                <div class="text-[10px] text-slate-400 font-medium leading-tight">Mesin/Bak</div>
                            </div>
                            <div class="text-center">
                                <div class="text-xs font-bold text-slate-800 uppercase leading-snug">
                                    {{ $check->diperiksa_oleh ?? '-' }}</div>
                                <div class="text-[10px] text-slate-400 font-medium mt-0.5">Operator</div>
                            </div>
                            <div class="text-center">
                                <div class="text-xs font-bold text-slate-800 uppercase leading-snug">
                                    {{ $check->diketahui_oleh ?? '-' }}</div>
                                <span
                                    class="text-[9px] font-medium text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded-full border border-indigo-100 inline-block mt-0.5">
                                    {{ \App\Models\User::where('name', $check->diketahui_oleh)->value('job_level') ?? 'Foreman' }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination Compound --}}
            @if (isset($compoundChecks) && $compoundChecks->hasPages())
                <div class="mt-4">
                    {{ $compoundChecks->appends(request()->except('comp_page'))->links() }}
                </div>
            @endif
        </div>

        <div x-html="editHtml"></div>
    </div>
</div>
