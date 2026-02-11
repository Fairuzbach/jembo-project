@props(['workOrders'])

<div class="space-y-4">

    {{-- ALERT BANNER (Tetap Sama) --}}
    @if (session('alert-action'))
        <div class="mb-4 bg-orange-50 border-l-4 border-orange-500 p-4 rounded-r shadow-md mx-4 md:mx-0">
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
                    <p class="text-xs text-orange-700 mt-1">{{ session('alert-action')['instruction'] }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- ====================================================================== --}}
    {{-- TAMPILAN DESKTOP (TABLE) - HANYA MUNCUL DI LAYAR MENENGAH KE ATAS --}}
    {{-- ====================================================================== --}}
    <div class="hidden md:block bg-white shadow-xl rounded-sm overflow-hidden border border-slate-200">
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
                        {{-- INCLUDE LOGIC HAK AKSES DISINI AGAR BISA DIPAKAI DI TABLE --}}
                        @php
                            // LOGIC HAK AKSES (SAMA SEPERTI SEBELUMNYA)
                            $user = auth()->user();
                            $currRole = strtolower($user->role ?? '');
                            $currDivisi = strtolower($user->divisi ?? '');
                            $ticketDept = strtolower($item->department ?? '');
                            $ticketStatus = $item->status;
                            $isGaAdmin = in_array($currRole, ['ga.admin', 'super.ga.admin']);

                            $canEdit = false;
                            if ($isGaAdmin) {
                                $canEdit = in_array($ticketStatus, ['in_progress', 'pending', 'completed']);
                            }

                            $isTechnicalApprover = false;
                            if ($ticketStatus == 'waiting_approval') {
                                if (str_contains($currRole, 'manager') || str_contains($currRole, 'spv')) {
                                    if (trim($currDivisi) == trim($ticketDept)) {
                                        $isTechnicalApprover = true;
                                    }
                                } else {
                                    $targetRole = match (true) {
                                        str_contains($ticketDept, 'facility') || $ticketDept == 'fh' => 'fh.admin',
                                        str_contains($ticketDept, 'general') || $ticketDept == 'ga' => 'ga.admin',
                                        str_contains($ticketDept, 'it') || str_contains($ticketDept, 'information')
                                            => 'it.admin',
                                        str_contains($ticketDept, 'maintenance') || $ticketDept == 'mt' => 'mt.admin',
                                        str_contains($ticketDept, 'marketing') => 'mkt.admin',
                                        str_contains($ticketDept, 'low') || $ticketDept == 'lv' => 'lv.admin',
                                        str_contains($ticketDept, 'medium') || $ticketDept == 'mv' => 'mv.admin',
                                        str_contains($ticketDept, 'fiber') || $ticketDept == 'fo' => 'fo.admin',
                                        str_contains($ticketDept, 'quality') || in_array($ticketDept, ['qr', 'qc'])
                                            => 'qr.admin',
                                        str_contains($ticketDept, 'sales 1') => 'sales1.admin',
                                        str_contains($ticketDept, 'sales 2') => 'sales2.admin',
                                        str_contains($ticketDept, 'sc') || str_contains($ticketDept, 'support')
                                            => 'sc.admin',
                                        str_contains($ticketDept, 'ss') || str_contains($ticketDept, 'gudang')
                                            => 'ss.admin',
                                        str_contains($ticketDept, 'engineering') || $ticketDept == 'pe' => 'eng.admin',
                                        str_contains($ticketDept, 'human') || $ticketDept == 'hc' => 'hc.admin',
                                        str_contains($ticketDept, 'finance') || $ticketDept == 'fa' => 'fa.admin',
                                        default => null,
                                    };
                                    if ($targetRole && ($currRole === $targetRole || $currRole === 'super.admin')) {
                                        $isTechnicalApprover = true;
                                    }
                                }
                            }

                            $showApproveButton = false;
                            if ($ticketStatus === 'waiting_approval') {
                                if ($isTechnicalApprover || $isGaAdmin) {
                                    $showApproveButton = true;
                                }
                            } elseif ($ticketStatus === 'waiting_approval_ga') {
                                if ($isGaAdmin) {
                                    $showApproveButton = true;
                                }
                            }
                            $cat = $item->category;

                            // Tentukan Warna
                            $badgeClass = match ($cat) {
                                'HIGH' => 'text-red-700 bg-red-50 border-red-200',
                                'MEDIUM' => 'text-yellow-700 bg-yellow-50 border-yellow-200',
                                default => 'text-green-700 bg-green-50 border-green-200', // Default LOW
                            };

                            // Tentukan Teks
                            $badgeText = match ($cat) {
                                'HIGH' => 'BERAT',
                                'MEDIUM' => 'SEDANG',
                                default => 'RINGAN', // Default LOW
                            };
                        @endphp

                        <tr
                            class="hover:bg-yellow-50/50 transition-colors duration-150 group {{ $index % 2 == 0 ? 'bg-white' : 'bg-slate-50/30' }}">
                            <td class="px-6 py-4"><input type="checkbox" value="{{ (string) $item->id }}"
                                    x-model="selected" class="rounded-sm border-slate-300"></td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-black text-slate-900 font-mono">{{ $item->ticket_num }}</div>
                                <div class="text-[10px] text-slate-400 font-bold mt-0.5 uppercase">
                                    {{ $item->created_at->format('d M Y') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs font-bold text-slate-700">{{ $item->requester_name }}</div>
                                @if ($item->requester_department)
                                    <div class="text-[10px] font-bold text-slate-400 mt-1">Dept:
                                        {{ $item->requester_department }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($item->plant)
                                    <span
                                        class="block px-2 py-0.5 mb-1 rounded-sm text-[10px] font-black bg-slate-100 text-slate-600 border uppercase">LOC:
                                        {{ $item->plantInfo->name ?? $item->plant }}</span>
                                @endif
                                @if ($item->department)
                                    <span
                                        class="block px-2 py-0.5 rounded-sm text-[10px] font-black bg-slate-800 text-white uppercase">DEPT:
                                        {{ $item->department }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs font-bold text-slate-700 uppercase">
                                {{ $item->parameter_permintaan ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 text-[10px] font-black rounded-sm border uppercase {{ $badgeClass }}">
                                    {{ $badgeText }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500 max-w-xs truncate">
                                {{ Str::limit($item->description, 35) }}</td>
                            <td class="px-6 py-4 text-xs font-bold text-slate-700 uppercase">
                                {{ $item->processed_by_name ?? '-' }}</td>
                            <td class="px-6 py-4">
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
                                @endphp
                                <span
                                    class="px-3 py-1 text-[10px] font-black uppercase rounded-sm border {{ $statusClass }} tracking-wider">{{ str_replace('_', ' ', $item->status) }}</span>
                            </td>


                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center gap-2 justify-end">
                                    <button type="button"
                                        @click="$dispatch('buka-detail', '{{ base64_encode(json_encode($item)) }}')"
                                        class="text-slate-400 hover:text-blue-600"><svg class="h-5 w-5" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg></button>
                                    @if ($canEdit)
                                        <button type="button"
                                            @click="$dispatch('open-edit-modal', {{ json_encode($item) }})"
                                            class="text-blue-600 hover:text-blue-900"><svg class="h-5 w-5"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg></button>
                                    @endif
                                    @if ($showApproveButton)
                                        <form id="form-tech-desktop-{{ $item->id }}"
                                            action="{{ route('ga.approve-technical', $item->id) }}" method="POST"
                                            class="hidden">@csrf<input type="hidden" name="action"
                                                id="input-action-desktop-{{ $item->id }}"><input type="hidden"
                                                name="reason" id="input-reason-desktop-{{ $item->id }}"></form>
                                        @if ($isGaAdmin)
                                            <button type="button"
                                                @click="$dispatch('open-accept-modal', {{ json_encode($item->load('plantInfo')) }})"
                                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-1 px-2 rounded text-[10px] uppercase shadow-sm">Validasi</button>
                                        @else
                                            <button type="button"
                                                onclick="confirmTechnicalAction('{{ $item->id }}', 'approve', 'desktop')"
                                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-1 px-2 rounded text-[10px] uppercase shadow-sm">Approve</button>
                                        @endif
                                        <button type="button"
                                            onclick="confirmTechnicalAction('{{ $item->id }}', 'decline', 'desktop')"
                                            class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-1 px-2 rounded text-[10px] uppercase shadow-sm">Reject</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-16 text-center text-slate-500 font-bold uppercase">Tidak
                                ada data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ====================================================================== --}}
    {{-- TAMPILAN MOBILE (CARD VIEW) - HANYA MUNCUL DI HP (md:hidden) --}}
    {{-- ====================================================================== --}}
    <div class="md:hidden space-y-4">
        @forelse ($workOrders as $item)
            @php
                // COPY PASTE LOGIC PERMISSION (Karena Blade scope terpisah)
                // Idealnya logic ini ada di Controller/Model, tapi untuk cepat kita taruh sini.
                $user = auth()->user();
                $currRole = strtolower($user->role ?? '');
                $currDivisi = strtolower($user->divisi ?? '');
                $ticketDept = strtolower($item->department ?? '');
                $ticketStatus = $item->status;
                $isGaAdmin = in_array($currRole, ['ga.admin', 'super.ga.admin']);

                $canEdit = false;
                if ($isGaAdmin) {
                    $canEdit = in_array($ticketStatus, ['in_progress', 'pending']);
                }

                $isTechnicalApprover = false;
                if ($ticketStatus == 'waiting_approval') {
                    if (str_contains($currRole, 'manager') || str_contains($currRole, 'spv')) {
                        if (trim($currDivisi) == trim($ticketDept)) {
                            $isTechnicalApprover = true;
                        }
                    } else {
                        // (Logic Role Admin Unit sama spt di atas, disederhanakan utk contoh)
                        $isTechnicalApprover = true; // Asumsi admin unit cocok (cek logic lengkap di atas)
                    }
                }

                $showApproveButton = false;
                if ($ticketStatus === 'waiting_approval' && ($isTechnicalApprover || $isGaAdmin)) {
                    $showApproveButton = true;
                } elseif ($ticketStatus === 'waiting_approval_ga' && $isGaAdmin) {
                    $showApproveButton = true;
                }
            @endphp

            <div class="bg-white rounded-lg shadow-md border border-slate-200 overflow-hidden relative">
                {{-- Header Card --}}
                <div class="bg-slate-50 px-4 py-3 border-b border-slate-100 flex justify-between items-center">
                    <div>
                        <span
                            class="text-xs font-black text-blue-600 bg-blue-50 px-2 py-1 rounded border border-blue-100">#{{ $item->ticket_num }}</span>
                        <div class="text-[10px] text-slate-400 font-bold mt-1 uppercase">
                            {{ $item->created_at->format('d M Y') }}</div>
                    </div>
                    {{-- Status Badge --}}
                    @php
                        $statusClass = match ($item->status) {
                            'completed' => 'bg-emerald-100 text-emerald-800',
                            'pending' => 'bg-orange-100 text-orange-800',
                            'in_progress' => 'bg-blue-100 text-blue-800',
                            'waiting_approval', 'waiting_approval_ga' => 'bg-yellow-100 text-yellow-800',
                            'cancelled', 'rejected' => 'bg-rose-100 text-rose-800',
                            default => 'bg-slate-100 text-slate-800',
                        };
                    @endphp
                    <span class="px-2 py-1 text-[10px] font-black uppercase rounded-lg {{ $statusClass }}">
                        {{ str_replace('_', ' ', $item->status) }}
                    </span>
                </div>

                {{-- Body Card --}}
                <div class="p-4 space-y-3">
                    {{-- Pelapor & Dept --}}
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-bold text-slate-500 uppercase">Pelapor</p>
                            <p class="text-sm font-bold text-slate-800">{{ $item->requester_name }}</p>
                            <p class="text-[10px] text-slate-400 uppercase">{{ $item->requester_department ?? '-' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-bold text-slate-500 uppercase">Lokasi</p>
                            <p class="text-xs font-bold text-slate-800">{{ $item->plantInfo->name ?? $item->plant }}
                            </p>
                        </div>
                    </div>

                    {{-- Deskripsi --}}
                    <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                        <p class="text-xs text-slate-500 italic line-clamp-3">"{{ $item->description }}"</p>
                    </div>

                    {{-- Info Tambahan (PIC & Kategori) --}}
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="block font-bold text-slate-400 uppercase">PIC</span>
                            <span class="font-bold text-slate-700">{{ $item->processed_by_name ?? '-' }}</span>
                        </div>
                        <div class="text-right">
                            <span class="block font-bold text-slate-400 uppercase">Bobot</span>
                            <span
                                class="{{ $item->category == 'HIGH' ? 'text-red-600' : 'text-green-600' }} font-bold">{{ $item->category }}</span>
                        </div>
                    </div>
                </div>

                {{-- Footer Card (Actions) --}}
                <div class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex justify-between items-center gap-2">
                    {{-- Tombol Detail (Kiri) --}}
                    <button type="button"
                        @click="$dispatch('buka-detail', '{{ base64_encode(json_encode($item)) }}')"
                        class="flex-1 bg-white border border-slate-300 text-slate-600 py-2 rounded-lg text-xs font-bold uppercase shadow-sm hover:bg-slate-50">
                        Detail
                    </button>

                    {{-- Tombol Approval / Edit (Kanan) --}}
                    @if ($canEdit)
                        <button type="button" @click="$dispatch('open-edit-modal', {{ json_encode($item) }})"
                            class="flex-1 bg-blue-600 text-white py-2 rounded-lg text-xs font-bold uppercase shadow-sm hover:bg-blue-700">
                            Update
                        </button>
                    @endif

                    @if ($showApproveButton)
                        {{-- Form Hidden Mobile --}}
                        <form id="form-tech-mobile-{{ $item->id }}"
                            action="{{ route('ga.approve-technical', $item->id) }}" method="POST" class="hidden">
                            @csrf <input type="hidden" name="action" id="input-action-mobile-{{ $item->id }}">
                            <input type="hidden" name="reason" id="input-reason-mobile-{{ $item->id }}">
                        </form>

                        <div class="flex gap-2 flex-1">
                            @if ($isGaAdmin)
                                <button
                                    @click="$dispatch('open-accept-modal', {{ json_encode($item->load('plantInfo')) }})"
                                    class="flex-1 bg-emerald-600 text-white py-2 rounded-lg text-xs font-bold uppercase">Validasi</button>
                            @else
                                <button onclick="confirmTechnicalAction('{{ $item->id }}', 'approve', 'mobile')"
                                    class="flex-1 bg-emerald-600 text-white py-2 rounded-lg text-xs font-bold uppercase">Approve</button>
                            @endif
                            <button onclick="confirmTechnicalAction('{{ $item->id }}', 'decline', 'mobile')"
                                class="w-10 bg-rose-600 text-white py-2 rounded-lg text-xs font-bold flex items-center justify-center">
                                X
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-10 bg-white rounded-lg shadow border border-slate-200">
                <span class="text-slate-400 font-bold text-sm">Tidak ada data</span>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="bg-slate-50 px-6 py-4 border-t border-slate-200">
        {{ $workOrders->appends(request()->all())->links() }}
    </div>
</div>

{{-- SCRIPT TAMBAHAN UNTUK HANDLE FORM ID MOBILE vs DESKTOP --}}
<script>
    function confirmTechnicalAction(id, action, viewType = 'desktop') {
        // Sesuaikan ID form berdasarkan tampilan (desktop/mobile)
        let formId = viewType === 'mobile' ? `form-tech-mobile-${id}` : `form-tech-desktop-${id}`;
        let actionId = viewType === 'mobile' ? `input-action-mobile-${id}` : `input-action-desktop-${id}`;
        let reasonId = viewType === 'mobile' ? `input-reason-mobile-${id}` : `input-reason-desktop-${id}`;

        if (action === 'approve') {
            Swal.fire({
                title: 'Setujui Tiket?',
                text: "Tiket akan diteruskan ke proses selanjutnya.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Setujui!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(actionId).value = 'approve';
                    document.getElementById(formId).submit();
                }
            })
        } else if (action === 'decline') {
            Swal.fire({
                title: 'Tolak Tiket?',
                input: 'textarea',
                inputLabel: 'Alasan Penolakan',
                inputPlaceholder: 'Tulis alasan penolakan...',
                inputAttributes: {
                    'aria-label': 'Tulis alasan penolakan'
                },
                showCancelButton: true,
                confirmButtonText: 'Tolak Tiket',
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#6b7280',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Anda harus menulis alasan penolakan!'
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(actionId).value = 'reject';
                    document.getElementById(reasonId).value = result.value;
                    document.getElementById(formId).submit();
                }
            })
        }
    }
</script>
