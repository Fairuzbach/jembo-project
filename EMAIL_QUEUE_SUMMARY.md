# Email Queue Implementation Summary

## ✅ Sudah Dikonfigurasi

### 1. Job Class

- **File**: `app/Jobs/SendWorkOrderNotification.php`
- **Fitur**:
    - Retry otomatis: 3 kali
    - Backoff timing: 60s → 300s → 900s
    - Queue name: 'emails'
    - Failed jobs tracking

### 2. Service Layer Update

- **File**: `app/Services/GeneralAffair/WorkOrderService.php`
- **Perubahan**:
    - Import `SendWorkOrderNotification` job
    - Method `safeMail()` menggunakan queue dispatch
    - Fallback ke direct send jika queue gagal
    - Comprehensive logging

### 3. Queue Configuration

- **Driver**: Database
- **Connection**: MySQL (sama dengan aplikasi)
- **Tables**: `jobs` dan `failed_jobs`
- **Environment**: `QUEUE_CONNECTION=database` (sudah di `.env`)

### 4. Helper File

- **File**: `start-queue-worker.bat`
- **Fungsi**: Untuk development di Windows
- **Cara pakai**: Double-click atau `start-queue-worker.bat` di terminal

## 🚀 Cara Menggunakan

### Development (Local)

```powershell
# Terminal 1: Jalankan aplikasi normal
php artisan serve

# Terminal 2: Jalankan queue worker
php artisan queue:work --queue=emails --timeout=60
```

Atau double-click `start-queue-worker.bat`

### Testing Email Queue

1. **Create Work Order**
    - Buat WO baru sebagai regular user (bukan GA Admin)
    - Status akan menjadi `waiting_approval_spv`
    - Email akan di-queue

2. **Check Queue**

    ```bash
    SELECT COUNT(*) as pending_jobs FROM jobs;
    SELECT * FROM jobs LIMIT 5;
    ```

3. **Process Queue**
    - Jalankan `php artisan queue:work --queue=emails`
    - Lihat email diproses
    - Check log: `storage/logs/laravel.log`

4. **Monitor Failed Jobs**

    ```bash
    # Lihat failed jobs
    SELECT * FROM failed_jobs;

    # Retry failed jobs
    php artisan queue:retry all
    ```

## 📊 Database Tables

### `jobs` table (Queue Pending)

```
Columns:
- id (int)
- queue (string) = 'emails'
- payload (text) = serialized job data
- attempts (int) = jumlah attempt
- reserved_at (timestamp) = saat diambil worker
- available_at (timestamp) = saat siap diproses
- created_at (timestamp)
- updated_at (timestamp)
```

### `failed_jobs` table (Job yang Gagal)

```
Columns:
- id (int)
- uuid (string) = unique identifier
- connection (string) = 'mysql'
- queue (string) = 'emails'
- payload (longtext) = job data
- exception (longtext) = error message
- failed_at (timestamp)
```

## 🔄 Email Flow

```
WO Created
  ↓
safeMail() dipanggil
  ↓
SendWorkOrderNotification::dispatch()
  ↓
Job disimpan di tabel `jobs`
  ↓
queue:work picks it up
  ↓
Mail dikirim via SMTP
  ↓
Success: Job dihapus dari `jobs`
Fail: Retry dengan backoff
After 3 attempts fail: Pindah ke `failed_jobs`
```

## 📝 Useful Commands

```bash
# Run queue worker
php artisan queue:work --queue=emails --timeout=60

# List failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# Retry specific failed job (by ID)
php artisan queue:retry 1

# Remove failed job
php artisan queue:forget 1

# Flush all failed jobs
php artisan queue:flush

# Restart queue workers (kill all)
php artisan queue:restart

# Monitor queue with verbose
php artisan queue:work --queue=emails -vvv

# Show queue status
php artisan queue:monitor emails:100
```

## ⚙️ Configuration Details

### Retry Strategy

- **Attempts**: 3 kali
- **Backoff**:
    - 1st fail → wait 60 seconds
    - 2nd fail → wait 300 seconds (5 min)
    - 3rd fail → wait 900 seconds (15 min)
    - Final fail → move to `failed_jobs`

### Timeout

- **Job timeout**: 60 seconds
- Jika job belum selesai dalam 60 detik, akan di-mark failed

### Queue Name

- **'emails'**: Semua email notification
- Bisa di-set di `SendWorkOrderNotification::__construct()`

## 🐛 Troubleshooting

### Queue worker tidak proses jobs

```bash
# Check queue worker
php artisan queue:work --queue=emails -vvv

# Restart queue
php artisan queue:restart
php artisan queue:work --queue=emails
```

### Jobs stuck di `jobs` table

```bash
# Clear dan restart
php artisan queue:restart
TRUNCATE TABLE jobs;
php artisan queue:work --queue=emails
```

### SMTP error tapi mau test

```
Set di .env: MAIL_DRIVER=log
Job tetap di-queue, email masuk ke storage/logs/
```

### Email tidak terkirim

```bash
# Check failed jobs
SELECT * FROM failed_jobs;

# Check logs
tail -f storage/logs/laravel.log

# Retry
php artisan queue:retry all
```

## 📚 File Changes Summary

| File                                              | Change           | Purpose                                  |
| ------------------------------------------------- | ---------------- | ---------------------------------------- |
| `app/Jobs/SendWorkOrderNotification.php`          | Created          | Handle email job queue                   |
| `app/Services/GeneralAffair/WorkOrderService.php` | Updated          | Dispatch to queue instead of direct send |
| `.env`                                            | No change needed | `QUEUE_CONNECTION=database` sudah ada    |
| `config/queue.php`                                | No change needed | Database driver sudah configured         |
| `start-queue-worker.bat`                          | Created          | Helper untuk run queue worker            |
| `QUEUE_EMAIL_SETUP.md`                            | Created          | Detailed setup guide                     |

## ✨ Benefits

1. **Reliability**: Email di-queue di database, tidak hilang
2. **Retry Logic**: Otomatis retry jika gagal
3. **Performance**: Request tidak terblokir oleh email SMTP
4. **Monitoring**: Track success/failed jobs
5. **Fallback**: Jika queue error, fallback ke direct send

## 🎯 Next Steps

1. ✅ Review implementasi di atas
2. Run `php artisan queue:work --queue=emails`
3. Test dengan membuat WO baru
4. Monitor di database: `SELECT * FROM jobs;`
5. Untuk production: Setup Supervisor atau Windows Task Scheduler
