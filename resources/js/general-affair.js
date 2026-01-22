import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import Swal from 'sweetalert2';

Chart.register(ChartDataLabels);
window.Chart = Chart;

document.addEventListener('alpine:init', () => {
    // Ambil konfigurasi dari Window (yang dikirim dari Blade)
    const config = window.gaConfig || {};

    Alpine.data('gaData', () => ({
        // --- 1. MODAL STATES ---
        showDetailModal: false,
        showCreateModal: false,
        showConfirmModal: false,
        showEditModal: false,
        showAcceptModal: false,
        showRejectModal: false,
        
        // Filter State (Diambil dari Config)
        showFilters: config.showFilters || false,

        // ID Holder
        acceptId: '',
        rejectId: '',
        show: false,

        // --- 2. DATA SELECTION (BULK ACTION) ---
        // Kita pindahkan logika checkbox dari Blade ke sini
        selected: JSON.parse(localStorage.getItem('ga_selected_ids') || '[]').map(String),
        pageIds: (config.pageIds || []).map(String),

        // --- 3. CURRENT USER DATA ---
        currentUser: {
            nik: config.userNik || '',
            name: config.userName || '',
            department: config.userDept || ''
        },

        // --- 4. FORM DATA ---
        formData: {
            nik: '',
            manual_requester_name: '',
            department: '',
            plant_id: '',
            category: 'RINGAN',
            description: '',
            parameter_permintaan: '',
            status_permintaan: 'OPEN'
        },

        editForm: {
            id: '',
            ticket_num: '',
            status: '',
            pic: '',
            department: '',
            category: '',
            photo_path: '',
            start_date: '',
            target_date: '',
            completion_note: '',
            cancellation_note: ''
        },

        // --- 5. DATA HOLDER ---
        ticket: null,
        isChecking: false,
        locationMap: {
            'Plant A': 'Low Voltage', 'Plant B': 'Medium Voltage', 'Plant C': 'Low Voltage',
            'Plant D': 'Medium Voltage', 'Autowire': 'Low Voltage', 'MC Cable': 'Low Voltage',
            'QC LAB': 'QR', 'QC LV': 'QR', 'QC MV': 'QR', 'QC FO': 'QR',
            'RM 1': 'SC', 'RM 2': 'SC', 'RM 3': 'SC', 'RM 5': 'SC', 'RM Office': 'SC',
            'Workshop Electric': 'MT', 'Konstruksi': 'FH', 'Plant E': 'FO',
            'Plant Tools': 'PE', 'Gudang Jadi': 'SS', 'GA': 'GA', 'FA': 'FA',
            'IT': 'IT', 'HC': 'HC', 'Sales': 'Sales', 'Marketing': 'Marketing',
        },

        // --- METHODS ---
        init() {
            this.resetToMe();
            // console.log('Alpine gaData Initialized');
        },

        // Toggle Select All (Checkbox Header)
        toggleSelectAll() {
            // Cek apakah semua ID di halaman ini sudah terpilih
            const allSelected = this.pageIds.length > 0 && this.pageIds.every(id => this.selected.includes(id));
            
            if (allSelected) {
                // Uncheck semua yang ada di halaman ini
                this.selected = this.selected.filter(id => !this.pageIds.includes(id));
            } else {
                // Check semua di halaman ini (hindari duplikat)
                this.pageIds.forEach(id => {
                    if (!this.selected.includes(id)) {
                        this.selected.push(id);
                    }
                });
            }
            // Simpan ke localStorage agar persisten saat pindah page
            localStorage.setItem('ga_selected_ids', JSON.stringify(this.selected));
        },

        clearSelection() {
            this.selected = [];
            localStorage.setItem('ga_selected_ids', JSON.stringify(this.selected));
        },

        openDetail(encodedData) {
            if (!encodedData) return;
            try {
                const ticketData = JSON.parse(atob(encodedData));
                this.ticket = ticketData;
                this.showDetailModal = true;
            } catch (error) {
                console.error('Error parsing ticket detail:', error);
                Swal.fire('Error', 'Gagal memuat detail data.', 'error');
            }
        },
        
      openEditModal(data) {
    console.log("CEK DATA DARI BACKEND:", data);

    this.editForm.id = data.id;
    this.editForm.ticket_num = data.ticket_num;
    this.editForm.status = data.status;
    this.editForm.pic = data.processed_by_name || this.currentUser.name;
    this.editForm.department = data.department || 'GA';
    this.editForm.category = data.category || 'MEDIUM';

    // --- 1. LOGIKA START DATE (DENGAN JAM) ---
    let fullStartDate = '';
    if (data.actual_start_date) {
        let d = new Date(data.actual_start_date);
        if (!isNaN(d.getTime())) {
            // Koreksi Timezone WIB
            d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
            // Format YYYY-MM-DD HH:mm (Ganti T jadi spasi)
            fullStartDate = d.toISOString().slice(0, 16).replace('T', ' ');
        }
    }
    // Masukkan ke model Alpine
    this.editForm.start_date = fullStartDate;


    // --- 2. LOGIKA SELESAI AKTUAL (DENGAN JAM) ---
    const formatToDateTimeLocal = (dateString) => {
        if (!dateString) return '';
        let date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
        return date.toISOString().slice(0, 16); // Format YYYY-MM-DDTHH:mm
    };

    // PERBAIKAN TYPO: actual_end_date (bukan data)
    this.editForm.actual_end_date = formatToDateTimeLocal(data.actual_end_date); 
    
    // Reset field lain
    this.editForm.target_date = data.target_completion_date || '';
    this.editForm.completion_note = data.completion_note || '';
    this.editForm.cancellation_note = data.cancellation_note || '';

    this.showEditModal = true;
    
    // --- 3. FLATPICKR START DATE (AKTIFKAN WAKTU) ---
    setTimeout(() => {
        const startDateInput = document.getElementById('modal_start_date');
        if (startDateInput && typeof flatpickr !== 'undefined') {
            if (startDateInput._flatpickr) startDateInput._flatpickr.destroy();

            flatpickr(startDateInput, { 
                enableTime: true,        // <--- PENTING: Agar bisa pilih jam
                dateFormat: "Y-m-d H:i", // <--- Format kirim ke server (YYYY-MM-DD HH:mm)
                time_24hr: true,         // Format 24 jam
                defaultDate: fullStartDate, // <--- Gunakan variabel yang ada jamnya
                allowInput: true,
                onChange: (selectedDates, dateStr) => {
                    this.editForm.start_date = dateStr;
                }
            });
        }
    }, 50);
},

        resetToMe() {
            this.formData.nik = this.currentUser.nik;
            this.formData.manual_requester_name = this.currentUser.name;
            this.formData.department = this.currentUser.department;
        },

        async checkNik() {
            if (!this.formData.nik) { this.resetToMe(); return; }
            if (this.formData.nik === this.currentUser.nik) { this.resetToMe(); return; }

            this.isChecking = true;
            try {
                const response = await fetch(`/ga/check-employee?nik=${this.formData.nik}`);
                const result = await response.json();

                if (result.status === 'success') {
                    this.formData.manual_requester_name = result.data.name;
                    this.formData.department = result.data.department;
                    Swal.fire({
                        toast: true, position: 'top-end', icon: 'success',
                        title: 'Data Ditemukan', showConfirmButton: false, timer: 2000
                    });
                } else {
                    this.formData.manual_requester_name = '';
                    this.formData.department = '';
                    Swal.fire({
                        icon: 'error', title: 'NIK Tidak Ditemukan!',
                        text: `NIK ${this.formData.nik} tidak terdaftar.`,
                        confirmButtonColor: '#0f172a'
                    });
                }
            } catch (e) { console.error(e); } 
            finally { this.isChecking = false; }
        },

        updateDepartment() {
            let select = document.getElementById('plantSelect');
            if (select) {
                let selectedOption = select.options[select.selectedIndex];
                let selectedText = selectedOption.text.trim();
                if (this.locationMap[selectedText]) {
                    this.formData.department = this.locationMap[selectedText];
                }
            }
        },

        openAcceptModal(id) { 
            this.acceptId = id; 
            this.showAcceptModal = true; 
        },

        openRejectModal(id) { 
            this.rejectId = id; 
            this.showRejectModal = true; 
        }
    }));
});

