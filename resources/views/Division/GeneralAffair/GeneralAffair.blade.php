@section('browser_title', 'General Affair Work Order')

<x-app-layout>
    {{-- CSS CUSTOM --}}
    <style>
        [x-cloak] {
            display: none !important;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
    </style>

    <x-slot name="header">
        <div class="flex justify-between items-center -my-2">
            <h2 class="font-black text-3xl leading-tight uppercase tracking-wider flex items-center gap-4">
                <div class="relative">
                    <div class="w-2 h-10 bg-gradient-to-b from-red-600 to-red-700 shadow-lg border border-red-800"></div>
                    <div class="absolute -right-1 top-0 w-2 h-10 bg-gradient-to-b from-white to-gray-100 opacity-30">
                    </div>
                </div>
                <span class="flex gap-2 items-center">
                    <span class="text-red-600 drop-shadow-sm">GENERAL</span>
                    <span class="text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.3)]"
                        style="text-shadow: 2px 2px 0 #dc2626, -1px -1px 0 #dc2626, 1px -1px 0 #dc2626, -1px 1px 0 #dc2626;">AFFAIR</span>
                    <span class="text-red-300 font-light">|</span>
                    <span class="text-slate-700 text-lg self-center tracking-normal normal-case font-bold">Request
                        Order</span>
                </span>
            </h2>
        </div>
    </x-slot>

    {{-- LOAD LIBRARY --}}
    <script src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @vite(['resources/js/app.js'])
    <script>
        window.gaConfig = {
            pageIds: @json($pageIds ?? []),
            startDate: "{{ request('start_date') }}",
            endDate: "{{ request('end_date') }}",
            userNik: "{{ Auth::user()->nik }}",
            userName: "{{ Auth::user()->name }}",
            userDept: "{{ Auth::user()->divisi }}",


            flash: {
                success: "{{ session('success') }}",
                error: "{{ session('error') }}"
            }
        };
    </script>

    {{-- MULAI SCOPE ALPINE JS --}}
    <div class="py-12 min-h-screen font-sans bg-slate-100 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9IiNjYmQ1ZTEiIGZpbGwtb3BhY2l0eT0iMC4zIi8+PC9zdmc+')] bg-fixed"
        x-data="gaData" @buka-detail.window="openDetail($event.detail)" x-cloak>
        {{-- PENGUMUMAN PENTING (Hilang permanen jika di-close oleh user) --}}


        <div class="max-w-[95%] mx-auto px-4 sm:px-6 lg:px-8">

            {{-- 1. STATISTIK --}}
            <x-gaIndex.stats-card :countTotal="$countTotal" :countPending="$countPending" :countInProgress="$countInProgress" :countCompleted="$countCompleted"
                :countWaitingApproval="$countWaitingApproval" :countWaitingApprovalSpv="$countWaitingApprovalSpv ?? 0" :countWaitingApprovalGA="$countWaitingApprovalGA ?? 0" :countRejected="$countRejected ?? 0" />
            {{-- 2. PENGUMUMAN UPDATE KATEGORI (Warna Emerald/Hijau) --}}
            {{-- PENGUMUMAN UPDATE KATEGORI & FILTER (Warna Emerald/Hijau) --}}
            @if (in_array(auth()->user()->role, ['ga.admin', 'super.ga.admin']))
                <div x-data="{ showUpdateNotif: !localStorage.getItem('ga_logbook_v1_seen') }" x-show="showUpdateNotif" x-transition.opacity.duration.500ms
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                    style="display: none;">

                    <div @click.away="localStorage.setItem('ga_logbook_v1_seen', 'true'); showUpdateNotif = false"
                        class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all border-t-8 border-emerald-600">

                        {{-- Header Banner --}}
                        <div class="bg-gradient-to-r from-emerald-600 to-teal-600 p-6 text-center text-white">
                            <div
                                class="inline-flex items-center justify-center w-12 h-12 bg-white/20 rounded-full mb-3 shadow-inner">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                    </path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-black tracking-tight">Fitur Logbook Internal GA!</h3>
                            <p class="text-emerald-100 text-xs mt-1 font-medium">Update Sistem | Logbook & Pemisahan
                                Data</p>
                        </div>

                        {{-- Konten --}}
                        <div class="p-6">
                            <p class="text-sm text-slate-600 mb-4 font-medium leading-relaxed">
                                Kami telah memisahkan catatan pekerjaan internal tim GA agar tidak bercampur dengan
                                permintaan dari divisi lain:
                            </p>

                            <ul class="space-y-4 mb-6">
                                <li class="flex items-start">
                                    <div
                                        class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mt-0.5">
                                        <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <span class="block text-sm font-bold text-slate-800">Tab Pemisah Baru</span>
                                        <span class="block text-xs text-slate-500">Klik tab <b>"Internal GA"</b> untuk
                                            melihat catatan pekerjaan mandiri tim GA.</span>
                                    </div>
                                </li>
                                <li class="flex items-start">
                                    <div
                                        class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center mt-0.5">
                                        <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <span class="block text-sm font-bold text-slate-800">Pemisahan Laporan
                                            (Excel)</span>
                                        <span class="block text-xs text-slate-500">Export Excel kini secara otomatis
                                            menyesuaikan dengan tab yang sedang Anda buka.</span>
                                    </div>
                                </li>
                            </ul>

                            {{-- Tombol Tutup --}}
                            <button @click="localStorage.setItem('ga_logbook_v1_seen', 'true'); showUpdateNotif = false"
                                type="button"
                                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white bg-slate-900 hover:bg-slate-800 transition-all active:scale-95">
                                Siap, Saya Mengerti
                            </button>
                        </div>
                    </div>
                </div>
            @endif


            {{-- 2. CONTROL PANEL --}}
            <x-gaIndex.control-panel :filterOptions="[
                'status' => [
                    'pending',
                    'waiting_approval',
                    'waiting_approval_ga',
                    'in_progress',
                    'completed',
                    'cancelled',
                    'rejected',
                ],
                'category' => ['BERAT', 'SEDANG', 'RINGAN'],
                'parameter' => ['KEBERSIHAN', 'PEMELIHARAAN', 'PERBAIKAN', 'PEMBUATAN BARU', 'PERIZINAN', 'RESERVASI'],
            ]" />
            @if (in_array(auth()->user()->role, ['ga.admin', 'super.ga.admin', 'admin_ga']))
                <div x-data="{
                    active: '{{ request('view') === 'internal' ? 'logbook' : 'request' }}',
                    init() {
                        this.$nextTick(() => this.moveSlider());
                    },
                    moveSlider() {
                        const target = this.$refs[this.active];
                        const slider = this.$refs.slider;
                        if (!target || !slider) return;
                        slider.style.width = target.offsetWidth + 'px';
                        slider.style.transform = 'translateX(' + target.offsetLeft + 'px)';
                    },
                    go(tab, url) {
                        this.active = tab;
                        this.moveSlider();
                        setTimeout(() => window.location.href = url, 250);
                    }
                }"
                    class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">

                    {{-- Tab Switcher --}}
                    <div class="relative flex bg-slate-100 border border-slate-200 rounded-xl p-0.5 gap-0">

                        {{-- Sliding pill --}}
                        <div x-ref="slider"
                            class="absolute top-0.5 bottom-0.5 left-0.5 rounded-[10px] bg-white border border-slate-200 shadow-sm"
                            style="transition: transform 0.25s cubic-bezier(0.4,0,0.2,1), width 0.25s cubic-bezier(0.4,0,0.2,1);">
                        </div>

                        {{-- Tab: Request Divisi Lain --}}
                        <button x-ref="request" @click="go('request', '{{ route('ga.index') }}')"
                            class="relative z-10 flex items-center gap-2 px-5 py-2.5 rounded-[10px] text-sm font-medium border border-transparent transition-colors duration-200 cursor-pointer">

                            <svg class="w-3.5 h-3.5 shrink-0 transition-colors duration-200"
                                :class="active === 'request' ? 'text-blue-600' : 'text-slate-400'" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>

                            <span class="transition-colors duration-200"
                                :class="active === 'request' ? 'text-blue-700' : 'text-slate-500'">
                                Request Divisi Lain
                            </span>
                        </button>

                        {{-- Tab: Logbook Internal GA --}}
                        <button x-ref="logbook"
                            @click="go('logbook', '{{ route('ga.index', ['view' => 'internal']) }}')"
                            class="relative z-10 flex items-center gap-2 px-5 py-2.5 rounded-[10px] text-sm font-medium border border-transparent transition-colors duration-200 cursor-pointer">

                            <svg class="w-3.5 h-3.5 shrink-0 transition-colors duration-200"
                                :class="active === 'logbook' ? 'text-emerald-600' : 'text-slate-400'" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>

                            <span class="transition-colors duration-200"
                                :class="active === 'logbook' ? 'text-emerald-700' : 'text-slate-500'">
                                Logbook Internal GA
                            </span>
                        </button>

                    </div>

                    {{-- Info Panel --}}
                    <div class="flex items-center gap-3 px-4 py-2.5 bg-white border border-slate-200 rounded-xl">

                        {{-- Request mode --}}
                        <template x-if="active === 'request'">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-800 leading-tight">Data Request</p>
                                    <p class="text-[11px] text-slate-400 leading-tight">Permintaan dari divisi lain</p>
                                </div>
                            </div>
                        </template>

                        {{-- Logbook mode --}}
                        <template x-if="active === 'logbook'">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-800 leading-tight">Data Logbook</p>
                                    <p class="text-[11px] text-slate-400 leading-tight">Pekerjaan tim GA</p>
                                </div>
                            </div>
                        </template>

                    </div>

                </div>
            @endif
            {{-- 3. DATA TABLE (Pusat Tombol Mata) --}}
            <x-gaIndex.table-data :workOrders="$workOrders" />

            {{-- 4. MODAL-MODAL (Semua ada di dalam scope x-data) --}}
            <x-gaIndex.modal-create :plants="$plants" :categoriesDB="$categoriesDB" :categories="$categories" />
            <x-gaIndex.modal-detail />
            <x-gaIndex.modal-edit />
            <x-gaIndex.modal-confirm />

            {{-- Modal Process --}}
            <x-gaIndex.modal-process />
            {{-- Modal Accept --}}
            <x-gaIndex.modal-accept />
            {{-- MODAL REJECT GA --}}
            <x-gaIndex.modal-reject />

        </div> {{-- End max-w container --}}
    </div> {{-- AKHIR DIV x-data="gaData" --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log("✅ SYSTEM APPROVAL READY (Event Delegation Mode)");

            // KITA PASANG TELINGA DI DOCUMENT BODY
            document.body.addEventListener('click', function(e) {

                // 1. Cek apakah yang diklik adalah tombol action kita?
                const button = e.target.closest('.js-action-btn');

                // Jika bukan tombol kita, abaikan
                if (!button) return;

                // 2. Ambil Data dari Tombol
                const id = button.getAttribute('data-id');
                const action = button.getAttribute('data-action');
                const viewType = button.getAttribute('data-view');

                console.log("🔥 TOMBOL DIKLIK:", {
                    id,
                    action,
                    viewType
                });

                // 3. Logic Form & SweetAlert (Sama seperti sebelumnya)
                const formId = `form-tech-${viewType}-${id}`;
                const actionInputId = `input-action-${viewType}-${id}`;
                const reasonInputId = `input-reason-${viewType}-${id}`;

                const formEl = document.getElementById(formId);
                const actionEl = document.getElementById(actionInputId);
                const reasonEl = document.getElementById(reasonInputId);

                if (!formEl) {
                    console.error(`❌ Form ID ${formId} tidak ditemukan.`);
                    alert("Error: Form tidak ditemukan. Coba Refresh.");
                    return;
                }

                // Mencegah klik ganda / default action
                e.preventDefault();

                if (action === 'approve') {
                    Swal.fire({
                        title: 'Setujui Tiket?',
                        text: "Tiket akan diproses ke tahap selanjutnya.",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#10b981',
                        confirmButtonText: 'Ya, Setujui'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            actionEl.value = 'approve';
                            formEl.submit();
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Tolak Tiket?',
                        input: 'textarea',
                        inputPlaceholder: 'Wajib isi alasan...',
                        showCancelButton: true,
                        confirmButtonColor: '#e11d48',
                        confirmButtonText: 'Tolak',
                        inputValidator: (value) => !value && 'Wajib diisi!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            actionEl.value = 'reject';
                            if (reasonEl) reasonEl.value = result.value;
                            formEl.submit();
                        }
                    });
                }
            });
        });
    </script>
    <script>
        // Daftarkan ke window agar bisa diakses darimana saja oleh onclick
        window.submitReject = function(id, view) {
            Swal.fire({
                title: 'Tolak Tiket?',
                input: 'textarea',
                inputLabel: 'Alasan Penolakan (Wajib)',
                inputPlaceholder: 'Tulis alasan di sini...',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Tolak',
                cancelButtonText: 'Batal',
                inputValidator: (value) => {
                    if (!value || value.trim() === '') {
                        return 'Alasan penolakan wajib diisi!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(`input-action-${view}-${id}`).value = 'reject';
                    document.getElementById(`input-reason-${view}-${id}`).value = result.value.trim();
                    document.getElementById(`form-tech-${view}-${id}`).submit();
                }
            });
        };

        // Daftarkan juga fungsi Approve ke window
        window.submitApprove = function(id, view) {
            document.getElementById(`input-action-${view}-${id}`).value = 'approve';
            document.getElementById(`form-tech-${view}-${id}`).submit();
        };
    </script>
</x-app-layout>
