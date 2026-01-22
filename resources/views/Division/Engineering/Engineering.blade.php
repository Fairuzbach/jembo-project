@section('browser_title', 'Engineering Improvement Order')
<x-app-layout title="Engineering Improvement Order">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight relative z-10">
            {{ __('Engineering Improvement Order') }}
        </h2>
    </x-slot>

    {{-- LOAD LIBRARY DI ATAS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    {{-- CONTAINER UTAMA --}}
    <div class="py-12" x-data="{
        // --- 1. MODAL STATES ---
        showDetailModal: false,
        showCreateModal: false,
        showConfirmModal: false,
        showEditModal: false,
        showExportModal: false,
    
        // --- 2. DATA EXPORT ---
        selectedTickets: [],
        pageIds: {{ Js::from($workOrders->pluck('id')) }},
    
        // --- 3. DATA HOLDER ---
        ticket: {}, // Initialized as empty object
        allPlants: {{ Js::from($plants) }},
        allTechnicians: {{ Js::from($technicians) }},
    
        // --- 4. FORM VARIABLES ---
        currentDate: '',
        currentTime: '',
        currentShift: '',
        selectedPlant: '',
        machineOptions: [],
        work_method: 'sendiri',
        currentUsername: '{{ auth()->user()->name }}',
        userRole: '{{ auth()->user()->role }}',
        isManualInput: false,
    
        form: { kerusakan: '', kerusakan_detail: '', priority: 'low', initial_status: 'OPEN', plant: '', machine_name: '', damaged_part: '', production_status: '', file_name: '', improvement_parameters: '', engineer_tech: [] },
    
        editForm: { id: '', ticket_num: '', status: '', maintenance_note: '' },
    
        // ================= FUNCTIONS =================
    
        toggleSelectAll() {
            const allSelected = this.pageIds.every(id => this.selectedTickets.includes(id));
            if (allSelected) {
                this.selectedTickets = this.selectedTickets.filter(id => !this.pageIds.includes(id));
            } else {
                this.pageIds.forEach(id => {
                    if (!this.selectedTickets.includes(id)) this.selectedTickets.push(id);
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
            if (totalMinutes >= 405 && totalMinutes <= 915) { this.currentShift = '1'; } else if (totalMinutes >= 916 && totalMinutes <= 1365) { this.currentShift = '2'; } else { this.currentShift = '3'; }
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
    
        handleFile(event) { this.form.file_name = event.target.files[0] ? event.target.files[0].name : ''; },
    
        submitForm() {
            if (this.$refs.createForm.reportValidity()) { this.$refs.createForm.submit(); } else { this.showConfirmModal = false; }
        },
    
        // --- OPEN DETAIL MODAL ---
        openDetailModal(data, reporterName) {
            this.ticket = data;
            // Inject Reporter Name manually
            this.ticket.requester_name = reporterName;
            this.showDetailModal = true;
        },
    
        // --- OPEN EDIT/APPROVAL MODAL ---
        openEditModal(data, reporterName) {
            this.ticket = data;
            // Inject Reporter Name manually
            this.ticket.requester_name = reporterName;
    
            // Map data to editForm
            this.editForm.id = data.id;
            this.editForm.ticket_num = data.ticket_num;
            this.editForm.status = data.improvement_status;
            this.editForm.maintenance_note = ''; // Reset note
    
            this.showEditModal = true;
        },
    
        // --- INIT ---
        init() {
            this.updateTime();
            setInterval(() => { this.updateTime(); }, 10000);
    
            const saved = localStorage.getItem('selected_wo_ids');
            if (saved) {
                try {
                    this.selectedTickets = JSON.parse(saved);
                } catch (e) { this.selectedTickets = []; }
            }
            this.$watch('selectedTickets', (value) => {
                localStorage.setItem('selected_wo_ids', JSON.stringify(value));
            });
    
            this.$watch('showCreateModal', (value) => {
                if (!value) {
                    this.selectedPlant = '';
                    this.machineOptions = [];
                    this.isManualInput = false;
                    this.form.plant = '';
                    this.form.machine_name = '';
                    this.form.kerusakan_detail = '';
                    this.form.damaged_part = '';
                    this.form.file_name = '';
                    this.form.engineer_tech = [];
                    this.work_method = 'sendiri';
                }
            });
        }
    }" x-init="init()">

        {{-- Auto Open Modal --}}
        @if ($errors->hasAny(['machine_name', 'damaged_part', 'production_status', 'kerusakan_detail', 'photo']))
            <div x-init="showCreateModal = true"></div>
        @endif

        @if ($errors->hasAny(['start_date', 'end_date']))
            <div x-init="showExportModal = true"></div>
        @endif

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- A. ALERT SUCCESS --}}
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative"
                    role="alert">
                    <strong class="font-bold">Berhasil!</strong>
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            {{-- ALERT ERROR --}}
            @if ($errors->any())
                <div x-data="{ show: true }" x-show="show" x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform translate-x-8"
                    x-transition:enter-end="opacity-100 transform translate-x-0" x-init="setTimeout(() => show = false, 5000)"
                    class="fixed top-24 right-5 z-[100] bg-red-500 text-white px-6 py-4 rounded-lg shadow-xl flex items-center gap-3 border-l-4 border-red-700">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h4 class="font-bold text-lg">Gagal Menyimpan!</h4>
                        <ul class="text-sm list-disc pl-4 mt-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <button @click="show = false" class="ml-4 text-white hover:text-red-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            @endif

            {{-- B. STATISTIK CARDS --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6" x-data="{ show: false }" x-init="setTimeout(() => show = true, 100)">

                {{-- Card Total --}}
                <div x-show="show" x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="opacity-0 translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="relative group bg-white overflow-hidden shadow-sm sm:rounded-xl p-6 border border-indigo-100 
                           transition-all duration-300 hover:shadow-xl hover:-translate-y-1 hover:border-indigo-300 cursor-default">
                    <div
                        class="absolute -right-6 -top-6 w-24 h-24 bg-indigo-50 rounded-full group-hover:bg-indigo-100 transition-all duration-500">
                    </div>

                    <div class="relative flex items-center justify-between z-10">
                        <div>
                            <div class="text-sm font-semibold text-indigo-500 mb-1 tracking-wide uppercase">Total Tiket
                            </div>
                            <div
                                class="text-3xl font-extrabold text-slate-800 group-hover:text-indigo-600 transition-colors">
                                {{ $countTotal }}
                            </div>
                        </div>
                        <div
                            class="p-3 bg-indigo-50 rounded-lg text-indigo-600 group-hover:scale-110 group-hover:bg-indigo-600 group-hover:text-white transition-all duration-300 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Card Open --}}
                <div x-show="show" x-transition:enter="transition ease-out duration-500 delay-100"
                    x-transition:enter-start="opacity-0 translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="relative group bg-white overflow-hidden shadow-sm sm:rounded-xl p-6 border border-blue-100 
           transition-all duration-300 hover:shadow-xl hover:-translate-y-1 hover:border-blue-300 cursor-default">

                    <div
                        class="absolute -right-6 -top-6 w-24 h-24 bg-blue-50 rounded-full group-hover:bg-blue-100 transition-all duration-500">
                    </div>

                    <div class="relative flex items-center justify-between z-10">
                        <div>
                            <div class="text-sm font-semibold text-blue-500 mb-1 tracking-wide uppercase">OPEN</div>
                            <div
                                class="text-3xl font-extrabold text-slate-800 group-hover:text-blue-600 transition-colors">
                                {{ $countPending }}
                            </div>
                        </div>
                        <div
                            class="p-3 bg-blue-50 rounded-lg text-blue-600 group-hover:scale-110 group-hover:bg-blue-500 group-hover:text-white transition-all duration-300 shadow-sm">
                            {{-- Icon Open (Folder/Document) --}}
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Card WIP --}}
                <div x-show="show" x-transition:enter="transition ease-out duration-500 delay-200"
                    x-transition:enter-start="opacity-0 translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="relative group bg-white overflow-hidden shadow-sm sm:rounded-xl p-6 border border-amber-100 
transition-all duration-300 hover:shadow-xl hover:-translate-y-1 hover:border-amber-300 cursor-default">
                    <div
                        class="absolute -right-6 -top-6 w-24 h-24 bg-amber-50 rounded-full group-hover:bg-amber-100 transition-all duration-500">
                    </div>

                    <div class="relative flex items-center justify-between z-10">
                        <div>
                            <div class="text-sm font-semibold text-amber-500 mb-1 tracking-wide uppercase">WIP (IN
                                PROGRESS)
                            </div>
                            <div
                                class="text-3xl font-extrabold text-slate-800 group-hover:text-amber-600 transition-colors">
                                {{ $countInProgress }}
                            </div>
                        </div>
                        <div
                            class="p-3 bg-amber-50 rounded-lg text-amber-600 group-hover:scale-110 group-hover:bg-amber-500 group-hover:text-white transition-all duration-300 shadow-sm">
                            {{-- Icon WIP (Tools/Settings) --}}
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Card CLOSED --}}
                <div x-show="show" x-transition:enter="transition ease-out duration-500 delay-200"
                    x-transition:enter-start="opacity-0 translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="relative group bg-white overflow-hidden shadow-sm sm:rounded-xl p-6 border border-emerald-100 
           transition-all duration-300 hover:shadow-xl hover:-translate-y-1 hover:border-emerald-300 cursor-default">

                    <div
                        class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-50 rounded-full group-hover:bg-emerald-100 transition-all duration-500">
                    </div>

                    <div class="relative flex items-center justify-between z-10">
                        <div>
                            <div class="text-sm font-semibold text-emerald-500 mb-1 tracking-wide uppercase">CLOSED
                            </div>
                            <div
                                class="text-3xl font-extrabold text-slate-800 group-hover:text-emerald-600 transition-colors">
                                {{ $countCompleted }}
                            </div>
                        </div>
                        <div
                            class="p-3 bg-emerald-50 rounded-lg text-emerald-600 group-hover:scale-110 group-hover:bg-emerald-500 group-hover:text-white transition-all duration-300 shadow-sm">
                            {{-- Icon Check/Success --}}
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- C. TABEL DATA --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg transition-colors">
                <div class="p-6 text-slate-900">
                    {{-- Header Tabel & Search --}}
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <div class="w-full md:w-2/3">
                            <form action="{{ route('eng.index') }}" method="GET"
                                class="flex flex-col md:flex-row gap-3">
                                <div class="relative w-full">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <svg class="w-4 h-4 text-slate-500" aria-hidden="true"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                            <path stroke="currentColor" stroke-linecap="round"
                                                stroke-linejoin="round" stroke-width="2"
                                                d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
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
                                        <option value="OPEN"
                                            {{ request('improvement_status') == 'OPEN' ? 'selected' : '' }}>Open
                                        </option>
                                        <option value="WIP"
                                            {{ request('improvement_status') == 'WIP' ? 'selected' : '' }}>WIP
                                        </option>
                                        <option value="CLOSED"
                                            {{ request('improvement_status') == 'CLOSED' ? 'selected' : '' }}>
                                            Closed
                                        </option>
                                        <option value="cancelled"
                                            {{ request('improvement_status') == 'cancelled' ? 'selected' : '' }}>
                                            Cancelled
                                        </option>
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
                                <svg class="w-5 h-5 text-emerald-100 group-hover:text-white transition-colors"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                <span
                                    x-text="selectedTickets.length > 0 ? 'Export (' + selectedTickets.length + ') Terpilih' : 'Export Data'"></span>
                            </button>
                            <button @click="showCreateModal = true" type="button"
                                class="group bg-gradient-to-r from-indigo-600 to-indigo-600 hover:from-indigo-700 hover:to-indigo-700 text-white font-semibold py-2.5 px-5 rounded-lg text-sm transition-all shadow-md hover:shadow-lg flex items-center gap-2 w-full md:w-auto justify-center focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                <svg class="w-5 h-5 text-indigo-100 group-hover:text-white transition-colors"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                Buat Laporan Baru
                            </button>
                        </div>
                    </div>

                    {{-- Tabel --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left w-10">
                                        <input type="checkbox" @click="toggleSelectAll()"
                                            :checked="pageIds.length > 0 && pageIds.every(id => selectedTickets.includes(id))"
                                            class="w-4 h-4 text-indigo-600 bg-slate-100 border-slate-300 rounded focus:ring-indigo-500 cursor-pointer">
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                        Tiket / Tanggal</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                        Mesin & Plant</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                        Judul dan Uraian</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                        Status</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                        Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200">
                                @forelse ($workOrders as $wo)
                                    <tr class="hover:bg-slate-50 transition-colors"
                                        :class="selectedTickets.includes({{ $wo->id }}) ? 'bg-indigo-50' : ''">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" value="{{ $wo->id }}"
                                                x-model="selectedTickets"
                                                class="w-4 h-4 text-indigo-600 bg-slate-100 border-slate-300 rounded focus:ring-indigo-500 cursor-pointer">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-bold text-indigo-600 font-mono">
                                                {{ $wo->ticket_num }}</div>
                                            <div class="text-xs text-slate-500">
                                                {{ \Carbon\Carbon::parse($wo->report_date)->format('d M Y') }} -
                                                {{ \Carbon\Carbon::parse($wo->report_time)->format('H:i') }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-slate-900">
                                                {{ $wo->machine_name ?? '-' }}</div>
                                            <div class="text-xs text-slate-500">
                                                {{ $wo->plant ?? '-' }}</div>
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
                                                    'OPEN'
                                                        => 'bg-blue-100 text-blue-800 ring-1 ring-inset ring-blue-600/20',
                                                    'WIP'
                                                        => 'bg-amber-100 text-amber-800 ring-1 ring-inset ring-amber-600/20',
                                                    'CLOSED'
                                                        => 'bg-emerald-100 text-emerald-800 ring-1 ring-inset ring-emerald-600/20',
                                                    'CANCELLED'
                                                        => 'bg-rose-100 text-rose-800 ring-1 ring-inset ring-rose-600/20',
                                                    default => 'bg-slate-100 text-slate-800',
                                                };
                                            @endphp
                                            <span
                                                class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full {{ $statusClass }}">
                                                {{ strtoupper(str_replace('_', ' ', $wo->improvement_status)) }}
                                                <div
                                                    class="text-xs text-slate-400 ml-1 uppercase border-l pl-1 border-slate-300">
                                                    {{ $wo->priority }}</div>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button type="button"
                                                @click='ticket = @json($wo); ticket.requester_name = @json($wo->requester->name ?? '-'); showDetailModal=true'
                                                class="text-indigo-600 hover:text-indigo-900 mr-3">Detail</button>
                                            @if (auth()->user()->id === $wo->requester_id && auth()->user()->role !== 'eng.admin')
                                                <button type="button"
                                                    @click="openEditModal({{ Js::from($wo) }}, '{{ $wo->requester->name ?? '-' }}')"
                                                    class="text-amber-600 hover:text-amber-900 font-bold ml-2">Edit
                                                    Status</button>
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
                                        <td colspan="7" class="px-6 py-12 text-center text-slate-500">Data
                                            Tidak Ditemukan</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $workOrders->links() }}</div>
                </div>
            </div>
        </div>

        {{-- MODAL 1: CREATE TICKET --}}
        <template x-teleport="body">
            <div x-show="showCreateModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto"
                role="dialog" aria-modal="true">
                <div x-show="showCreateModal" x-transition.opacity
                    class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity"
                    @click="showCreateModal = false"></div>
                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <div x-show="showCreateModal" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl">

                        <div
                            class="bg-white border-b border-slate-200 px-4 py-4 sm:px-6 flex justify-between items-center">
                            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                Fill the Improvement Request Form
                            </h3>
                            <button @click="showCreateModal = false"
                                class="text-slate-400 hover:text-slate-500 transition"><svg class="h-6 w-6"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg></button>
                        </div>

                        <form x-ref="createForm" action="{{ route('eng.store') }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="px-4 py-5 sm:p-6 space-y-6">
                                {{-- Row 1 --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div><label class="block text-sm font-semibold text-slate-700 mb-1">Tanggal
                                            Lapor</label>
                                        <input type="date" name="report_date" x-model="currentDate" readonly
                                            class="w-full rounded-md border-slate-300 bg-slate-100 text-slate-600 shadow-sm cursor-not-allowed font-bold">
                                    </div>
                                    <div><label class="block text-sm font-semibold text-slate-700 mb-1">Jam
                                            Lapor (WIB)</label>
                                        <input type="text" name="report_time" x-model="currentTime" readonly
                                            class="w-full rounded-md border-slate-300 bg-slate-100 text-slate-600 shadow-sm cursor-not-allowed font-bold">
                                    </div>
                                </div>
                                {{-- Row 2 --}}
                                <div class="mb-4">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Metode
                                        Pengerjaan</label>
                                    <div class="flex items-center gap-4">
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="radio" name="work_method_dummy" value="sendiri"
                                                x-model="work_method"
                                                class="form-radio text-blue-600 focus:ring-blue-500 border-gray-300">
                                            <span class="ml-2 text-sm text-slate-700">Dilakukan Sendiri</span>
                                        </label>

                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="radio" name="work_method_dummy" value="bersama"
                                                x-model="work_method"
                                                class="form-radio text-blue-600 focus:ring-blue-500 border-gray-300">
                                            <span class="ml-2 text-sm text-slate-700">Bersama Tim (Max 5)</span>
                                        </label>
                                    </div>
                                </div>

                                <template x-if="work_method === 'sendiri'">
                                    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                        <p class="text-sm text-blue-800 font-medium">
                                            Engineer: <span x-text="currentUsername"></span>
                                        </p>
                                        <p class="text-xs text-blue-600 mt-1">Anda akan tercatat sebagai pelaksana
                                            tunggal pekerjaan ini.</p>

                                        {{-- HIDDEN INPUT: Otomatis kirim nama user yang login --}}
                                        <input type="hidden" name="engineer_tech[]" :value="currentUsername">
                                    </div>
                                </template>

                                <template x-if="work_method === 'bersama'">
                                    <div class="mb-4">
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                                            Pilih Anggota Tim (Termasuk Anda jika ikut) <span
                                                class="text-red-500">*</span>
                                        </label>

                                        <div
                                            class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto border p-2 rounded bg-slate-50">
                                            @foreach ($technicians as $tech)
                                                <label class="inline-flex items-center space-x-2 cursor-pointer">
                                                    <input type="checkbox" value="{{ $tech->name }}"
                                                        {{-- Logic checked --}}
                                                        :checked="form.engineer_tech.includes('{{ $tech->name }}')"
                                                        {{-- Logic disable max 5 --}}
                                                        :disabled="form.engineer_tech.length >= 5 && !form.engineer_tech
                                                            .includes('{{ $tech->name }}')"
                                                        @change="toggleEngineer('{{ $tech->name }}')"
                                                        class="rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                                                    <span class="text-sm text-slate-700">{{ $tech->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>

                                        {{-- Validation Helper Message --}}
                                        <p class="text-xs mt-1"
                                            :class="form.engineer_tech.length == 0 ? 'text-red-500' : 'text-slate-500'">
                                            <span x-show="form.engineer_tech.length == 0">Wajib pilih minimal 1
                                                orang.</span>
                                            <span x-show="form.engineer_tech.length > 0">
                                                Terpilih: <span x-text="form.engineer_tech.length"></span>/5
                                            </span>
                                        </p>

                                        {{-- HIDDEN INPUT: Kirim array nama yang dipilih --}}
                                        <template x-for="name in form.engineer_tech">
                                            <input type="hidden" name="engineer_tech[]" :value="name">
                                        </template>
                                    </div>
                                </template>

                                {{-- Row 3 --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div><label class="block text-sm font-semibold text-slate-700 mb-1">Plant</label>
                                        <select name="plant" x-model="selectedPlant" @change="onPlantChange()"
                                            class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            required>
                                            <option value="">Pilih Plant</option>
                                            <template x-for="plant in allPlants" :key="plant.id">
                                                <option :value="plant.name" x-text="plant.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div><label class="block text-sm font-semibold text-slate-700 mb-1">Nama
                                            Mesin <span x-show="isManualInput && selectedPlant"
                                                class="text-xs text-indigo-500 ml-1">(Input Manual)</span></label>
                                        <select x-show="!isManualInput" x-model="form.machine_name"
                                            name="machine_name"
                                            class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            :disabled="isManualInput" :required="!isManualInput">
                                            <option value="">Pilih Mesin...</option>
                                            <template x-for="mesin in machineOptions" :key="mesin.id">
                                                <option :value="mesin.name" x-text="mesin.name"></option>
                                            </template>
                                        </select>
                                        <input x-show="isManualInput" type="text" x-model="form.machine_name"
                                            name="machine_name" placeholder="Ketik nama mesin..."
                                            class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            :disabled="!isManualInput" :required="isManualInput">
                                    </div>
                                </div>
                                {{-- Row 4 --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div><label class="block text-sm font-semibold text-slate-700 mb-1">Judul</label>
                                        <input type="text" name="damaged_part" x-model="form.damaged_part"
                                            placeholder="Contoh: Pengukuran hasil ketebalan lapisan timah.."
                                            class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-indigo-500"
                                            required>
                                        <input type="hidden" name="kerusakan" x-bind:value="form.damaged_part">
                                    </div>
                                    <div class="mb-4">
                                        <label for="improvement_parameters"
                                            class="block text-sm font-semibold text-slate-700 mb-1">Parameter
                                            Improvement</label>
                                        <select name="improvement_parameters" x-model="form.improvement_parameters"
                                            class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-indigo-500"
                                            required>
                                            <option value="" disabled selected>-- Pilih Parameter --</option>
                                            @foreach ($improvementParameters as $param)
                                                <option value="{{ $param->name }}"
                                                    {{ old('improvement_parameters') == $param->name ? 'selected' : '' }}>
                                                    {{ $param->name }} ({{ $param->name }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div><label
                                                class="block text-sm font-semibold text-slate-700 mb-1">Prioritas</label>
                                            <select name="priority" x-model="form.priority"
                                                class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-indigo-500">
                                                <option value="low">Low</option>
                                                <option value="medium">Medium</option>
                                                <option value="high">High</option>
                                                <option value="critical">Critical</option>
                                            </select>
                                        </div>
                                        <div><label class="block text-sm font-semibold text-slate-700 mb-1">Uraian
                                                Improvement</label>
                                            <textarea name="kerusakan_detail" x-model="form.kerusakan_detail" rows="1" placeholder="Jelaskan..."
                                                class="w-full rounded-md border-slate-300 text-slate-900 shadow-sm focus:border-indigo-500" required></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 mb-1">Status
                                                Awal</label>
                                            <select name="initial_status" x-model="form.initial_status"
                                                class="w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 font-bold"
                                                :class="{
                                                    'text-blue-600': form.initial_status === 'OPEN',
                                                    'text-amber-600': form.initial_status === 'WIP',
                                                    'text-emerald-600': form.initial_status === 'CLOSED'
                                                }">
                                                <option value="OPEN">OPEN</option>
                                                <option value="WIP">WIP (On Progress)</option>
                                                <option value="CLOSED">CLOSED (Selesai)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div><label class="block text-sm font-semibold text-slate-700 mb-1">Upload
                                            Foto (Opsional)</label>
                                        <input type="file" name="photo" @change="handleFile"
                                            class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                    </div>
                                </div>
                                <div
                                    class=" px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse items-center gap-3 rounded-b-lg">
                                    <button type="button" @click="showConfirmModal = true"
                                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:w-auto sm:text-sm transition-colors">Lihat
                                        & Kirim</button>
                                    <button type="button" @click="showCreateModal = false"
                                        class="text-slate-400 hover:text-red-500 transition mr-auto sm:mr-0"><svg
                                            class="w-6 h-6" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>

        {{-- MODAL 2: CONFIRMATION --}}
        <template x-teleport="body">
            <div x-show="showConfirmModal" style="display: none;" class="fixed inset-0 z-[60] overflow-y-auto"
                role="dialog" aria-modal="true">
                <div x-show="showConfirmModal" class="fixed inset-0 bg-slate-900 bg-opacity-90 transition-opacity"
                    @click="showConfirmModal = false"></div>
                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <div x-show="showConfirmModal" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border-2 border-indigo-500">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div
                                    class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-lg font-semibold leading-6 text-slate-900" id="modal-title">
                                        Konfirmasi
                                        Laporan</h3>
                                    <div class="mt-4 space-y-3 text-sm text-slate-600">
                                        <div class="grid grid-cols-2 gap-2 bg-slate-50 p-3 rounded-md">
                                            <span class="font-semibold">Tanggal:</span> <span
                                                x-text="currentDate"></span>
                                            <span class="font-semibold">Jam:</span> <span x-text="currentTime"></span>
                                            <span class="font-semibold">Shift:</span> <span
                                                x-text="currentShift"></span>
                                            <span class="font-semibold">Plant:</span> <span
                                                x-text="form.plant"></span>
                                            <span class="font-semibold">Mesin:</span> <span
                                                x-text="form.machine_name"></span>
                                            <span class="font-semibold">Judul:</span> <span
                                                x-text="form.damaged_part"></span>
                                            <span class="font-semibold">Parameter Improvement:</span> <span
                                                x-text="form.improvement_parameters ? form.improvement_parameters : 'Belum dipilih'"></span>

                                            <span class="font-semibold">Prioritas:</span> <span
                                                x-text="form.priority.toUpperCase()"></span>
                                        </div>
                                        <div><span class="font-bold block">Uraian Improvement:</span>
                                            <p class="italic" x-text="form.kerusakan_detail"></p>
                                        </div>
                                        <template x-if="form.file_name">
                                            <div class="text-indigo-500 text-xs">📎 File terlampir: <span
                                                    x-text="form.file_name"></span></div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-2">
                            <button type="button" @click="submitForm()"
                                class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto">Ya,
                                Kirim Laporan</button>
                            <button type="button" @click="showConfirmModal = false"
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Periksa
                                Lagi</button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        {{-- MODAL 3: DETAIL TICKET --}}
        <template x-teleport="body">
            <div x-show="showDetailModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
                <div x-show="showDetailModal" class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity"
                    @click="showDetailModal = false"></div>
                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <div x-show="showDetailModal" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl">
                        <div
                            class="bg-slate-50 px-4 py-3 sm:px-6 flex justify-between items-center border-b border-slate-200">
                            <h3 class="text-base font-semibold leading-6 text-slate-900">Detail Work Order</h3>
                            <button @click="showDetailModal = false"
                                class="text-slate-400 hover:text-slate-500">&times;</button>
                        </div>
                        <div class="bg-white px-6 py-6 max-h-[80vh] overflow-y-auto">
                            <template x-if="ticket">
                                <div class="space-y-6">
                                    <div class="flex justify-between items-start border-b border-slate-200 pb-4">
                                        <div><span
                                                class="text-xs font-bold text-slate-500 uppercase tracking-wider">Nomor
                                                Tiket</span>
                                            <p class="text-2xl font-bold text-indigo-600 font-mono mt-1"
                                                x-text="ticket.ticket_num"></p>
                                        </div>
                                        <div class="text-right"><span
                                                class="text-xs font-bold text-slate-500 uppercase tracking-wider">Status</span>
                                            <div class="mt-1"><span
                                                    class="px-3 py-1 text-sm font-semibold rounded-full bg-indigo-100 text-indigo-800"
                                                    x-text="ticket.improvement_status ? ticket.improvement_status.replace('_', ' ').toUpperCase() : ''"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                                        <div><span class="text-xs text-slate-500 block mb-1">Tanggal & Jam Lapor</span>
                                            <p class="text-sm font-medium text-slate-900"><span
                                                    x-text="ticket.report_date ? ticket.report_date.substring(0,10).replace(/-/g, '/'):''"></span>
                                                • <span
                                                    x-text="ticket.report_time ? ticket.report_time.substring(0,5) : ''"></span>
                                            </p>
                                        </div>
                                        <div><span class="text-xs text-slate-500 block mb-1">Pelapor</span>
                                            <p class="text-sm font-medium text-slate-900"><span
                                                    x-text="ticket.requester_name"></span> </p>
                                        </div>
                                        <div><span class="text-xs text-slate-500 block mb-1">Plant / Area</span>
                                            <p class="text-sm font-medium text-slate-900" x-text="ticket.plant"></p>
                                        </div>
                                        <div><span class="text-xs text-slate-500 block mb-1">Mesin / Unit</span>
                                            <p class="text-sm font-medium text-slate-900"
                                                x-text="ticket.machine_name">
                                            </p>
                                        </div>
                                        <div><span class="text-xs text-slate-500 block mb-1">Judul</span>
                                            <p class="text-sm font-medium text-slate-900"
                                                x-text="ticket.damaged_part">
                                            </p>
                                        </div>
                                        <div><span class="text-xs text-slate-500 block mb-1">Parameter
                                                Improvement</span>
                                            <p class="text-sm font-medium text-slate-900"
                                                x-text="ticket.improvement_parameters"></p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-slate-500 block mb-2">Engineer</span>

                                            <div class="flex flex-wrap gap-2">
                                                {{-- Cek apakah ada data technicians/engineer_tech --}}
                                                <template x-if="ticket.technicians || ticket.engineer_tech">

                                                    {{-- 
                LOGIKA UTAMA: 
                1. Ambil string nama (technicians atau engineer_tech)
                2. Gunakan .split(',') untuk memecahnya menjadi Array
                3. Loop menggunakan x-for
            --}}
                                                    <template
                                                        x-for="techName in (ticket.technicians || ticket.engineer_tech).split(',')"
                                                        :key="techName">

                                                        {{-- Tampilan Badge Per Nama --}}
                                                        <span
                                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700 border border-indigo-200 shadow-sm">
                                                            {{-- Icon User Kecil (Opsional, pemanis) --}}
                                                            <svg class="w-3 h-3 mr-1.5 opacity-50" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                                                </path>
                                                            </svg>

                                                            {{-- Render Nama (trim() untuk menghapus spasi berlebih) --}}
                                                            <span x-text="techName.trim()"></span>
                                                        </span>

                                                    </template>
                                                </template>

                                                {{-- Fallback jika kosong --}}
                                                <template x-if="!ticket.technicians && !ticket.engineer_tech">
                                                    <span class="text-sm text-slate-400 italic">- Tidak ada teknisi
                                                        -</span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-100">
                                        <span
                                            class="text-xs font-bold text-slate-500 uppercase tracking-wide block mb-2">Uraian
                                            Improvement</span>
                                        <p class="text-sm text-slate-800 whitespace-pre-wrap leading-relaxed"
                                            x-text="ticket.kerusakan_detail"></p>
                                    </div>
                                    <template x-if="ticket.photo_path">
                                        <div><span
                                                class="text-xs font-bold text-slate-500 uppercase tracking-wide block mb-2">Foto
                                                Bukti</span>
                                            <div class="rounded-lg overflow-hidden border border-slate-200">
                                                <img :src="'/storage/' + ticket.photo_path" alt="Bukti Foto"
                                                    class="w-full h-auto max-h-96 object-contain bg-slate-100">
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="button"
                                class="inline-flex w-full justify-center rounded-md bg-white border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm hover:bg-slate-50 sm:ml-3 sm:w-auto"
                                @click="showDetailModal = false">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        {{-- MODAL 4: EDIT TICKET --}}
        <template x-teleport="body">
            <div x-show="showEditModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity"
                    @click="showEditModal = false"></div>
                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <div
                        class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">

                        <div class="bg-white px-4 py-4 sm:px-6 border-b border-slate-200">
                            <h3 class="text-lg font-bold text-slate-900"
                                x-text="userRole === 'eng.admin' ? 'Admin Approval #' + editForm.ticket_num : 'Update Status Laporan #' + editForm.ticket_num">
                            </h3>
                        </div>

                        <form x-ref="editFormHtml" :action="'/engineering/' + editForm.id + '/update-status'"
                            method="POST">
                            @csrf
                            @method('PUT')

                            <div class="px-6 py-6 space-y-6">
                                {{-- Info Ringkas --}}
                                <div class="bg-slate-50 p-4 rounded-md border border-slate-200 text-sm">
                                    <p class="font-bold text-slate-700" x-text="ticket.damaged_part"></p>
                                    <p class="text-slate-500 mt-1" x-text="ticket.kerusakan_detail"></p>
                                </div>

                                {{-- === TAMPILAN USER (UPDATE STATUS) === --}}
                                <template x-if="userRole !== 'eng.admin'">
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-2">Update Status
                                            Pengerjaan</label>
                                        <div class="grid grid-cols-2 gap-4">
                                            {{-- Pilihan WIP --}}
                                            <label class="cursor-pointer">
                                                <input type="radio" name="status" value="WIP"
                                                    x-model="editForm.status" class="peer sr-only">
                                                <div
                                                    class="rounded-md border border-slate-200 p-4 text-center hover:bg-amber-50 peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-700 transition-all">
                                                    <div class="font-bold">WIP</div>
                                                    <div class="text-xs">Sedang Dikerjakan</div>
                                                </div>
                                            </label>

                                            {{-- Pilihan CLOSED --}}
                                            <label class="cursor-pointer">
                                                <input type="radio" name="status" value="CLOSED"
                                                    x-model="editForm.status" class="peer sr-only">
                                                <div
                                                    class="rounded-md border border-slate-200 p-4 text-center hover:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 transition-all">
                                                    <div class="font-bold">CLOSED</div>
                                                    <div class="text-xs">Selesai (Auto Date)</div>
                                                </div>
                                            </label>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-2">*Memilih CLOSED akan otomatis mencatat
                                            tanggal selesai hari ini.</p>
                                    </div>
                                </template>

                                {{-- === TAMPILAN ADMIN (APPROVAL LAMA) === --}}
                                <template x-if="userRole === 'eng.admin'">
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-2">Keputusan
                                            Admin</label>
                                        <select name="status" x-model="editForm.status"
                                            class="w-full rounded-md border-slate-300">
                                            <option value="OPEN">OPEN (Pending)</option>
                                            <option value="WIP">WIP (In Progress)</option>
                                            <option value="CLOSED">CLOSED (Completed)</option>
                                            <option value="cancelled">CANCELLED</option>
                                        </select>
                                    </div>
                                </template>

                            </div>

                            <div
                                class="bg-slate-50 px-6 py-4 sm:flex sm:flex-row-reverse border-t border-slate-200 gap-3">
                                <button type="submit"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                                    Simpan Status
                                </button>
                                <button type="button" @click="showEditModal = false"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                    Batal
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>

        {{-- MODAL 5: EXPORT --}}
        <template x-teleport="body">
            <div x-show="showExportModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
                <div x-show="showExportModal" x-transition.opacity
                    class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity"
                    @click="showExportModal = false"></div>
                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <div x-show="showExportModal"
                        class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200">
                        <div class="bg-white px-4 py-4 sm:px-6 border-b border-slate-200">
                            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">Export
                                Data Laporan</h3>
                        </div>
                        <form action="{{ route('eng.export') }}" method="GET">
                            <div class="px-6 py-6 space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div><label class="block text-sm font-medium text-slate-700 mb-1">Dari
                                            Tanggal</label><input type="date" name="start_date" required
                                            class="w-full rounded-md border-slate-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                    </div>
                                    <div><label class="block text-sm font-medium text-slate-700 mb-1">Sampai
                                            Tanggal</label><input type="date" name="end_date" required
                                            class="w-full rounded-md border-slate-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                    </div>
                                </div>
                            </div>
                            <div class="bg-slate-50 px-6 py-4 sm:flex sm:flex-row-reverse gap-3">
                                <button type="submit"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Download</button>
                                <button type="button" @click="showExportModal = false"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>

    </div> {{-- Closing X-DATA div --}}

    {{-- SCRIPTS (Backup) --}}
    <script>
        function handleSubmit() {
            document.getElementById('loading-spinner').style.display = 'block';
            setTimeout(function() {
                document.getElementById('loading-spinner').style.display = 'none';
                alert('Jika download belum selesai, silahkan tunggu sebentar lagi..');
            }, 5000)
        }
    </script>
</x-app-layout>