// Flatpickr Range Init
document.addEventListener('DOMContentLoaded', function() {
    const pickerInput = document.getElementById("date_range_picker");
    const config = window.gaConfig || {};

    if (pickerInput && typeof flatpickr !== 'undefined') {
        flatpickr(pickerInput, {
            mode: "range", dateFormat: "Y-m-d", altInput: true, altFormat: "j F Y",
            defaultDate: [config.startDate, config.endDate],
            onChange: function(selectedDates) {
                if (selectedDates.length === 2) {
                    const start = selectedDates[0].toISOString().split('T')[0];
                    const end = selectedDates[1].toISOString().split('T')[0];
                    document.getElementById('start_date').value = start;
                    document.getElementById('end_date').value = end;
                }
            }
        });
    }
});

// =========================================================================
// GLOBAL FUNCTIONS (WINDOW)
// =========================================================================

// 1. Handle Session Flash
document.addEventListener('DOMContentLoaded', () => {
    const config = window.gaConfig || {};
    const flash = config.flash || {};

    if (flash.success) Swal.fire({ title: 'Berhasil!', text: flash.success, icon: 'success', confirmButtonColor: '#dc2626' });
    if (flash.error) Swal.fire({ title: 'Gagal!', text: flash.error, icon: 'error', confirmButtonColor: '#d33' });
});

