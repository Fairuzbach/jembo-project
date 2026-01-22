# Dokumentasi: Implementasi Waiting Approval SPV & GA

## Overview

Sistem approval work order General Affair sekarang memiliki 2 tahap approval:

1. **Waiting Approval SPV** - Menunggu persetujuan dari Supervisor/Manager departemen
2. **Waiting Approval GA** - Menunggu persetujuan dari General Affair Admin

## Status Flow

```
User membuat WO
    ↓
Jika user adalah GA Admin → Status: Pending (langsung bisa dikerjakan)
Jika user biasa → Status: Waiting Approval SPV
    ↓
Supervisor/Manager approve → Status: Waiting Approval GA
    ↓
GA Admin approve → Status: Pending (siap dikerjakan)
    ↓
    Dikerjakan, Selesai, atau Ditolak
```

## File yang Diubah

### 1. **app/Services/GeneralAffair/WorkOrderService.php**

- **createWorkOrder()**: Mengubah status awal dari `waiting_approval` menjadi `waiting_approval_spv` untuk user biasa
- **processTicket()**: Diperbarui untuk menangani 2 tahap approval:
    - Jika status `waiting_approval_spv`: Supervisor bisa approve ke `waiting_approval_ga`, atau GA Admin bisa skip langsung ke `pending`
    - Jika status `waiting_approval_ga`: Hanya GA Admin yang bisa approve ke `pending`
- **getIndexStats()**: Menghitung stats untuk setiap status approval secara terpisah:
    - `countWaitingApprovalSpv` - Menunggu SPV
    - `countWaitingApprovalGA` - Menunggu GA
    - `countWaitingApproval` - Total kedua-duanya
- **applyAccessControl()**: Supervisor bisa melihat tiket dengan status `waiting_approval_spv` dari divisi mereka
- **sendNotifications()**: Mengirim notifikasi ke Supervisor untuk tahap 1 approval
- **getApproversForDeptLevel()**: Fungsi baru untuk mencari approver berdasarkan level (supervisor/admin)

### 2. **app/Models/GeneralAffair/WorkOrderGeneralAffair.php**

- Menambahkan ke `$fillable`:
    - `approved_spv_by` - ID supervisor yang approve
    - `approved_spv_at` - Waktu supervisor approve

### 3. **app/Services/GeneralAffair/DashboardService.php**

- Update `getDashboardData()` untuk membedakan antara `pending` dan approval statuses
- Menambahkan `countWaitingApprovalSpv` dan `countWaitingApprovalGA` ke return data

### 4. **resources/views/Components/index/stats-card.blade.php**

- Mengubah props untuk menerima `countWaitingApprovalSpv` dan `countWaitingApprovalGA`
- Memperbarui card array untuk menampilkan:
    - "Pending" (bukan "Waiting Approval" lagi)
    - "Waiting Approval SPV" - Hanya untuk GA Admin
    - "Waiting Approval GA" - Hanya untuk GA Admin
- Grid layout disesuaikan: 7 cards untuk admin, 5 cards untuk user biasa

### 5. **resources/views/Division/GeneralAffair/GeneralAffair.blade.php**

- Update stats-card component call untuk menyertakan:
    - `:countWaitingApprovalSpv="$countWaitingApprovalSpv ?? 0"`
    - `:countWaitingApprovalGA="$countWaitingApprovalGA ?? 0"`
- Update filter options untuk termasuk status baru:
    - `waiting_approval_spv`
    - `waiting_approval_ga`

### 6. **resources/views/Components/index/table-data.blade.php**

- Update kondisi untuk menampilkan action buttons:
    - Dari `in_array($item->status, ['waiting_spv', 'waiting_approval'])`
    - Menjadi `in_array($item->status, ['waiting_approval_spv', 'waiting_approval_ga'])`

### 7. **database/migrations/2026_01_20_add_approval_tracking_columns.php**

- Migration baru untuk menambahkan kolom:
    - `approved_spv_by` (unsignedBigInteger, nullable) - ID supervisor
    - `approved_spv_at` (dateTime, nullable) - Waktu approval

## User Roles dan Permissions

### 1. **User Biasa**

- Membuat WO → Status: `waiting_approval_spv`
- Melihat hanya WO milik divisi mereka dan WO yang pending SPV approval
- Tidak bisa approve

### 2. **Supervisor/Manager**

- Melihat WO yang menunggu approval dari divisi mereka (`waiting_approval_spv`)
- Approve tahap 1 → Status berubah menjadi `waiting_approval_ga`
- Notifikasi dikirim ke GA Admin

### 3. **GA Admin (ga.admin)**

- Melihat semua WO di semua status
- Bisa approve di tahap 1: langsung skip ke `pending` (melewati SPV)
- Bisa approve di tahap 2: dari `waiting_approval_ga` ke `pending`
- Melihat 2 card statistik tambahan: "Waiting Approval SPV" dan "Waiting Approval GA"

## Statuses yang Ada

1. `waiting_approval_spv` - Menunggu SPV/Manager approval (tahap 1)
2. `waiting_approval_ga` - Menunggu GA Admin approval (tahap 2)
3. `pending` - Sudah diapprove, siap dikerjakan
4. `in_progress` - Sedang dikerjakan
5. `completed` - Selesai
6. `rejected` - Ditolak
7. `cancelled` - Dibatalkan

## API & Route Changes

No changes required to routes - sistem masih menggunakan:

- `POST /ga/{id}/process` untuk approve/reject
- `POST /ga/{id}/approve-technical` untuk approval

## Testing Checklist

- [ ] User biasa membuat WO → Status: `waiting_approval_spv`
- [ ] SPV melihat WO yang pending dari divisi mereka
- [ ] SPV approve WO → Status: `waiting_approval_ga`
- [ ] GA Admin melihat WO yang `waiting_approval_ga`
- [ ] GA Admin approve → Status: `pending`
- [ ] GA Admin bisa langsung approve dari `waiting_approval_spv` ke `pending`
- [ ] Stats card menampilkan count yang benar
- [ ] Filter dapat memfilter berdasarkan status baru
- [ ] History mencatat setiap approval action

## Database Schema (Baru)

```sql
ALTER TABLE work_order_general_affairs ADD COLUMN approved_spv_by BIGINT UNSIGNED NULL COMMENT 'ID supervisor yang approve';
ALTER TABLE work_order_general_affairs ADD COLUMN approved_spv_at DATETIME NULL COMMENT 'Waktu supervisor approve';
```

Kolom yang sudah ada dan digunakan:

- `approved_ga_by` - ID GA Admin yang approve
- `approved_ga_at` - Waktu GA Admin approve
- `processed_by` - ID user yang terakhir process ticket
- `processed_by_name` - Nama user yang process
- `processed_at` - Waktu processing

## Notes

- Email notification sudah terintegrasi untuk mengirim ke approver yang tepat
- Access control sudah update agar SPV hanya bisa lihat WO dari divisi mereka yang status `waiting_approval_spv`
- Semua perubahan backward compatible - WO lama dengan status `waiting_ga_approval` masih bisa diproses
