@section('browser_title', 'Engineering Improvement Order')
<x-app-layout title="Engineering Improvement Order">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight relative z-10">
            {{ __('Engineering Improvement Order') }}
        </h2>
    </x-slot>

    {{-- LOAD LIBRARY --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    {{-- CONTAINER UTAMA (Panggil komponen Alpine di sini) --}}
    <div class="py-12" x-data="engineeringOrder()">

        {{-- Auto Open Modal Handlers --}}
        @if ($errors->hasAny(['machine_name', 'damaged_part', 'production_status', 'kerusakan_detail', 'photo']))
            <div x-init="showCreateModal = true"></div>
        @endif

        @if ($errors->hasAny(['start_date', 'end_date']))
            <div x-init="showExportModal = true"></div>
        @endif

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- A. ALERTS SECTION --}}
            @include('Division.Engineering.partials.alerts')

            {{-- B. STATISTIK CARDS --}}
            @include('Division.Engineering.partials.stats')

            {{-- C. TABEL DATA & SEARCH --}}
            @include('Division.Engineering.partials.table')

        </div>

        {{-- D. MODALS --}}
        @include('Division.Engineering.partials.modals.spk-create')
        @include('Division.Engineering.partials.modals.compound-create')
        @include('Division.Engineering.partials.modals.create')
        @include('Division.Engineering.partials.modals.confirm')
        @include('Division.Engineering.partials.modals.detail')
        @include('Division.Engineering.partials.modals.edit')
        @include('Division.Engineering.partials.modals.export')

    </div> {{-- Closing X-DATA div --}}

    {{-- ALPINE JS COMPONENT LOGIC --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('engineeringOrder', () => ({
                // 1. MODAL STATES
                showDetailModal: false,
                showCreateModal: false,
                showSpkModal: false,
                showCompoundModal: false,
                showConfirmModal: false,
                showEditModal: false,
                showExportModal: false,

                // 2. DATA EXPORT
                selectedTickets: [],
                pageIds: {{ Js::from($workOrders->pluck('id')) }},

                // 3. DATA HOLDER
                ticket: {},
                allPlants: {{ Js::from($plants) }}.filter(plant => {
                    const allowedPlants = [
                        'plant a',
                        'plant a - autowire',
                        // 'plant b',
                        // 'plant c',
                    ];
                    return allowedPlants.includes(plant.name.toLowerCase());
                }),
                allTechnicians: {{ Js::from($technicians) }},

                // 4. FORM VARIABLES
                currentDate: '',
                currentTime: '',
                currentShift: '',
                selectedPlant: '',
                machineOptions: [],
                work_method: 'sendiri',
                currentUsername: '{{ auth()->user()->name }}',
                userRole: '{{ auth()->user()->role }}',
                isManualInput: false,

                form: {
                    kerusakan: '',
                    kerusakan_detail: '',
                    priority: 'low',
                    initial_status: 'OPEN',
                    plant: '',
                    machine_name: '',
                    damaged_part: '',
                    production_status: '',
                    file_name: '',
                    improvement_parameters: '',
                    engineer_tech: [],
                    operator_name: '',
                    target_date: '',
                    target_time: '',
                    action_taken: '', // Tindakan Perbaikan
                    spare_parts: '', // Spare Part
                },

                editForm: {
                    id: '',
                    ticket_num: '',
                    status: '',
                    maintenance_note: ''
                },
                compoundForm: {
                    plant: '',
                    machine_name: '',
                    tanggal_cek: '',

                    // Data Drawing
                    drawing_type: '',
                    drawing_supplier: '',
                    drawing_warna: '',
                    drawing_konsentrasi: '',
                    drawing_ph: '',
                    drawing_temp: '',

                    // Data Annealing
                    annealing_type: '',
                    annealing_supplier: '',
                    annealing_warna: '',
                    annealing_konsentrasi: '',
                    annealing_ph: '',
                    annealing_temp: '',

                    keterangan: ''
                },
                // ================= FUNCTIONS =================

                init() {
                    this.updateTime();
                    setInterval(() => {
                        this.updateTime();
                    }, 60000);

                    const saved = localStorage.getItem('selected_wo_ids');
                    if (saved) {
                        try {
                            this.selectedTickets = JSON.parse(saved);
                        } catch (e) {
                            this.selectedTickets = [];
                        }
                    }

                    this.$watch('selectedTickets', (value) => {
                        localStorage.setItem('selected_wo_ids', JSON.stringify(value));
                    });

                    this.$watch('showCreateModal', (value) => {
                        if (!value) {
                            this.resetForm();
                        }
                    });
                },

                updateTime() {
                    const now = new Date();
                    const year = now.getFullYear();
                    const month = String(now.getMonth() + 1).padStart(2, '0');
                    const day = String(now.getDate()).padStart(2, '0');
                    this.currentDate = `${year}-${month}-${day}`;

                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    this.currentTime = `${hours}:${minutes}`;

                    const totalMinutes = (now.getHours() * 60) + now.getMinutes();
                    if (totalMinutes >= 405 && totalMinutes <= 915) {
                        this.currentShift = '1';
                    } else if (totalMinutes >= 916 && totalMinutes <= 1365) {
                        this.currentShift = '2';
                    } else {
                        this.currentShift = '3';
                    }
                },

                resetForm() {
                    this.selectedPlant = '';
                    this.machineOptions = [];
                    this.isManualInput = false;
                    this.work_method = 'sendiri';

                    // Reset semua field form
                    this.form.plant = '';
                    this.form.machine_name = '';
                    this.form.kerusakan_detail = '';
                    this.form.damaged_part = '';
                    this.form.file_name = '';
                    this.form.engineer_tech = [];
                    // Reset field SPK baru
                    this.form.operator_name = '';
                    this.form.target_date = '';
                    this.form.target_time = '';
                    this.form.action_taken = '';
                    this.form.spare_parts = '';
                },

                toggleSelectAll() {
                    const allSelected = this.pageIds.every(id => this.selectedTickets.includes(id));
                    if (allSelected) {
                        this.selectedTickets = this.selectedTickets.filter(id => !this.pageIds.includes(
                            id));
                    } else {
                        this.pageIds.forEach(id => {
                            if (!this.selectedTickets.includes(id)) this.selectedTickets.push(
                                id);
                        });
                    }
                },

                toggleEngineer(name) {
                    if (this.form.engineer_tech.includes(name)) {
                        this.form.engineer_tech = this.form.engineer_tech.filter(n => n !== name);
                    } else {
                        if (this.form.engineer_tech.length < 5) {
                            this.form.engineer_tech.push(name);
                        } else {
                            alert('Maksimal 5 Engineer!');
                        }
                    }
                },

                handleExportClick() {
                    if (this.selectedTickets.length > 0) {
                        const ids = this.selectedTickets.join(',');
                        window.location.href = `{{ route('eng.export') }}?ticket_ids=${ids}`;
                        setTimeout(() => {
                            this.selectedTickets = [];
                            localStorage.removeItem('selected_wo_ids');
                        }, 2000);
                    } else {
                        this.showExportModal = true;
                    }
                },

                onPlantChange() {
                    const plantData = this.allPlants.find(p => p.name === this.selectedPlant);
                    if (plantData && plantData.machines.length > 0) {
                        this.machineOptions = plantData.machines;
                        this.isManualInput = false;
                    } else {
                        this.machineOptions = [];
                        this.isManualInput = true;
                    }
                    this.form.plant = this.selectedPlant;
                    this.form.machine_name = '';
                },
                onCompoundPlantChange() {
                    // Cari data plant yang dipilih
                    const plantData = this.allPlants.find(p => p.name === this.compoundForm.plant);

                    // Jika ada plant dan ada mesinnya, tampilkan dropdown
                    if (plantData && plantData.machines.length > 0) {
                        this.machineOptions = plantData.machines;
                        this.isManualInput = false;
                    } else {
                        // Jika tidak ada mesin di plant tersebut, ubah jadi input teks biasa
                        this.machineOptions = [];
                        this.isManualInput = true;
                    }
                    // Kosongkan nama mesin saat plant berubah
                    this.compoundForm.machine_name = '';
                },
                handleFile(event) {
                    this.form.file_name = event.target.files[0] ? event.target.files[0].name : '';
                },

                submitForm() {
                    // Kita gunakan ref yang dinamis tergantung modal mana yang terbuka
                    let formRef = this.showSpkModal ? this.$refs.spkCreateForm : this.$refs.createForm;

                    if (formRef && formRef.reportValidity()) {
                        formRef.submit();
                    } else {
                        // Jika validasi gagal atau form tidak ketemu, tutup modal konfirmasi jika terbuka
                        this.showConfirmModal = false;
                    }
                },

                openDetailModal(data, reporterName) {
                    this.ticket = data;
                    this.ticket.requester_name = reporterName;
                    this.showDetailModal = true;
                },

                openEditModal(data, reporterName) {
                    this.ticket = data;
                    this.ticket.requester_name = reporterName;

                    this.editForm.id = data.id;
                    this.editForm.ticket_num = data.ticket_num;
                    this.editForm.status = data.improvement_status;
                    this.editForm.maintenance_note = '';

                    this.showEditModal = true;
                }
            }));
        });
    </script>
</x-app-layout>
