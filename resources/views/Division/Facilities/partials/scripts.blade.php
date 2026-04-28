{{-- resources/views/Division/Facilities/partials/scripts.blade.php --}}

<script>
    // =========================================================================
    // 1. SWEETALERT NOTIFICATIONS
    // Menampilkan notifikasi sukses/error dari session Laravel
    // =========================================================================
    @if (session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: '{{ session('success') }}',
            confirmButtonColor: '#1E3A5F',
            timer: 2000,
            showConfirmButton: false
        });
    @endif

    @if (session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '{{ session('error') }}',
            confirmButtonColor: '#d33',
        });
    @endif

    // =========================================================================
    // 2. ALPINE JS DATA OBJECT
    // Semua state dan logic UI dibungkus dalam fungsi ini agar bisa
    // digunakan sebagai x-data="facilitiesData()" di blade utama
    // =========================================================================
    function facilitiesData() {
        return {

            // ---------------------------------------------------------------
            // DATA USER (diambil dari session Laravel, dipakai untuk canApprove)
            // ---------------------------------------------------------------
            currentUserRole: @json(auth()->user()->role),
            currentUserDivisi: @json(auth()->user()->divisi ?? ''),
            currentUserJabatan: @json(auth()->user()->jabatan ?? ''),
            currentUserLevel: @json(auth()->user()->job_level ?? ''),

            // ---------------------------------------------------------------
            // STATE MODAL
            // ---------------------------------------------------------------
            showCreateModal: false,
            showEditModal: false,
            showDetailModal: false,
            ticket: null,

            // ---------------------------------------------------------------
            // STATE MESIN (untuk dropdown di modal create)
            // ---------------------------------------------------------------
            isMachineDropdownOpen: false,
            searchMachine: '',

            // ---------------------------------------------------------------
            // FORM DATA — Modal Create
            // ---------------------------------------------------------------
            form: {
                requester_name: '',
                plant_id: '',
                machine_id: '',
                new_machine_name: '',
                category: '',
                description: '',
                target_completion_date: '',
                photo: null,
            },

            // ---------------------------------------------------------------
            // FORM DATA — Modal Edit/Update Status
            // ---------------------------------------------------------------
            editForm: {
                id: '',
                status: '',
                category: '',
                start_date: '',
                actual_completion_date: '',
                completion_note: '',
                selectedTechs: [],
            },

            // ---------------------------------------------------------------
            // DATA MASTER (dari Controller via json)
            // ---------------------------------------------------------------
            machinesData: @json($machines),
            techniciansData: @json($technicians),
            filteredMachines: [],
            ticketsData: @json($workOrders->items()),
            pageIds: @json($pageIds),
            selectedTickets: [],

            // ---------------------------------------------------------------
            // WAKTU & SHIFT
            // ---------------------------------------------------------------
            currentDate: '',
            currentDateDB: '',
            currentTime: '',
            currentShift: '',

            // ---------------------------------------------------------------
            // COMPUTED: Filter mesin berdasarkan search input
            // ---------------------------------------------------------------
            get searchedMachines() {
                if (this.searchMachine.trim() === '') return this.filteredMachines;
                return this.filteredMachines.filter(m =>
                    m.name.toLowerCase().includes(this.searchMachine.toLowerCase())
                );
            },

            // ---------------------------------------------------------------
            // COMPUTED: Nama mesin yang dipilih di dropdown
            // ---------------------------------------------------------------
            get selectedMachineName() {
                const machine = this.filteredMachines.find(m => m.id == this.form.machine_id);
                return machine ? machine.name : '-- Pilih Mesin --';
            },

            // =================================================================
            // INIT
            // =================================================================
            init() {
                this.updateTime();
                setInterval(() => this.updateTime(), 1000);

                /**
                 * AUTO-OPEN DETAIL MODAL VIA URL PARAM
                 * -------------------------------------
                 * Jika URL mengandung ?open={id}, cari tiket di ticketsData
                 * dan buka modal detail secara otomatis.
                 *
                 * Digunakan untuk link di notifikasi WhatsApp:
                 * Contoh: https://domain.com/fh?open=123
                 *
                 * Catatan: delay 300ms agar Alpine selesai mount
                 * sebelum event di-dispatch ke modal-detail component.
                 */
                const urlParams = new URLSearchParams(window.location.search);
                const openId = urlParams.get('open');
                if (openId) {
                    const ticket = this.ticketsData.find(t => t.id == openId);
                    if (ticket) {
                        setTimeout(() => {
                            this.$dispatch('open-detail-modal', ticket);
                        }, 300);
                    }
                }

                /**
                 * WATCHER: Auto-fill tanggal saat status berubah
                 * -----------------------------------------------
                 * - in_progress → isi start_date jika masih kosong
                 * - completed   → isi start_date & actual_completion_date jika kosong
                 */
                this.$watch('editForm.status', (newStatus) => {
                    if (newStatus === 'in_progress' || newStatus === 'completed') {
                        if (!this.editForm.start_date) {
                            this.editForm.start_date = this.getNowISO();
                        }
                    }
                    if (newStatus === 'completed') {
                        if (!this.editForm.actual_completion_date) {
                            this.editForm.actual_completion_date = this.getNowISO();
                        }
                    }
                });
            },

            // =================================================================
            // HELPER: Waktu sekarang dalam format HTML5 datetime-local
            // Format: YYYY-MM-DDTHH:MM (dipakai untuk input type="datetime-local")
            // =================================================================
            getNowISO() {
                let now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                return now.toISOString().slice(0, 16);
            },

            // =================================================================
            // OPEN MODAL EDIT
            // Load data WO ke editForm sebelum modal dibuka
            // =================================================================
            openEditModal(wo) {
                this.ticket = wo;
                this.editForm.id = wo.id;
                this.editForm.status = wo.status;
                this.editForm.selectedTechs = wo.technicians ?
                    wo.technicians.map(t => t.id) : [];
                this.editForm.category = wo.category || '';
                // Load start_date — format DB "2023-01-01 10:00:00" → HTML "2023-01-01T10:00"
                this.editForm.start_date = wo.start_date ?
                    wo.start_date.replace(' ', 'T').substring(0, 16) :
                    '';

                // Load actual_completion_date
                this.editForm.actual_completion_date = wo.actual_completion_date ?
                    wo.actual_completion_date.replace(' ', 'T').substring(0, 16) :
                    '';

                this.editForm.completion_note = wo.completion_note || '';
                this.showEditModal = true;
            },

            // =================================================================
            // UPDATE WAKTU & SHIFT
            // Dipanggil setiap detik via setInterval di init()
            // =================================================================
            updateTime() {
                const now = new Date();

                this.currentDate = now.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric'
                });

                const y = now.getFullYear();
                const m = String(now.getMonth() + 1).padStart(2, '0');
                const d = String(now.getDate()).padStart(2, '0');
                this.currentDateDB = `${y}-${m}-${d}`;

                this.currentTime = now.toLocaleTimeString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                });

                const hour = now.getHours();
                this.currentShift = hour >= 7 && hour < 15 ?
                    '1 (Pagi)' :
                    hour >= 15 && hour < 23 ?
                    '2 (Sore)' :
                    '3 (Malam)';
            },

            // =================================================================
            // FILTER MESIN berdasarkan plant yang dipilih di form create
            // =================================================================
            filterMachines() {
                this.form.machine_id = '';
                this.searchMachine = '';
                this.isMachineDropdownOpen = false;
                this.filteredMachines = this.machinesData.filter(
                    m => m.plant_id == this.form.plant_id
                );
            },

            // =================================================================
            // RESET FORM CREATE ke kondisi awal
            // =================================================================
            resetForm() {
                this.form = {
                    requester_name: '',
                    plant_id: '',
                    machine_id: '',
                    new_machine_name: '',
                    category: '',
                    description: '',
                    target_completion_date: '',
                    photo: null,
                };
                this.filteredMachines = [];
                this.searchMachine = '';
                this.isMachineDropdownOpen = false;
            },

            // =================================================================
            // CEK apakah kategori yang dipilih membutuhkan dropdown mesin
            // =================================================================
            needsMachineSelect() {
                const dropdownCategories = [
                    'Modifikasi Mesin',
                    'Pembongkaran Mesin',
                    'Relokasi Mesin',
                    'Perbaikan',
                    'Pembuatan Alat Baru',
                ];
                return dropdownCategories.includes(this.form.category);
            },

            // =================================================================
            // OPEN DETAIL MODAL by ticket ID (dipanggil dari tombol detail)
            // =================================================================
            openDetail(id) {
                this.ticket = this.ticketsData.find(t => t.id == id);
                this.showDetailModal = true;
            },

            // =================================================================
            // TOGGLE TEKNISI di modal edit (max 5 teknisi)
            // =================================================================
            toggleTech(id) {
                if (this.editForm.selectedTechs.includes(id)) {
                    this.editForm.selectedTechs = this.editForm.selectedTechs.filter(t => t !== id);
                } else {
                    if (this.editForm.selectedTechs.length >= 5) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Limit',
                            text: 'Maksimal 5 teknisi per tiket.',
                            confirmButtonColor: '#1E3A5F',
                        });
                        return;
                    }
                    this.editForm.selectedTechs.push(id);
                }
            },

            // =================================================================
            // GET nama teknisi berdasarkan ID
            // =================================================================
            getTechName(id) {
                const tech = this.techniciansData.find(t => t.id == id);
                return tech ? tech.name : 'Unknown';
            },

            // =================================================================
            // SELECT ALL tiket di halaman ini (untuk bulk export)
            // =================================================================
            toggleSelectAll() {
                this.selectedTickets = (this.selectedTickets.length === this.pageIds.length) ? [] : [...this.pageIds];
            },

            // =================================================================
            // EXPORT tiket ke Excel
            // Jika ada tiket yang dipilih → export selected
            // Jika tidak ada → export semua berdasarkan filter aktif
            // =================================================================
            submitExport() {
                if (this.selectedTickets.length > 0) {
                    window.location.href = '{{ route('fh.index') }}?export=true&selected_ids=' +
                        this.selectedTickets.join(',');
                } else {
                    const params = new URLSearchParams(window.location.search);
                    params.set('export', 'true');
                    window.location.href = '{{ route('fh.index') }}?' + params.toString();
                }
            },

            // =================================================================
            // CAN APPROVE (JS version)
            // ---------------------------------------------------------------
            // Digunakan di blade untuk mengontrol tampilan tombol approve.
            // Harus KONSISTEN dengan canApproveBy() di Model PHP.
            //
            // Approval Matrix (sama dengan getFacilityMatrix() di Service):
            // - PLANT A, B, C, D, E    → divisi harus sama persis dengan plant
            // - PLANT A - AUTOWIRE     → divisi PLANT A - AUTOWIRE atau PLANT A
            // - PLANT D - CCV          → divisi PLANT D - CCV atau PLANT D
            // - PP                     → divisi PRODUCTION PLANNING
            // - SS                     → divisi SALES SUPPORT
            // - MT                     → divisi MAINTENANCE
            // - PROCUREMENT            → divisi PROCUREMENT
            // =================================================================
            canApprove(ticket) {
                if (!ticket) return false;

                const userDivisi = (this.currentUserDivisi || '').toString().toUpperCase().trim();
                const userLevel = (this.currentUserLevel || '').toString().toUpperCase().trim();
                const userRole = (this.currentUserRole || '').toString().toLowerCase().trim();
                const ticketPlant = (ticket.plant || '').toString().toUpperCase().trim();
                const ticketStatus = (ticket.status || '').toString().toLowerCase().trim();

                const adminRoles = ['fh.admin', 'super.admin', 'super.fh.admin'];

                // Admin selalu bisa approve
                if (adminRoles.includes(userRole) || userDivisi === 'FACILITY') return true;

                // Harus minimal SPV / Manager / Head / Director
                const isSpv = userLevel.includes('SUPERVISOR') || userLevel.includes('SPV');
                const isMgr = userLevel.includes('MANAGER') || userLevel.includes('HEAD') ||
                    userLevel.includes('MGR') || userLevel.includes('DIRECTOR');

                if (!isSpv && !isMgr) return false;

                /**
                 * LOGIC 1: waiting_approval
                 * → Perlu approval SPV/Manager divisi yang sesuai
                 */
                if (ticketStatus === 'waiting_approval') {

                    // Approval matrix — konsisten dengan getFacilityMatrix() PHP
                    const matrix = {
                        'PLANT A': {
                            spv: ['PLANT A'],
                            mgr: ['PLANT A']
                        },
                        'PLANT A - AUTOWIRE': {
                            spv: ['PLANT A - AUTOWIRE'],
                            mgr: ['PLANT A - AUTOWIRE', 'PLANT A']
                        },
                        'PLANT B': {
                            spv: ['PLANT B'],
                            mgr: ['PLANT B']
                        },
                        'PLANT C': {
                            spv: ['PLANT C'],
                            mgr: ['PLANT C']
                        },
                        'PLANT D': {
                            spv: ['PLANT D'],
                            mgr: ['PLANT D']
                        },
                        'PLANT D - CCV': {
                            spv: ['PLANT D - CCV'],
                            mgr: ['PLANT D - CCV', 'PLANT D']
                        },
                        'PLANT E': {
                            spv: ['PLANT E'],
                            mgr: ['PLANT E']
                        },
                        'PP': {
                            spv: ['PRODUCTION PLANNING'],
                            mgr: ['PRODUCTION PLANNING']
                        },
                        'SS': {
                            spv: ['SALES SUPPORT'],
                            mgr: ['SALES SUPPORT']
                        },
                        'MT': {
                            spv: ['MAINTENANCE'],
                            mgr: ['MAINTENANCE']
                        },
                        'PROCUREMENT': {
                            spv: ['PROCUREMENT'],
                            mgr: ['PROCUREMENT']
                        },
                    };

                    const config = matrix[ticketPlant];

                    if (config) {
                        // Cek SPV
                        if (isSpv) {
                            for (const keyword of config.spv) {
                                if (userDivisi.includes(keyword.toUpperCase())) return true;
                            }
                        }
                        // Cek Manager
                        if (isMgr) {
                            for (const keyword of config.mgr) {
                                if (userDivisi.includes(keyword.toUpperCase())) return true;
                            }
                        }
                        // Ada di matrix tapi tidak match → tolak
                        return false;
                    }

                    // Fallback: exact match
                    return userDivisi === ticketPlant;
                }

                /**
                 * LOGIC 2: waiting_facility_approval
                 * → Hanya admin facility yang bisa verifikasi di tahap ini
                 */
                if (ticketStatus === 'waiting_facility_approval') {
                    return adminRoles.includes(userRole) || userDivisi === 'FACILITY';
                }

                return false;
            },
        }
    }
</script>
