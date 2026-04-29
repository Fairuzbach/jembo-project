@props(['wo', 'isMobile' => false])

<div x-data="{
    showRejectModal: false,
    rejectReason: '',
    isSubmitting: false,
    submitReject() {
        if (!this.rejectReason.trim()) return;
        this.isSubmitting = true;
        this.$refs.rejectForm.submit();
    }
}" class="flex flex-col {{ $isMobile ? 'flex-row items-center' : 'items-end' }} gap-2">

    @php
        $currentUser = Auth::user();
        $status = $wo->status;

        $hasAdminRole = in_array($currentUser->role, ['fh.admin', 'super.admin', 'super.fh.admin']);
        $userDivisi = strtoupper(trim($currentUser->divisi ?? ''));
        $userLevel = strtoupper(trim($currentUser->job_level ?? ''));

        $isFacilitySpv =
            $userDivisi === 'FACILITY' &&
            (str_contains($userLevel, 'SUPERVISOR') ||
                str_contains($userLevel, 'SPV') ||
                str_contains($userLevel, 'MANAGER'));

        $isFacilityAdmin = $hasAdminRole || $isFacilitySpv;
        $showSpvAction = $status === 'waiting_approval' && $wo->canApproveBy($currentUser);
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

        {{-- TOMBOL UPDATE --}}
        @if (($status === 'pending' || $status === 'in_progress' || $status === 'cancelled') && $isFacilityAdmin)
            <button @click='openEditModal(@json($wo))'
                class="flex items-center gap-1 bg-blue-100 text-blue-700 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-blue-200 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Update
            </button>
        @endif
    </div>

    {{-- FORM APPROVE & REJECT --}}
    @if ($showActionButtons)
        <div class="flex items-center {{ $isMobile ? '' : 'justify-end' }} gap-2">

            {{-- APPROVE --}}
            <form action="{{ route('fh.approve', $wo->id) }}" method="POST"
                onsubmit="return confirmSubmit(this, 'Apakah Anda yakin ingin menyetujui tiket ini?')">
                @csrf
                <button type="submit"
                    class="px-3 py-1.5 bg-emerald-500 text-white rounded-md text-xs font-bold hover:bg-emerald-600 transition shadow-sm flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 btn-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="btn-text">Approve</span>
                </button>
            </form>

            {{-- REJECT — Trigger Modal --}}
            <button @click="showRejectModal = true"
                class="px-3 py-1.5 bg-rose-500 text-white rounded-md text-xs font-bold hover:bg-rose-600 transition shadow-sm flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Reject
            </button>
        </div>

        {{-- MODAL REJECT --}}
        <template x-teleport="body">
            <div x-show="showRejectModal" class="fixed inset-0 z-[70] flex items-center justify-center p-4"
                style="display:none" x-transition.opacity>

                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showRejectModal = false"></div>

                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl p-6 z-10" @click.stop
                    x-transition.scale.origin.center>

                    {{-- Header --}}
                    <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-rose-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800">Tolak Tiket</h3>
                                <p class="text-xs text-slate-500">{{ $wo->ticket_num }}</p>
                            </div>
                        </div>
                        <button @click="showRejectModal = false" class="text-slate-400 hover:text-slate-600 transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Body --}}
                    <form x-ref="rejectForm" action="{{ route('fh.reject', $wo->id) }}" method="POST">
                        @csrf
                        <div class="mb-5">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Alasan Penolakan <span class="text-rose-500">*</span>
                            </label>
                            <textarea x-model="rejectReason" name="reason" rows="4" placeholder="Jelaskan alasan penolakan tiket ini..."
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-rose-300 focus:border-rose-400 resize-none transition"
                                required></textarea>
                            <p class="text-xs text-slate-400 mt-1">Alasan ini akan dikirimkan ke requester via WhatsApp
                                & Email.</p>
                        </div>

                        <div class="flex gap-3">
                            <button type="button" @click="showRejectModal = false"
                                class="flex-1 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-bold transition">
                                Batal
                            </button>
                            <button type="button" @click="submitReject()"
                                :disabled="!rejectReason.trim() || isSubmitting"
                                :class="!rejectReason.trim() || isSubmitting ?
                                    'bg-rose-300 cursor-not-allowed' :
                                    'bg-rose-500 hover:bg-rose-600'"
                                class="flex-1 px-4 py-2.5 text-white rounded-xl text-sm font-bold transition flex items-center justify-center gap-2">
                                <svg x-show="isSubmitting" class="animate-spin w-4 h-4" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span x-text="isSubmitting ? 'Memproses...' : 'Ya, Tolak Tiket'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    @endif

    {{-- STATUS INFO BADGES --}}
    @if (!$showActionButtons && !$isMobile)
        @if ($status === 'waiting_facility_approval' && !$isFacilityAdmin)
            <span
                class="text-[10px] font-semibold text-orange-600 bg-orange-50 px-2 py-1 rounded border border-orange-100">
                ⏳ Menunggu Verifikasi Facility
            </span>
        @elseif ($status === 'waiting_approval')
            <span
                class="text-[10px] font-semibold text-slate-500 bg-slate-50 px-2 py-1 rounded border border-slate-200">
                ⏳ Menunggu Approval Atasan
            </span>
        @endif
    @endif
</div>