// 2. Konfirmasi Submit
window.confirmSubmit = function(event, title, text, icon = 'warning', confirmColor = '#3085d6') {
    event.preventDefault();
    const formEl = event.target.closest('form');
    if(!formEl){
        console.error('Form tidak ditemukan!');
        return;
    }
    Swal.fire({
        title: title, text: text, icon: icon,
        showCancelButton: true, confirmButtonColor: confirmColor, cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Lanjutkan!', cancelButtonText: 'Batal', reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) formEl.submit();
    });
};

// 3. Reject Reason
window.confirmRejectWithReason = function(event) {
    event.preventDefault();
    const formEl = event.target.closest('form');
    if(!formEl){
        console.error('Form tidak ditemukan!');
        return;
    }
    Swal.fire({
        title: 'Tolak Tiket?', input: 'textarea',
        inputLabel: 'Alasan Penolakan (Opsional)', inputPlaceholder: 'Tulis alasan...',
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b', confirmButtonText: 'Ya, Tolak', cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            let reasonInput = formEl.querySelector('input[name="reason"]');
            if(!reasonInput){
                reasonInput = document.createElement('input');
                reasonInput.type = 'hidden';
                reasonInput.name = 'reason';
                formEl.appendChild(reasonInput);
            }
            reasonInput.value = result.value ? result.value : '-';
            formEl.submit();
        }
    });
};

// 4. Modal Proses GA
window.openProcessModal = function(id, ticketNum, requesterName) {
    let baseUrl = "/ga/process/:id";
    let url = baseUrl.replace(':id', id);
    const form = document.getElementById('form-process-ga');
    if(form) form.action = url;

    const ticketSpan = document.getElementById('modal-ticket-num');
    if(ticketSpan) ticketSpan.innerText = ticketNum;

    const reqSpan = document.getElementById('modal-requester-name');
    if(reqSpan) reqSpan.innerText = requesterName;

    const radioApprove = document.querySelector('input[name="action"][value="approve"]');
    if(radioApprove) radioApprove.checked = true;
    
    window.toggleReasonGa(false);
    const modal = document.getElementById('modal-process-ga');
    if(modal) modal.classList.remove('hidden');
};

window.closeProcessModal = function() {
    const modal = document.getElementById('modal-process-ga');
    if(modal) modal.classList.add('hidden');
};

window.toggleReasonGa = function(isReject) {
    const container = document.getElementById('reason-container-ga');
    const input = document.getElementById('reason-input-ga');
    if (isReject) {
        container.classList.remove('hidden');
        input.setAttribute('required', 'required');
        input.focus();
    } else {
        container.classList.add('hidden');
        input.removeAttribute('required');
        input.value = '';
    }
};

// 5. Konfirmasi Admin Teknis
window.confirmTechnicalAction = function(id, type) {
    let titleText = type === 'approve' ? 'Setujui Tiket?' : 'Tolak Tiket?';
    let bodyText = type === 'approve' ? 'Tiket akan diteruskan ke tim GA.' : 'Tiket akan dikembalikan ke user.';
    let iconType = type === 'approve' ? 'question' : 'warning';
    let confirmColor = type === 'approve' ? '#059669' : '#e11d48';

    Swal.fire({
        title: titleText, text: bodyText, icon: iconType,
        showCancelButton: true, confirmButtonColor: confirmColor, cancelButtonColor: '#64748b',
        confirmButtonText: type === 'approve' ? 'Ya, Approve!' : 'Ya, Tolak!', cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            const inputAction = document.getElementById('input-action-' + id);
            const formTech = document.getElementById('form-tech-' + id);
            if(inputAction) inputAction.value = type;
            if(formTech) formTech.submit();
        }
    });
};