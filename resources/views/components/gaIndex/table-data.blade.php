@props(['workOrders'])

<div class="bg-white shadow-xl rounded-sm overflow-hidden border border-slate-200">
    {{-- ALERT BANNER --}}
    @if (session('alert-action'))
        <div class="mb-6 bg-orange-50 border-l-4 border-orange-500 p-4 rounded-r shadow-md" role="alert">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-orange-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-orange-700 font-bold">{{ session('alert-action')['message'] }}</p>
                    <p class="text-sm text-orange-700 mt-1">{{ session('alert-action')['instruction'] }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-900">
                <tr>
                    <th class="px-6 py-4 w-10">
                        <input type="checkbox" @change="toggleSelectAll()"
                            :checked="pageIds.length > 0 && pageIds.every(id => selected.includes(String(id)))"
                            class="rounded-sm border-slate-600 bg-slate-700 text-yellow-400 focus:ring-offset-slate-900 focus:ring-yellow-400 cursor-pointer">
                    </th>
                    @foreach (['Tiket', 'Pelapor', 'Lokasi / Dept', 'Parameter', 'Bobot', 'Uraian', 'Diterima Oleh', 'Status', 'Aksi'] as $head)
                        <th
                            class="px-6 py-4 text-left text-[11px] font-black text-white uppercase tracking-widest {{ $head == 'Tiket' ? 'text-yellow-400' : '' }}">
                            {{ $head }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody class="bg-white divide-y divide-slate-100">
                @forelse ($workOrders as $index => $item)
                    <tr
                        class="hover:bg-yellow-50/50 transition-colors duration-150 group {{ $index % 2 == 0 ? 'bg-white' : 'bg-slate-50/30' }}">

                        {{-- Checkbox --}}
                        <td class="px-6 py-4">
                            <input type="checkbox" value="{{ (string) $item->id }}" x-model="selected"
                                class="rounded-sm border-slate-300 text-slate-900 focus:ring-yellow-400 cursor-pointer" />
                        </td>

                        {{-- Tiket --}}
                        <td class="px-6 py-4">
                            <div
                                class="text-sm font-black text-slate-900 font-mono group-hover:text-blue-600 transition-colors">
                                {{ $item->ticket_num }}
                            </div>
                            <div class="text-[10px] text-slate-400 font-bold mt-0.5 uppercase">
                                {{ $item->created_at->format('d M Y') }}
                            </div>
                        </td>

                        {{-- Pelapor --}}
                        <td class="px-6 py-4">
                            <div class="text-xs font-bold text-slate-700">{{ $item->requester_name }}</div>
                            @if ($item->requester_department)
                                <div class="text-[10px] font-bold text-slate-400 mt-1">Dept:
                                    {{ $item->requester_department }}</div>
                            @endif
                        </td>

                        {{-- Lokasi / Dept --}}
                        <td class="px-6 py-4">
                            <div class="flex flex-col gap-1 items-start">
                                @if ($item->plant)
                                    <span
                                        class="px-2 py-0.5 rounded-sm text-[10px] font-black bg-slate-100 text-slate-600 border border-slate-200 uppercase tracking-tight">
                                        LOC: {{ $item->plantInfo->name ?? $item->plant }}
                                    </span>
                                @endif
                                @if ($item->department)
                                    <span
                                        class="px-2 py-0.5 rounded-sm text-[10px] font-black bg-slate-800 text-white uppercase tracking-tight">
                                        DEPT: {{ $item->department }}
                                    </span>
                                @endif
                            </div>
                        </td>

                        {{-- Parameter --}}
                        <td class="px-6 py-4 text-xs font-bold text-slate-600 uppercase">
                            {{ $item->parameter_permintaan }}
                        </td>

                        {{-- Bobot --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $catDisplay = match ($item->category) {
                                    'HIGH' => 'BERAT',
                                    'MEDIUM' => 'SEDANG',
                                    'LOW' => 'RINGAN',
                                    default => $item->category,
                                };
                                $catColor = match ($catDisplay) {
                                    'BERAT' => 'text-red-700 bg-red-50 border-red-200',
                                    'SEDANG' => 'text-yellow-700 bg-yellow-50 border-yellow-200',
                                    default => 'text-green-700 bg-green-50 border-green-200',
                                };
                            @endphp
                            <span
                                class="px-2 py-1 text-[10px] font-black rounded-sm border {{ $catColor }} uppercase tracking-wide">
                                {{ $catDisplay }}
                            </span>
                        </td>

                        {{-- Uraian --}}
                        <td class="px-6 py-4 text-xs text-slate-500 max-w-xs truncate font-medium">
                            {{ Str::limit($item->description, 35) }}
                        </td>

                        {{-- Diterima Oleh --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($item->processed_by_name)
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-6 h-6 rounded-full bg-slate-800 text-white flex items-center justify-center text-[10px] font-black border border-slate-600">
                                        {{ substr($item->processed_by_name, 0, 1) }}
                                    </div>
                                    <span
                                        class="text-xs font-bold text-slate-700 uppercase">{{ $item->processed_by_name }}</span>
                                </div>
                            @else
                                <span
                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-wide border border-dashed border-slate-300 px-2 py-1 rounded-sm">
                                    Menunggu
                                </span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusClass = match ($item->status) {
                                    'completed' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                    'pending' => 'bg-orange-100 text-orange-800 border-orange-200',
                                    'in_progress' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    'waiting_approval',
                                    'waiting_approval_ga'
                                        => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    'cancelled', 'rejected' => 'bg-rose-100 text-rose-800 border-rose-200',
                                    default => 'bg-slate-100 text-slate-800',
                                };

                                $statusLabel = match ($item->status) {
                                    'waiting_approval' => 'WAITING DEPT APPROVAL',
                                    'waiting_approval_ga' => 'WAITING GA APPROVAL',
                                    'pending' => 'PENDING (ANTRIAN)',
                                    default => str_replace('_', ' ', $item->status),
                                };
                            @endphp
                            <span
                                class="px-3 py-1 text-[10px] font-black uppercase rounded-sm border {{ $statusClass }} tracking-wider">
                                {{ $statusLabel }}
                            </span>
                        </td>

                        {{-- Aksi --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3 justify-end">

                                {{-- 1. Tombol Detail --}}
                                <button type="button"
                                    @click="$dispatch('buka-detail', '{{ base64_encode(json_encode($item)) }}')"
                                    class="text-slate-400 hover:text-blue-600 transition-colors" title="Lihat Detail">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>

                                {{-- ================================================= --}}
                                {{-- LOGIKA HAK AKSES SESUAI REQUEST (MAPPING)         --}}
                                {{-- ================================================= --}}
                                @php
                                    $user = auth()->user();
                                    $currRole = strtolower($user->role ?? '');
                                    $currDivisi = strtolower($user->divisi ?? '');
                                    $ticketDept = strtolower($item->department ?? '');
                                    $ticketStatus = $item->status;

                                    // --- A. CEK HAK AKSES EDIT ---
                                    $canEdit = false;

                                    // 1. Admin GA (Super User)
                                    if (in_array($currRole, ['ga.admin', 'super.ga.admin'])) {
                                        // Admin bisa edit selama belum final (Rejected/Cancelled)
                                        $canEdit = in_array($ticketStatus, ['in_progress', 'pending']);
                                    }
                                    // 2. User Biasa (Pemohon)
                                    // elseif ($item->requester_id == $user->id) {
                                    //     // Hanya boleh edit jika masih Pending atau diminta revisi
                                    //     // (Jangan kasih edit kalau sudah In Progress/Completed!)
                                    //     $canEdit = in_array($ticketStatus, ['waiting_approval']);
                                    // }

                                    // --- B. CEK HAK AKSES APPROVAL TEKNIS (Boss Lokal) ---
                                    $isTechnicalApprover = false;

                                    // Hanya cek ini jika statusnya memang butuh approval atasan
                                    if ($ticketStatus == 'waiting_approval') {
                                        // 1. Logika MANAGER / SPV (Harus Satu Divisi)
                                        if (str_contains($currRole, 'manager') || str_contains($currRole, 'spv')) {
                                            // Cek kesamaan divisi (case-insensitive)
                                            if (trim($currDivisi) == trim($ticketDept)) {
                                                $isTechnicalApprover = true;
                                            }
                                        }

                                        // 2. Logika ADMIN UNIT (Mapping Manual)
                                        else {
                                            $targetRole = match (true) {
                                                // Facility
                                                str_contains($ticketDept, 'facility') || $ticketDept == 'fh'
                                                    => 'fh.admin',
                                                // General Affair
                                                str_contains($ticketDept, 'general') || $ticketDept == 'ga'
                                                    => 'ga.admin',
                                                // IT
                                                str_contains($ticketDept, 'it') ||
                                                    str_contains($ticketDept, 'information')
                                                    => 'it.admin',
                                                // Maintenance
                                                str_contains($ticketDept, 'maintenance') || $ticketDept == 'mt'
                                                    => 'mt.admin',
                                                // Marketing
                                                str_contains($ticketDept, 'marketing') => 'mkt.admin',
                                                // Produksi / Plant (LV, MV, FO)
                                                str_contains($ticketDept, 'plant a') ||
                                                    str_contains($ticketDept, 'plant c') ||
                                                    str_contains($ticketDept, 'low') ||
                                                    $ticketDept == 'lv'
                                                    => 'lv.admin',
                                                str_contains($ticketDept, 'plant b') ||
                                                    str_contains($ticketDept, 'plant d') ||
                                                    str_contains($ticketDept, 'medium') ||
                                                    $ticketDept == 'mv'
                                                    => 'mv.admin',
                                                str_contains($ticketDept, 'plant e') ||
                                                    str_contains($ticketDept, 'fiber') ||
                                                    $ticketDept == 'fo'
                                                    => 'fo.admin',
                                                // Quality
                                                str_contains($ticketDept, 'quality') ||
                                                    $ticketDept == 'qr' ||
                                                    $ticketDept == 'qc'
                                                    => 'qr.admin',
                                                // Sales
                                                str_contains($ticketDept, 'sales 1') => 'sales1.admin',
                                                str_contains($ticketDept, 'sales 2') => 'sales2.admin',
                                                // Supply Chain / Gudang
                                                str_contains($ticketDept, 'sc') ||
                                                    str_contains($ticketDept, 'support') ||
                                                    $ticketDept == 'rm'
                                                    => 'sc.admin',
                                                str_contains($ticketDept, 'ss') || str_contains($ticketDept, 'gudang')
                                                    => 'ss.admin',
                                                // Engineering
                                                str_contains($ticketDept, 'engineering') || $ticketDept == 'pe'
                                                    => 'eng.admin',
                                                // HC / FA
                                                str_contains($ticketDept, 'human') || $ticketDept == 'hc' => 'hc.admin',
                                                str_contains($ticketDept, 'finance') || $ticketDept == 'fa'
                                                    => 'fa.admin',
                                                // Default: Tidak ada yang cocok
                                                default => null,
                                            };

                                            // Jika user memiliki role target TERSEBUT atau Super Admin
                                            if (
                                                $targetRole &&
                                                ($currRole === $targetRole || $currRole === 'super.admin')
                                            ) {
                                                $isTechnicalApprover = true;
                                            }
                                        }
                                        $canApprove = false;
                                        $isGaAdmin = in_array($currRole, ['ga.admin', 'super.ga.admin']);
                                        if ($item->status == 'waiting_approval') {
                                            $isBoss =
                                                str_contains($currRole, 'manager') ||
                                                str_contains($currRole, 'supervisor');

                                            $isSamePlant = str_contains($currDivisi, $ticketDept);

                                            if ($isBoss && $isSamePlant) {
                                                $canApprove = true;
                                            }
                                        } elseif ($item->status == 'waiting_approval_ga') {
                                            if ($isGaAdmin || in_array($currRole, ['ga.admin', 'super.ga.admin'])) {
                                                $canApprove = true;
                                            }
                                        }
                                    }
                                @endphp
                                {{-- ================================================= --}}


                                {{-- 2. Tombol Edit --}}
                                @if ($canEdit)
                                    <button type="button" @click='openEditModal(@json($item))'
                                        class="text-slate-400 hover:text-yellow-500 transition-colors"
                                        title="Update / Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                @endif


                                {{-- 3. Tombol Approval (Unified Logic) --}}
                                @php
                                    // LOGIKA TOMBOL APPROVAL YANG LEBIH BERSIH & BENAR
                                    $showApproveButton = false;

                                    // A. TAHAP 1: WAITING APPROVAL DEPT (Bisa oleh Atasan Dept ATAU GA Admin Bypass)
                                    if ($item->status === 'waiting_approval') {
                                        // Muncul jika dia Atasan Teknis (IsTechnicalApprover sudah dihitung di atas)
                                        // ATAU dia GA Admin (Bypass)
                                        if (
                                            $isTechnicalApprover ||
                                            in_array($currRole, ['ga.admin', 'super.ga.admin'])
                                        ) {
                                            $showApproveButton = true;
                                        }
                                    }
                                    // B. TAHAP 2: WAITING GA APPROVAL (Hanya Tim GA)
                                    elseif ($item->status === 'waiting_approval_ga') {
                                        if (in_array($currRole, ['ga.admin', 'super.ga.admin'])) {
                                            $showApproveButton = true;
                                        }
                                    }
                                @endphp

                                @if ($showApproveButton)
                                    <form id="form-tech-{{ $item->id }}"
                                        action="{{ route('ga.approve-technical', $item->id) }}" method="POST"
                                        class="hidden">
                                        @csrf
                                        <input type="hidden" name="action" id="input-action-{{ $item->id }}">
                                        <input type="hidden" name="reason" id="input-reason-{{ $item->id }}">
                                    </form>

                                    <div class="flex gap-2">
                                        {{-- Tombol Approve --}}
                                        <button type="button"
                                            onclick="confirmTechnicalAction('{{ $item->id }}', 'approve')"
                                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-1 px-3 rounded text-[10px] shadow-sm transition-all hover:scale-105 active:scale-95">
                                            Approve
                                            {{-- Label Bypass untuk Admin di Tahap 1 --}}
                                            @if ($item->status == 'waiting_approval' && in_array($currRole, ['ga.admin', 'super.ga.admin']))
                                                (Bypass)
                                            @endif
                                        </button>

                                        {{-- Tombol Reject --}}
                                        <button type="button"
                                            onclick="confirmTechnicalAction('{{ $item->id }}', 'decline')"
                                            class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-1 px-3 rounded text-[10px] shadow-sm transition-all hover:scale-105 active:scale-95">
                                            Reject
                                        </button>
                                    </div>
                                @endif

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-6 py-16 text-center">
                            <span class="text-slate-500 font-bold uppercase tracking-wide">Tidak ada data
                                ditemukan</span>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="bg-slate-50 px-6 py-4 border-t border-slate-200">
        {{ $workOrders->appends(request()->all())->links() }}
    </div>
</div>
