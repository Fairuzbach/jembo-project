@props(['workOrders'])
<div
    class="bg-white rounded-2xl shadow-md border border-slate-100 overflow-hidden hover:shadow-xl transition-shadow duration-300">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            {{-- Table Header --}}
            <thead>
                <tr
                    class="bg-gradient-to-r from-slate-50 to-slate-100 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-600 font-extrabold">
                    <th class="px-6 py-5 w-10 text-center">
                        <input type="checkbox" @change="toggleSelectAll()"
                            :checked="selectedTickets.length === pageIds.length && pageIds.length > 0"
                            class="rounded border-slate-300 text-[#1E3A5F] focus:ring-[#1E3A5F] w-4 h-4 cursor-pointer">
                    </th>
                    <th class="px-6 py-5">Tiket</th>
                    <th class="px-6 py-5">Pemohon</th>
                    <th class="px-6 py-5">Lokasi</th>
                    <th class="px-6 py-5">Kategori Pekerjaan</th>
                    <th class="px-6 py-5">Status & PIC</th>
                    <th class="px-6 py-5 text-right">Action</th>
                </tr>
            </thead>

            {{-- Table Body --}}
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($workOrders as $wo)
                    <tr class="hover:bg-slate-50/50 transition-all duration-150 group">
                        {{-- Checkbox --}}
                        <td class="px-6 py-5 text-center align-top pt-6">
                            <input type="checkbox" value="{{ $wo->id }}" x-model="selectedTickets"
                                class="rounded border-slate-300 text-[#1E3A5F] focus:ring-[#1E3A5F] w-4 h-4 cursor-pointer transition-all">
                        </td>

                        {{-- Tiket Info --}}
                        <td class="px-6 py-5 align-top">
                            <div class="flex items-start gap-3">
                                <div
                                    class="h-10 w-10 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-[10px] group-hover:bg-[#1E3A5F] group-hover:text-white transition-all duration-200">
                                    WO
                                </div>
                                <div>
                                    <div class="font-bold text-slate-700 text-sm">
                                        {{ $wo->ticket_num }}</div>
                                    <div class="text-[11px] text-slate-400 mt-1">
                                        {{ $wo->report_date ? \Carbon\Carbon::parse($wo->report_date)->translatedFormat('d M Y') : '-' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Pemohon --}}
                        <td class="px-6 py-5 align-top">
                            <div class="text-sm font-bold text-slate-700">{{ $wo->requester_name }}
                            </div>
                        </td>

                        {{-- Lokasi --}}
                        <td class="px-6 py-5 align-top">
                            <div class="text-sm font-bold text-slate-700 mb-1">{{ $wo->plant }}
                            </div>
                            @if ($wo->machine)
                                <span
                                    class="inline-block px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-[10px] font-bold text-slate-600">
                                    {{ $wo->machine->name }}
                                </span>
                            @endif
                        </td>

                        {{-- Kategori --}}
                        <td class="px-6 py-5 align-top">
                            <span
                                class="inline-block px-3 py-1 rounded-full bg-slate-50 border border-slate-200 text-[11px] font-bold text-slate-600">
                                {{ $wo->category }}
                            </span>
                            <div class="text-[11px] text-slate-400 mt-2 max-w-[300px] truncate"
                                title="{{ $wo->description }}">
                                {{ $wo->description }}
                            </div>
                        </td>

                        {{-- Status & PIC --}}
                        <td class="px-6 py-5 align-top">
                            @php
                                $st = $wo->status;
                                $cls = match ($st) {
                                    'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'in_progress' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-slate-50 text-slate-700 border-slate-200',
                                };
                            @endphp
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-md border {{ $cls }} text-[10px] font-bold uppercase tracking-wide">
                                {{ str_replace('_', ' ', $st) }}
                            </span>

                            {{-- Technicians Avatar --}}
                            @if ($wo->technicians->count() > 0)
                                <div class="mt-2 flex -space-x-1 overflow-hidden">
                                    @foreach ($wo->technicians->take(3) as $tech)
                                        <div class="inline-flex h-6 w-6 rounded-full ring-2 ring-white bg-slate-200 items-center justify-center text-[9px] font-bold text-slate-600 hover:z-10 transition-transform hover:scale-110"
                                            title="{{ $tech->name }}">
                                            {{ substr($tech->name, 0, 1) }}
                                        </div>
                                    @endforeach
                                    @if ($wo->technicians->count() > 3)
                                        <div
                                            class="inline-flex h-6 w-6 rounded-full ring-2 ring-white bg-slate-100 items-center justify-center text-[9px] font-bold text-slate-500">
                                            +{{ $wo->technicians->count() - 3 }}
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </td>

                        {{-- Action Buttons --}}
                        <td class="px-6 py-5 align-top">
                            <div class="flex items-start justify-end gap-2">
                                @php
                                    $currRole = strtolower(auth()->user()->role ?? '');
                                    $currJabatan = strtolower(auth()->user()->jabatan ?? ''); // AMBIL JABATAN

                                    $userDivisi = strtolower(auth()->user()->divisi ?? '');
                                    $ticketPlant = strtolower($wo->plant);

                                    $canApprove = false;

                                    // 1. TAHAP APPROVAL PLANT
                                    if ($wo->status == 'waiting_approval') {
                                        // DEFINISI BOSS: Cek Jabatan ATAU Role
                                        $isBoss =
                                            str_contains($currJabatan, 'manager') ||
                                            str_contains($currJabatan, 'spv') ||
                                            str_contains($currJabatan, 'supervisor') ||
                                            str_contains($currRole, 'admin'); // mv.admin masuk sini

                                        // Cek Kesamaan Lokasi
                                        $isSamePlant = str_contains($userDivisi, $ticketPlant);

                                        // Admin Bypass
                                        $isAdminBypass = in_array($currRole, ['fh.admin', 'super.admin']);

                                        if (($isBoss && $isSamePlant) || $isAdminBypass) {
                                            $canApprove = true;
                                        }
                                    }
                                    // 2. TAHAP FACILITY
                                    elseif ($wo->status == 'waiting_facility_approval') {
                                        if (in_array($currRole, ['fh.admin', 'fh.spv', 'fh.manager', 'super.admin'])) {
                                            $canApprove = true;
                                        }
                                    }
                                @endphp

                                @if ($canApprove)
                                    {{-- Approve Button --}}
                                    <form action="{{ route('fh.approve', $wo->id) }}" method="POST"
                                        class="inline-block"
                                        onsubmit="return confirm('Apakah Anda yakin ingin menyetujui tiket ini?')">
                                        @csrf
                                        <button type="submit"
                                            class="group px-4 py-2 bg-emerald-500 text-white rounded-lg text-xs font-semibold transition-all duration-200 hover:bg-emerald-600 hover:shadow-lg hover:scale-105 active:scale-95 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 transition-transform group-hover:scale-110"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                            Approve
                                        </button>
                                    </form>

                                    {{-- Reject Button --}}
                                    <button type="button" onclick="promptReject('{{ route('fh.reject', $wo->id) }}')"
                                        class="group px-4 py-2 bg-rose-500 text-white rounded-lg text-xs font-semibold transition-all duration-200 hover:bg-rose-600 hover:shadow-lg hover:scale-105 active:scale-95 flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 transition-transform group-hover:rotate-90"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Reject
                                    </button>
                                @endif

                                {{-- Detail Button --}}
                                <button @click="openDetail({{ $wo->id }})"
                                    class="group px-4 py-2 bg-indigo-500 text-white rounded-lg text-xs font-semibold transition-all duration-200 hover:bg-indigo-600 hover:shadow-lg hover:scale-105 active:scale-95 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 transition-transform group-hover:scale-110" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Detail
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-16 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-16 h-16 text-slate-300 mb-4" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                <p class="text-slate-400 font-medium">Tiket tidak ditemukan</p>
                                <p class="text-slate-300 text-sm mt-1">Coba gunakan filter lain atau
                                    buat tiket baru</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
        {{ $workOrders->links() }}
    </div>
</div>
