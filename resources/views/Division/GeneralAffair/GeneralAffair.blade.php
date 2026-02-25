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
            <div x-data="{ showUpdateNotif: !localStorage.getItem('update_v2_seen') }" x-show="showUpdateNotif" x-transition.opacity.duration.500ms
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                style="display: none;">

                <div @click.away="localStorage.setItem('update_v2_seen', 'true'); showUpdateNotif = false"
                    class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">

                    {{-- Header Gambar / Banner Gradient --}}
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-6 text-center">
                        <div
                            class="inline-flex items-center justify-center w-12 h-12 bg-white/20 rounded-full mb-3 shadow-inner">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-black text-white tracking-tight">System Update Baru!</h3>
                        <p class="text-blue-100 text-xs mt-1 font-medium">Versi 2.0 | Update Hari Ini</p>
                    </div>

                    {{-- Konten List Fitur --}}
                    <div class="p-6">
                        <p class="text-sm text-slate-600 mb-4 font-medium">
                            Kami baru saja menambahkan beberapa fitur baru untuk mempermudah pekerjaan Anda:
                        </p>

                        <ul class="space-y-3 mb-6">
                            <li class="flex items-start">
                                <div
                                    class="flex-shrink-0 w-5 h-5 rounded-full bg-red-100 flex items-center justify-center mt-0.5">
                                    <svg class="w-3 h-3 text-red-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <span class="block text-sm font-bold text-slate-800">Batal Request Mandiri
                                        (GA)</span>
                                    <span class="block text-xs text-slate-500">Kini Anda bisa membatalkan laporan
                                        General Affair Anda sendiri jika statusnya masih Pending.</span>
                                </div>
                            </li>
                        </ul>

                        {{-- Tombol Tutup --}}
                        <button @click="localStorage.setItem('update_v2_seen', 'true'); showUpdateNotif = false"
                            type="button"
                            class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-slate-900 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 transition-colors">
                            Mengerti, Lanjutkan
                        </button>
                    </div>
                </div>
            </div>
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
</x-app-layout>
