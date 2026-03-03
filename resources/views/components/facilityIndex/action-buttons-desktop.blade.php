@props(['wo', 'isMobile' => false])

<div class="flex flex-col {{ $isMobile ? 'flex-row items-center' : 'items-end' }} gap-2">

    @php
        $currentUser = Auth::user();
        $status = $wo->status;

        // Cek Hak Akses (Logic dipindah kesini agar rapi)
        $hasAdminRole = in_array($currentUser->role, ['fh.admin', 'super.admin', 'super.fh.admin']);
        $userDivisi = strtoupper($currentUser->divisi ?? '');
        $userLevel = strtoupper($currentUser->job_level ?? '');

        $isFacilitySpv =
            $userDivisi === 'FACILITY' &&
            (str_contains($userLevel, 'SUPERVISOR') ||
                str_contains($userLevel, 'SPV') ||
                str_contains($userLevel, 'MANAGER'));

        $isFacilityAdmin = $hasAdminRole || $isFacilitySpv;

        // Cek Tombol Apa yang Muncul
        // Pastikan model WorkOrder punya method canApproveBy, jika tidak ada, hapus bagian && $wo->canApproveBy(...)
        $showSpvAction =
            $status === 'waiting_approval' &&
            (method_exists($wo, 'canApproveBy') ? $wo->canApproveBy($currentUser) : false);
        $showAdminAction = $status === 'waiting_facility_approval' && $isFacilityAdmin;
        $showActionButtons = $showSpvAction || $showAdminAction;
    @endphp

    <div class="flex items-center {{ $isMobile ? '' : 'justify-end' }} gap-2">
        {{-- TOMBOL DETAIL --}}
        <button @click="$dispatch('open-detail-modal', {{ json_encode($wo) }})"
            class="group px-3 py-1.5 bg-slate-100 text-slate-600 rounded-lg text-xs font-semibold transition-all duration-200 hover:bg-slate-200 hover:text-slate-800 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            Detail
        </button>

        {{-- TOMBOL UPDATE (Pending/Progress) --}}
        @if ($status === 'pending' || $status === 'in_progress')
            @if ($isFacilitySpv)
                <button @click='openEditModal(@json($wo))'
                    class="flex items-center gap-1 bg-blue-100 text-blue-700 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-blue-200 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                        </path>
                    </svg>
                    Update
                </button>
            @endif
        @endif
    </div>

    {{-- FORM APPROVE & REJECT --}}
    @if ($showActionButtons)
        <div class="flex items-center {{ $isMobile ? '' : 'justify-end' }} gap-2">
            {{-- 1. FORM APPROVE --}}
            <form action="{{ route('fh.approve', $wo->id) }}" method="POST"
                onsubmit="return confirmSubmit(this, 'Apakah Anda yakin ingin menyetujui tiket ini?')">
                @csrf
                <button type="submit"
                    class="px-3 py-1.5 bg-emerald-500 text-white rounded-md text-xs font-bold hover:bg-emerald-600 transition shadow-sm flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 btn-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg class="animate-spin w-3.5 h-3.5 btn-spinner hidden" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span class="btn-text">Approve</span>
                </button>
            </form>

            {{-- 2. FORM REJECT --}}
            <form action="{{ route('fh.reject', $wo->id) }}" method="POST" onsubmit="return promptReject(this)">
                @csrf
                <input type="hidden" name="reason" id="reason_{{ $wo->id }}">
                <button type="submit"
                    class="px-3 py-1.5 bg-rose-500 text-white rounded-md text-xs font-bold hover:bg-rose-600 transition shadow-sm flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Reject
                </button>
            </form>
        </div>
    @endif

    {{-- STATUS BADGES INFO (Jika tombol tidak muncul) --}}
    @if (!$showActionButtons && !$isMobile)
        @if ($status === 'waiting_facility_approval' && !$isFacilityAdmin)
            <span
                class="text-[10px] font-semibold text-orange-600 bg-orange-50 px-2 py-1 rounded border border-orange-100">
                ⏳ Menunggu Verifikasi Facility
            </span>
        @elseif ($status === 'waiting_approval' && (method_exists($wo, 'canApproveBy') ? !$wo->canApproveBy($currentUser) : true))
            <span
                class="text-[10px] font-semibold text-slate-500 bg-slate-50 px-2 py-1 rounded border border-slate-200">
                ⏳ Menunggu Approval Atasan
            </span>
        @endif
    @endif
</div>
