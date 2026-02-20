<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg transition-colors">
    <div class="p-6 text-slate-900">

        {{-- Header Tabel & Search --}}
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <div class="w-full md:w-2/3">
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

            <div class="w-full md:w-auto flex flex-col md:flex-row gap-3 justify-end">
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
                <button @click="showSpkModal = true" type="button"
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
                </button>
            </div>
        </div>

        {{-- Data Tabel --}}
        <div class="overflow-x-auto">
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Aksi
                        </th>
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
                                <div class="text-sm font-bold text-indigo-600 font-mono">{{ $wo->ticket_num }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ \Carbon\Carbon::parse($wo->report_date)->format('d M Y') }} -
                                    {{ \Carbon\Carbon::parse($wo->report_time)->format('H:i') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-slate-900">{{ $wo->machine_name ?? '-' }}</div>
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
                                        'CANCELLED' => 'bg-rose-100 text-rose-800 ring-1 ring-inset ring-rose-600/20',
                                        default => 'bg-slate-100 text-slate-800',
                                    };
                                @endphp
                                <span
                                    class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full {{ $statusClass }}">
                                    {{ strtoupper(str_replace('_', ' ', $wo->improvement_status)) }}
                                    <div class="text-xs text-slate-400 ml-1 uppercase border-l pl-1 border-slate-300">
                                        {{ $wo->priority }}</div>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button type="button"
                                    @click="openDetailModal({{ Js::from($wo) }}, '{{ $wo->requester->name ?? '-' }}')"
                                    class="text-indigo-600 hover:text-indigo-900 mr-3">Detail</button>

                                @if (auth()->user()->id === $wo->requester_id && auth()->user()->role !== 'eng.admin')
                                    <button type="button"
                                        @click="openEditModal({{ Js::from($wo) }}, '{{ $wo->requester->name ?? '-' }}')"
                                        class="text-amber-600 hover:text-amber-900 font-bold ml-2">Edit Status</button>
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
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">Data Tidak Ditemukan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $workOrders->links() }}</div>
    </div>
</div>
