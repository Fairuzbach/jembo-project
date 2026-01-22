# Email Queue Implementation Guide

## Deskripsi

Sistem notifikasi email Work Order General Affair sekarang menggunakan Laravel Queue dengan database driver. Ini memastikan:

- Email tidak langsung terkirim (synchronous) tapi di-queue di database
- Jika email gagal, sistem akan retry hingga 3 kali dengan backoff (1 min, 5 min, 15 min)
- Jika semua retry gagal, job masuk ke tabel `failed_jobs`
- Aplikasi tetap responsif meskipun SMTP error

## Konfigurasi yang Sudah Dilakukan

### 1. Job Class

**File:** `app/Jobs/SendWorkOrderNotification.php`

- Handles email sending
- Retry logic: 3 kali
- Backoff: 60 detik, 300 detik, 900 detik
- Queue: 'emails'
- Failed jobs masuk ke tabel `failed_jobs`

### 2. Service Layer Update

**File:** `app/Services/GeneralAffair/WorkOrderService.php`

- Method `safeMail()` diupdate untuk dispatch ke queue
- Fallback ke direct send jika queue dispatch gagal
- Logging untuk tracking

### 3. Database Configuration

**File:** `config/queue.php`

- Driver: database
- Table: jobs
- Connection: database (menggunakan DB_CONNECTION yang sama)

### 4. Environment Variables

**File:** `.env`

```
QUEUE_CONNECTION=database
```

## Setup Steps

### Step 1: Run Migrations

```bash
php artisan migrate
```

Ini akan membuat tabel:

- `jobs` - menyimpan job queue yang pending
- `failed_jobs` - menyimpan job yang gagal

### Step 2: Run Queue Worker

Buka terminal dan jalankan:

```bash
php artisan queue:work --queue=emails --timeout=60
```

**Penjelasan:**

- `--queue=emails` - hanya process job dari queue 'emails'
- `--timeout=60` - job timeout setelah 60 detik
- Worker akan terus berjalan dan menunggu job baru

Untuk background process (production), gunakan Supervisor atau nohup:

```bash
nohup php artisan queue:work --queue=emails --timeout=60 > storage/logs/queue.log 2>&1 &
```

### Step 3: Monitor Queued Jobs

```bash
# Lihat job yang sedang pending
php artisan queue:failed

# Lihat status queue
php artisan queue:work --queue=emails --tries=3 --timeout=60
```

## Testing

### 1. Create Work Order

Buat work order baru melalui aplikasi:

- Regular user (bukan GA Admin) → status: `waiting_approval_spv`
- Email akan di-queue ke tabel `jobs`

### 2. Check Queued Jobs

```bash
SELECT * FROM jobs;
```

Akan melihat job dengan payload berisi email info.

### 3. Process Queue

Jalankan worker:

```bash
php artisan queue:work --queue=emails
```

Job akan diproses dan dihapus dari tabel `jobs`.

### 4. Check Sent Emails

Jika SMTP configured:

- Email akan terkirim ke recipient
- Log akan tercatat di `storage/logs/`

Jika SMTP error:

- Job akan retry sesuai backoff
- Setelah 3 kali gagal, masuk ke `failed_jobs`

## Troubleshooting

### Problem: "jobs" table doesn't exist

**Solution:**

```bash
php artisan migrate
```

### Problem: Queue worker tidak berjalan

**Solution:**

```bash
php artisan queue:restart
php artisan queue:work --queue=emails
```

### Problem: Job masuk ke failed_jobs

**Cek log:**

```bash
tail -f storage/logs/laravel.log
```

**Retry failed jobs:**

```bash
php artisan queue:retry all
```

### Problem: SMTP error tapi mau test

**Solution:** Gunakan log driver untuk email di `.env`:

```
MAIL_DRIVER=log
```

Email akan tercatat di `storage/logs/laravel.log` tanpa perlu SMTP.

## Database Tables

### `jobs` table

```
id | queue | payload | attempts | reserved_at | available_at | created_at | updated_at
```

### `failed_jobs` table

```
id | uuid | connection | queue | payload | exception | failed_at
```

## Monitoring Commands

```bash
# Proses queue dengan verbose output
php artisan queue:work --queue=emails -vvv

# Lihat failed jobs
php artisan queue:failed

# Retry specific failed job
php artisan queue:retry {id}

# Retry all failed jobs
php artisan queue:retry all

# Forget failed job
php artisan queue:forget {id}

# Flush all failed jobs
php artisan queue:flush

# Restart queue (kill all workers)
php artisan queue:restart

# Monitor queue status
php artisan queue:monitor emails:100
```

## Production Considerations

### 1. Keep Queue Worker Running

Use Supervisor (Linux/Mac) atau Task Scheduler (Windows).

### 2. Setup Email Retry

Job akan retry otomatis, tapi pastikan:

- SMTP credentials benar
- Email quota tidak habis
- Network connection stabil

### 3. Monitor Failed Jobs

Set up cron job untuk check `failed_jobs`:

```php
// Dalam scheduler
\Illuminate\Support\Facades\Artisan::queue('queue:failed');
```

### 4. Log Rotation

Pastikan `storage/logs/` tidak penuh:

```bash
# Clear old logs
php artisan log:clear
```

## Email Flow Diagram

```
User Create WO
    ↓
WorkOrderService->createWorkOrder()
    ↓
safeMail() -> SendWorkOrderNotification Job
    ↓
jobs table (pending)
    ↓
queue:work picks up job
    ↓
Mail::to()->send()
    ↓
Success: remove from jobs table
    ↓
Fail: retry (1 min later)
    ↓
Retry 2: fail → retry (5 min later)
    ↓
Retry 3: fail → move to failed_jobs table
    ↓
Manual retry with php artisan queue:retry
```

## Log Example

```
[2025-01-20 10:30:45] local.INFO: Email notification queued for: manager@company.com
[2025-01-20 10:30:48] local.INFO: Email sent via queue: manager@company.com
[2025-01-20 10:30:49] local.INFO: Email notification queued for: approver@company.com
```
