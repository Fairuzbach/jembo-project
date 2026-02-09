@props(['plants', 'categoriesDB', 'categories'])

<template x-teleport="body">
    <div x-show="showCreateModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity" @click="showCreateModal = false">
        </div>

        {{-- Container Utama: p-4 agar ada jarak dari tepi layar HP --}}
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">

            {{-- Wrapper Modal --}}
            <div class="relative w-full max-w-md md:max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all border border-slate-100 text-left my-8"
                x-data="{
                    // 1. DATA PLANTS (Server Side)
                    plantsData: @js($plants),
                
                    // 2. DATA USER LOGIN
                    currentUser: {
                        nik: '{{ Auth::user()->nik }}',
                        name: '{{ Auth::user()->name }}',
                        dept: '{{ Auth::user()->divisi }}',
                        plant_id: '{{ Auth::user()->plant_id }}'
                    },
                
                    // 3. FORM DATA
                    formData: {
                        nik: '{{ Auth::user()->nik }}',
                        manual_requester_name: '{{ Auth::user()->name }}',
                
                        // Default Plant
                        plant_id: '{{ old('plant_id', Auth::user()->plant_id) }}',
                
                        // Default Dept
                        department: '{{ old('department', Auth::user()->divisi) }}',
                
                        // DEFAULT VALUES (Hidden from User)
                        category: 'RINGAN',
                        parameter_permintaan: '-',
                        status_permintaan: 'OPEN',
                        target_completion_date: '',
                        description: ''
                    },
                
                    // 4. STATE VARIABLES
                    displayDept: '{{ old('department', Auth::user()->divisi) }}',
                    departments: [],
                    isChecking: false,
                    isSubmitting: false,
                    isLoadingDept: false,
                
                    // 5. INITIALIZATION
                    init() {
                        if (this.formData.plant_id) {
                            this.fetchDepartments(this.formData.plant_id, true);
                        }
                    },
                
                    // 6. FUNGSI-FUNGSI
                    resetToMe() {
                        this.formData.nik = this.currentUser.nik;
                        this.formData.manual_requester_name = this.currentUser.name;
                        this.displayDept = this.currentUser.dept;
                        this.formData.department = this.currentUser.dept;
                        this.formData.plant_id = this.currentUser.plant_id;
                        this.fetchDepartments(this.currentUser.plant_id, true);
                    },
                
                    async fetchDepartments(plantId, keepSelected = false) {
                        if (!plantId) {
                            this.departments = [];
                            return;
                        }
                
                        this.isLoadingDept = true;
                        try {
                            const response = await fetch('/ga/get-departments/' + plantId);
                
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                
                            this.departments = await response.json();
                
                            if (!keepSelected) {
                                this.formData.department = '';
                                this.displayDept = '';
                            }
                        } catch (error) {
                            console.error('Gagal memuat department', error);
                            this.departments = [];
                        } finally {
                            this.isLoadingDept = false;
                        }
                    },
                
                    syncDisplayDept() {
                        this.displayDept = this.formData.department;
                    },
                
                    async checkNik() {
                        let inputNik = this.formData.nik ? this.formData.nik.toString().trim() : '';
                        if (!inputNik) return;
                
                        let myNik = this.currentUser.nik.toString().trim();
                        if (inputNik === myNik) {
                            this.resetToMe();
                            return;
                        }
                
                        this.isChecking = true;
                        try {
                            const response = await axios.get('/ga/check-employee', {
                                params: { nik: inputNik }
                            });
                
                            if (response.data.status === 'success') {
                                this.formData.manual_requester_name = response.data.data.name;
                                this.formData.department = response.data.data.department;
                                this.displayDept = response.data.data.department;
                
                                Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
                                    .fire({ icon: 'success', title: 'Ditemukan: ' + response.data.data.name });
                            }
                        } catch (e) {
                            console.error('Error saat checkNik:', e);
                            this.formData.manual_requester_name = '';
                            this.displayDept = '';
                            this.formData.department = '';
                            Swal.fire({ toast: true, position: 'top', icon: 'error', title: 'NIK Tidak Ditemukan' });
                        } finally {
                            this.isChecking = false;
                        }
                    },
                
                    openConfirm() {
                        // VALIDASI DISEDERHANAKAN
                        if (!this.formData.plant_id || !this.formData.description || !this.formData.department) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Data Belum Lengkap',
                                text: 'Mohon lengkapi Lokasi, Department, dan Uraian Pekerjaan.'
                            });
                            return;
                        }
                        window.gaFormData = JSON.parse(JSON.stringify(this.formData));
                        $dispatch('open-confirm-modal');
                    },
                }" x-init="init()">

                {{-- LOADING OVERLAY --}}
                <div x-show="isSubmitting" x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    class="absolute inset-0 z-[100] bg-white/95 backdrop-blur-sm flex flex-col items-center justify-center">
                    <div class="flex space-x-3 mb-6">
                        <div class="w-5 h-5 bg-[#1E3A5F] rounded-full animate-bounce [animation-delay:-0.3s]"></div>
                        <div class="w-5 h-5 bg-yellow-500 rounded-full animate-bounce [animation-delay:-0.15s]"></div>
                        <div class="w-5 h-5 bg-[#1E3A5F] rounded-full animate-bounce"></div>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800">Mohon Tunggu</h3>
                    <p class="text-slate-500 text-sm mt-1">Sedang membuat tiket Anda...</p>
                </div>

                {{-- Header (Responsive Padding) --}}
                <div
                    class="bg-gradient-to-r from-[#1E3A5F] to-slate-700 px-4 py-4 md:px-8 md:py-7 flex justify-between items-center relative z-10">
                    <h3
                        class="text-lg md:text-xl font-bold text-white uppercase tracking-wide flex items-center gap-2 md:gap-3">
                        <span
                            class="bg-yellow-400 text-slate-900 px-2 py-1 md:px-3 md:py-1.5 text-[10px] md:text-xs font-black rounded-lg">NEW</span>
                        Create Work Order
                    </h3>
                    <button @click="showCreateModal = false"
                        class="text-white/60 hover:text-white rounded-full p-1 md:p-2.5 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                {{-- Body Form --}}
                <form x-ref="createForm"
                    @submit-confirmed.window="isSubmitting = true; setTimeout(() => $refs.createForm.submit(), 500)"
                    action="{{ route('ga.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- HIDDEN INPUTS --}}
                    <input type="hidden" name="category" value="RINGAN">
                    <input type="hidden" name="parameter_permintaan" value="-">
                    <input type="hidden" name="status_permintaan" value="OPEN">

                    {{-- Responsive Padding Content (p-4 di HP, p-8 di PC) --}}
                    <div class="p-4 md:p-8 space-y-4 md:space-y-6">

                        {{-- SECTION 1: IDENTITAS --}}
                        <div class="bg-slate-50 p-4 md:p-5 rounded-sm border border-slate-200 mb-4 md:mb-6">
                            <div class="flex justify-between items-center mb-4">
                                <label
                                    class="block text-[10px] md:text-xs font-black text-slate-400 uppercase tracking-widest">
                                    IDENTITAS PELAPOR
                                </label>
                                <button type="button" x-show="formData.nik !== currentUser.nik" @click="resetToMe()"
                                    class="text-[10px] bg-slate-200 hover:bg-slate-300 text-slate-600 px-2 py-1 rounded font-bold transition-colors flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Reset
                                </button>
                            </div>

                            {{-- Grid Responsive (1 kolom di HP, 2 kolom di PC) --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- INPUT NIK --}}
                                <div>
                                    <label class="text-xs font-bold text-slate-700 uppercase mb-1">NIK <span
                                            class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="text" name="requester_nik" x-model="formData.nik"
                                            @keydown.enter.prevent="checkNik()" @blur="checkNik()"
                                            class="w-full border-2 border-slate-300 focus:border-slate-900 rounded-sm text-sm font-bold h-11 placeholder-slate-300 px-3 cursor-not-allowed"
                                            placeholder="Ketik NIK..." required readonly>
                                        <div x-show="isChecking" class="absolute right-3 top-3" style="display: none;">
                                            <svg class="animate-spin h-5 w-5 text-slate-900" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                                    stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                {{-- INPUT NAMA & DEPT --}}
                                <div>
                                    <label class="text-xs font-bold text-slate-700 uppercase mb-1">Nama & Dept</label>
                                    <input type="text"
                                        :value="formData.manual_requester_name ? (formData.manual_requester_name + (
                                            displayDept ? ' - ' + displayDept : '')) : '-'"
                                        readonly
                                        class="w-full bg-slate-200 border-2 border-slate-200 text-slate-500 font-bold text-sm h-11 px-3 cursor-not-allowed mb-2 focus:outline-none">
                                    <input type="hidden" name="requester_name" :value="formData.manual_requester_name">
                                </div>
                            </div>
                        </div>

                        {{-- SECTION 2: AREA KERJA --}}
                        <div class="bg-slate-50 p-4 md:p-5 rounded-sm border border-slate-200 mt-4 md:mt-6">
                            <label
                                class="block text-[10px] md:text-xs font-black text-slate-400 uppercase mb-4 tracking-widest">Detail
                                Lokasi</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                {{-- 1. PILIH PLANT --}}
                                <div>
                                    <label class="text-xs font-bold text-slate-600 uppercase mb-1">Lokasi Pekerjaan
                                        <span class="text-red-500">*</span></label>
                                    <select name="plant_id" x-model="formData.plant_id"
                                        @change="fetchDepartments($event.target.value)"
                                        class="w-full border-2 border-slate-300 focus:border-slate-900 rounded-sm text-sm font-bold h-11 bg-white"
                                        required>
                                        <option value="">-- PILIH LOKASI --</option>
                                        @foreach ($plants as $plant)
                                            <option value="{{ $plant->id }}">{{ $plant->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- 2. PILIH DEPT --}}
                                <div>
                                    <label class="text-xs font-bold text-slate-600 uppercase mb-1">
                                        Department Pelapor <span class="text-red-500">*</span>
                                        <span x-show="isLoadingDept"
                                            class="text-[10px] text-yellow-600 ml-2 animate-pulse">Loading...</span>
                                    </label>

                                    <select name="department" x-model="formData.department"
                                        @change="syncDisplayDept()"
                                        class="w-full border-2 border-slate-300 focus:border-slate-900 rounded-sm text-sm font-bold bg-white h-11"
                                        required :disabled="isLoadingDept">

                                        <option value="">-- PILIH DEPARTMENT --</option>
                                        <template x-for="(deptName, deptKey) in departments" :key="deptKey">
                                            <option :value="deptName" x-text="deptName"></option>
                                        </template>

                                    </select>
                                    <p x-show="!formData.plant_id" class="text-[10px] text-red-400 mt-1 italic">Pilih
                                        Lokasi terlebih dahulu</p>
                                </div>
                            </div>
                        </div>

                        {{-- SECTION 3: URAIAN & FOTO --}}
                        <div class="mt-4 md:mt-6">
                            <label class="text-xs font-bold text-slate-700 uppercase mb-1">Uraian Pekerjaan <span
                                    class="text-red-500">*</span></label>
                            <textarea name="description" x-model="formData.description" rows="4"
                                class="w-full border-2 border-slate-300 focus:border-slate-900 rounded-sm text-sm font-medium p-3"
                                placeholder="Jelaskan secara detail apa yang perlu dikerjakan..." required></textarea>
                        </div>

                        <div class="mt-4">
                            <label class="text-xs font-bold text-slate-700 uppercase mb-1">Foto Bukti
                                (Opsional)</label>
                            <input type="file" accept="image/*, .pdf" name="photo"
                                class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-sm file:border-0 file:text-xs file:font-black file:uppercase file:bg-slate-900 file:text-white hover:file:bg-slate-700 cursor-pointer border border-slate-300 rounded-sm">
                            <p class="text-[10px] text-slate-400 mt-1 italic">*Mendukung Foto & PDF.</p>
                        </div>

                        {{-- Footer (Responsive Buttons) --}}
                        <div
                            class="px-4 py-4 md:px-8 md:py-5 bg-slate-50 flex flex-row gap-3 border-t border-slate-200 mt-4 md:mt-6">
                            {{-- Tombol Batal --}}
                            <button type="button" @click="showCreateModal = false"
                                class="flex-1 md:flex-none bg-white border-2 border-slate-200 text-slate-600 hover:border-slate-400 px-4 py-3.5 rounded-xl font-bold uppercase tracking-wide shadow-sm hover:shadow-md transition-all text-xs md:text-sm">
                                Batal
                            </button>

                            {{-- Tombol Kirim --}}
                            <button type="button" @click="openConfirm()"
                                class="flex-1 md:flex-none bg-gradient-to-br from-yellow-400 via-yellow-500 to-amber-500 text-slate-900 hover:from-yellow-500 px-6 py-3.5 rounded-xl font-bold uppercase tracking-wider shadow-lg hover:scale-105 active:scale-95 transition-all text-xs md:text-sm">
                                Kirim Tiket
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

@if ($errors->any())
    <script>
        let errorMessages = '';
        @foreach ($errors->all() as $error)
            errorMessages += '<li style="text-align: left;">{{ $error }}</li>';
        @endforeach

        Swal.fire({
            icon: 'error',
            title: 'Gagal Menyimpan!',
            html: `<ul>${errorMessages}</ul>`,
            confirmButtonText: 'Perbaiki',
            confirmButtonColor: '#d33',
        });
    </script>
@endif
