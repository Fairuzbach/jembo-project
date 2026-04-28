@section('browser_title', 'Facilities Work Order')
@php
    $user = auth()->user();
    $userDivisi = strtolower(trim($user->divisi ?? ''));

    $isAllowedFacility = false;

    if ($user->role === 'fh.admin') {
        $isAllowedFacility = true;
    } else {
        $plants = [
            'facility',
            'plant a',
            'plant b',
            'plant c',
            'plant d',
            'pp',
            'plant e',
            'general affair',
            'process engineering',
            'sales support',
            'procurement',
            'production planning',
            'maintenance',
        ];
        foreach ($plants as $plant) {
            if (str_contains($userDivisi, $plant)) {
                $isAllowedFacility = true;
                break;
            }
        }
    }
@endphp
@if ($isAllowedFacility)

    <x-app-layout>
        {{-- HEADER --}}
        <x-slot name="header">
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <div class="flex justify-between items-center py-2">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#1E3A5F] to-[#2d5285] flex items-center justify-center text-white shadow-lg shadow-blue-900/10">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-2xl text-slate-800 tracking-tight">WO Facilities</h2>
                    </div>
                </div>
            </div>
        </x-slot>
        @include('Division.Facilities.partials.styles')
        {{-- LIBRARIES --}}
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        {{-- GLOBAL STYLES --}}


        {{-- MAIN CONTENT --}}
        @if (session('success'))
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: '{{ session('success') }}',
                    confirmButtonColor: '#1E3A5F',
                    timer: 2000,
                    showConfirmButton: false
                })
            </script>
        @endif

        <div class="max-w-[95rem] mx-auto sm:px-6 lg:px-8 space-y-6" x-data ="facilitiesData()"
            @open-create-modal = "resetForm(); showCreateModal = true">

            {{-- 1. STATS OVERVIEW --}}
            <x-facilityIndex.stats-card :countTotal="$countTotal" :countPending="$countPending" :countProgress="$countProgress" :countDone="$countDone" />

            {{-- 2. FILTER & TOOLBAR (DIRAPIKAN) --}}
            <x-facilityIndex.toolbar :list-plants="$listPlants" :selected-tickets="$selectedTickets ?? []" />

            {{-- 3. TABLE --}}
            <x-facilityIndex.table-data :work-orders="$workOrders" />
            {{-- MODAL CREATE: (Clean & Simple) --}}
            <x-facilityIndex.modal-create :listPlants="$listPlants" />

            {{-- MODAL 2: UPDATE STATUS (MULTI SELECT DROPDOWN FIX) --}}
            <x-facilityIndex.modal-update />
            {{-- MODAL 3: DETAIL TICKET (VIEW) --}}
            <x-facilityIndex.modal-detail />
        </div>

        </div>
        @include('Division.Facilities.partials.scripts')
    </x-app-layout>
@else
    <script>
        // Redirect paksa ke halaman dashboard/utama
        window.location.href = "{{ route('dashboard') }}";
        // Atau tampilkan alert dulu
        alert("Akses Ditolak: Anda tidak memiliki izin ke menu Facility.");
    </script>
@endif
