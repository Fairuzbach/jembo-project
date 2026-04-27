{{-- resources/views/Division/Facilities/partials/scripts.blade.php --}}

<script>
    // 1. SweetAlert Logic
    @if (session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: '{{ session('success') }}',
            confirmButtonColor: '#1E3A5F',
            timer: 2000,
            showConfirmButton: false
        })
    @endif

    @if (session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '{{ session('error') }}',
            confirmButtonColor: '#d33',
        })
    @endif

    // 2. Fungsi Helper Reject
    function promptReject(formElement) {
        const reason = prompt("Masukkan alasan penolakan:");
        if (reason) {
            const reasonInput = document.createElement('input');
            reasonInput.type = 'hidden';
            reasonInput.name = 'reason';
            reasonInput.value = reason;
            formElement.appendChild(reasonInput);
            formElement.submit();
        }
        return false; // Prevent default submit
    }

    // 3. ALPINE JS DATA OBJECT (REFACTOR x-data)
    // bungkus semua logic x-data ke dalam fungsi ini
    function facilitiesData() {
        return {
            // --- DATA USER ---
            currentUserRole: @json(auth()->user()->role),
            currentUserDivisi: @json(auth()->user()->divisi ?? ''),
            currentUserJabatan: @json(auth()->user()->jabatan ?? ''),

            // State
            showCreateModal: false,
            showEditModal: false,
            showDetailModal: false,
            ticket: null,

            isMachineDropdownOpen: false,
            searchMachine: '',

            // --- FORM DATA ---
            form: {
                requester_name: '',
                plant_id: '',
                machine_id: '',
                new_machine_name: '',
                category: '',
                description: '',
                target_completion_date: '',
                photo: null
            },

            editForm: {
                id: '',
                status: '',
                start_date: '',
                actual_completion_date: '',
                completion_note: '',
                selectedTechs: []
            },

            // Data Master
            machinesData: @json($machines),
            techniciansData: @json($technicians),
            filteredMachines: [],
            ticketsData: @json($workOrders->items()),
            pageIds: @json($pageIds),
            selectedTickets: [],

            // Time
            currentDate: '',
            currentDateDB: '',
            currentTime: '',
            currentShift: '',

            get searchedMachines() {
                if (this.searchMachine.trim() === '') {
                    return this.filteredMachines;
                }
                return this.filteredMachines.filter(m => m.name.toLowerCase().includes(this.searchMachine
                    .toLowerCase()));
            },

            get selectedMachineName() {
                const machine = this.filteredMachines.find(m => m.id == this.form.machine_id);
                return machine ? machine.name : '-- Pilih Mesin --';
            },

            // =====================================================================
            // 1. INIT & WATCHER 
            // =====================================================================
            init() {
                this.updateTime();
                setInterval(() => this.updateTime(), 1000);

                //WATCHER: Pantau perubahan pada dropdown Status
                this.$watch('editForm.status', (newStatus) => {

                    // A. Jika status berubah ke PROGRESS atau COMPLETED
                    if (newStatus === 'in_progress' || newStatus === 'completed') {
                        // Cek: Kalau Start Date masih kosong, isi dengan Waktu Sekarang
                        if (!this.editForm.start_date) {
                            this.editForm.start_date = this.getNowISO();
                        }
                    }

                    // B. Jika status berubah ke COMPLETED
                    if (newStatus === 'completed') {
                        // Cek: Kalau Completion Date masih kosong, isi dengan Waktu Sekarang
                        if (!this.editForm.actual_completion_date) {
                            this.editForm.actual_completion_date = this.getNowISO();
                        }
                    }
                });
            },

            // Helper untuk dapatkan waktu sekarang format HTML5 (YYYY-MM-DDTHH:MM)
            getNowISO() {
                let now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                return now.toISOString().slice(0, 16);
            },

            // =====================================================================
            // 2. OPEN MODAL LOGIC
            // =====================================================================
            openEditModal(wo) {
                this.ticket = wo;
                this.editForm.id = wo.id;
                this.editForm.status = wo.status;
                this.editForm.selectedTechs = wo.technicians ? wo.technicians.map(t => t.id) : [];

                // A. LOAD START DATE (Jangan Reset jika ada)
                if (wo.start_date) {
                    // Ubah format DB "2023-01-01 10:00:00" -> HTML "2023-01-01T10:00"
                    this.editForm.start_date = wo.start_date.replace(' ', 'T').substring(0, 16);
                } else {
                    // Jika kosong, biarkan kosong (nanti diisi oleh Watcher di atas jika status berubah)
                    this.editForm.start_date = '';
                }

                // B. LOAD COMPLETION DATE
                if (wo.actual_completion_date) {
                    this.editForm.actual_completion_date = wo.actual_completion_date.replace(' ', 'T').substring(0, 16);
                } else {
                    this.editForm.actual_completion_date = ''; // Biarkan kosong/auto-fill by watcher
                }

                this.editForm.completion_note = wo.completion_note || '';

                this.showEditModal = true;
            },

            // --- Helper Function Lainnya (Sama seperti sebelumnya) ---
            updateTime() {
                const now = new Date();
                this.currentDate = now.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric'
                });

                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                this.currentDateDB = `${year}-${month}-${day}`;

                this.currentTime = now.toLocaleTimeString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                });
                const hour = now.getHours();
                this.currentShift = (hour >= 7 && hour < 15) ? '1 (Pagi)' : ((hour >= 15 && hour < 23) ? '2 (Sore)' :
                    '3 (Malam)');
            },

            filterMachines() {
                this.form.machine_id = '';
                this.searchMachine = '';
                this.isMachineDropdownOpen = false;
                this.filteredMachines = this.machinesData.filter(m => m.plant_id == this.form.plant_id);
            },

            resetForm() {
                this.form = {
                    requester_name: '',
                    plant_id: '',
                    machine_id: '',
                    new_machine_name: '',
                    category: '',
                    description: '',
                    target_completion_date: '',
                    photo: null
                };
                this.filteredMachines = [];
                this.searhMachine = '';
                this.isMachineDropdownOpen = false;
            },

            needsMachineSelect() {
                const dropdownCategories = ['Modifikasi Mesin', 'Pembongkaran Mesin', 'Relokasi Mesin', 'Perbaikan',
                    'Pembuatan Alat Baru'
                ];
                return dropdownCategories.includes(this.form.category);
            },

            openDetail(id) {
                this.ticket = this.ticketsData.find(t => t.id == id);
                this.showDetailModal = true;
            },

            toggleTech(id) {
                if (this.editForm.selectedTechs.includes(id)) {
                    this.editForm.selectedTechs = this.editForm.selectedTechs.filter(t => t !== id);
                } else {
                    if (this.editForm.selectedTechs.length >= 5) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Limit',
                            text: 'Max 5 technicians!',
                            confirmButtonColor: '#1E3A5F'
                        });
                        return;
                    }
                    this.editForm.selectedTechs.push(id);
                }
            },

            getTechName(id) {
                let tech = this.techniciansData.find(t => t.id == id);
                return tech ? tech.name : 'Unknown';
            },

            toggleSelectAll() {
                this.selectedTickets = (this.selectedTickets.length === this.pageIds.length) ? [] : [...this.pageIds];
            },

            submitExport() {
                let url = '{{ route('fh.index') }}?export=true';
                if (this.selectedTickets.length > 0) {
                    url += '&selected_ids=' + this.selectedTickets.join(',');
                } else {
                    const params = new URLSearchParams(window.location.search);
                    params.set('export', 'true');
                    url = '{{ route('fh.index') }}?' + params.toString();
                }
                window.location.href = url;
            },

            canApprove(ticket) {
                if (!ticket) return false;

                const userDivisi = (this.currentUserDivisi || '').toString().toLowerCase().trim();
                const userJabatan = (this.currentUserJabatan || '').toString().toLowerCase().trim();
                const userRole = (this.currentUserRole || '').toString().toLowerCase().trim();

                const ticketPlant = (ticket.plant || '').toString().toLowerCase().trim();
                const ticketStatus = (ticket.status || '').toString().toLowerCase().trim();

                // List Role
                const adminRoles = ['fh.admin', 'super.admin', 'super.fh.admin'];

                // LOGIC 1: Waiting Approval
                if (ticketStatus === 'waiting_approval') {

                    // Admin selalu bisa
                    if (adminRoles.includes(userRole)) return true;

                    // Cek Jabatan Boss
                    const isBoss = userJabatan.includes('manager') ||
                        userJabatan.includes('spv') ||
                        userJabatan.includes('supervisor') ||
                        userJabatan.includes('head');


                    // Jika Tiket = "plant d" DAN User = "ccv" -> TOMBOL HILANG
                    if (ticketPlant === 'plant d' && (userDivisi.includes('ccv') || userJabatan.includes('ccv'))) {
                        return false;
                    }
                    // ------------------------------------------


                    const isSamePlant = userDivisi.includes(ticketPlant) || ticketPlant.includes(userDivisi);

                    return isBoss && isSamePlant;
                }

                // LOGIC 2: Verifikasi Facility
                if (ticketStatus === 'waiting_facility_approval') {
                    return adminRoles.includes(userRole);
                }

                return false;
            },
        }
    }
</script>
