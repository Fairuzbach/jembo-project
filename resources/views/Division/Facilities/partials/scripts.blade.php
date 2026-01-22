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
    function promptReject(url) {
        const reason = prompt("Masukkan alasan penolakan:");
        if (reason) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;

            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken; // Fix syntax sebelumnya

            const reasonInput = document.createElement('input');
            reasonInput.type = 'hidden';
            reasonInput.name = 'reason';
            reasonInput.value = reason;

            form.appendChild(csrfInput);
            form.appendChild(reasonInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // 3. ALPINE JS DATA OBJECT (REFACTOR x-data)
    // Kita bungkus semua logic x-data ke dalam fungsi ini
    function facilitiesData() {
        return {
            currentUserRole: '{{ auth()->user()->role }}',
            currentUserDivisi: '{{ auth()->user()->divisi ?? '' }}',
            currentUserJabatan: '{{ auth()->user()->jabatan ?? '' }}',

            showCreateModal: false,
            showEditModal: false,
            showDetailModal: false,
            ticket: null,
            currentUserRole: '{{ auth()->user()->role }}',

            // Forms
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
                selectedTechs: []
            },


            machinesData: @json($machines),
            techniciansData: @json($technicians),
            filteredMachines: [],
            ticketsData: @json($workOrders->items()),
            pageIds: @json($pageIds),
            selectedTickets: [],

            // Time & Date
            currentDate: '',
            currentDateDB: '',
            currentTime: '',
            currentShift: '',

            init() {
                this.updateTime();
                setInterval(() => this.updateTime(), 1000);
            },

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
            },

            needsMachineSelect() {
                const dropdownCategories = [
                    'Modifikasi Mesin', 'Pembongkaran Mesin', 'Relokasi Mesin', 'Perbaikan', 'Pembuatan Alat Baru'
                ];
                return dropdownCategories.includes(this.form.category);
            },

            openDetail(id) {
                // Cari tiket berdasarkan ID
                this.ticket = this.ticketsData.find(t => t.id == id);
                console.log('Tiket Object:', this.ticket);
                this.showDetailModal = true;
            },

            openEditModal(wo) {
                this.ticket = wo;
                this.editForm.id = wo.id;
                this.editForm.status = wo.status;
                this.editForm.start_date = wo.start_date;
                this.editForm.selectedTechs = wo.technicians ? wo.technicians.map(t => t.id) : [];
                this.showEditModal = true;

                setTimeout(() => {
                    document.querySelectorAll('.date-picker-edit').forEach(el => flatpickr(el, {
                        dateFormat: 'Y-m-d'
                    }));
                }, 100);
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

            // Logika Approval Button
            canApprove(ticket) {
                if (!ticket) return false;

                const userDivisi = (this.currentUserDivisi || '').toLowerCase();
                const userJabatan = (this.currentUserJabatan || '').toLowerCase(); // AMBIL JABATAN
                const userRole = (this.currentUserRole || '').toLowerCase();
                const ticketPlant = (ticket.plant || '').toLowerCase();

                // LOGIK 1: Approval Plant (SPV/Manager Lokal)
                if (ticket.status === 'waiting_approval') {

                    // Cek apakah dia Boss (Lihat Jabatan ATAU Role)
                    const isBoss = userJabatan.includes('manager') ||
                        userJabatan.includes('spv') ||
                        userJabatan.includes('supervisor') ||
                        userRole.includes('admin'); // mv.admin

                    const isSamePlant = userDivisi.includes(ticketPlant);
                    const isAdminBypass = ['fh.admin', 'super.admin'].includes(this.currentUserRole);

                    return (isBoss && isSamePlant) || isAdminBypass;
                }
                // LOGIK 2: Approval Facility
                if (ticket.status === 'waiting_facility_approval') {
                    return ['fh.admin', 'fh.spv', 'fh.manager', 'super.admin'].includes(this.currentUserRole);
                }

                return false;
            },
        }
    }
</script>
