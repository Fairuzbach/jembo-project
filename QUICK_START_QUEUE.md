# 🚀 Quick Start: Email Queue untuk Work Order GA

## Apa itu Email Queue?

Email tidak langsung dikirim, tapi disimpan di database untuk diproses oleh worker. Ini memastikan:

- ✅ Email tidak hilang (stored in database)
- ✅ Retry otomatis jika gagal
- ✅ Aplikasi tetap cepat (tidak menunggu SMTP)
- ✅ Mudah di-monitor dan di-debug

## Setup di Development (Hanya 2 Step)

### Step 1: Run Migrations (sekali saja)

```powershell
cd "c:\Users\fairuz\Desktop\laragon\www\landing-page-wo - Copy"
php artisan migrate
```

Database tables `jobs` dan `failed_jobs` siap pakai.

### Step 2: Run Queue Worker (setiap development session)

**Option A: Double-click file**

```
start-queue-worker.bat
```

**Option B: PowerShell/Command Prompt**

```powershell
cd "c:\Users\fairuz\Desktop\laragon\www\landing-page-wo - Copy"
php artisan queue:work --queue=emails --timeout=60
```

**Output yang benar:**

```
   INFO  Processing jobs from the [emails] queue.
```

Queue worker siap mendengarkan dan memproses email!

---

## Testing Email Queue

### Test Case 1: Create Work Order (Regular User)

1. Login sebagai regular user (bukan GA Admin)
2. Create WO → Status: `waiting_approval_spv`
3. Email akan di-queue untuk dikirim ke manager

### Test Case 2: Check Queued Jobs

**Query database:**

```sql
SELECT * FROM jobs WHERE queue = 'emails';
```

**Output (contoh):**

```
id | queue  | payload | attempts | created_at
1  | emails | {...}   | 0        | 2025-01-20 10:30:45
2  | emails | {...}   | 0        | 2025-01-20 10:31:20
```

### Test Case 3: Process Queue

```bash
php artisan queue:work --queue=emails
```

**Lihat output saat job diproses:**

```
2025-01-20 10:30:46 [1] Processing: App\Jobs\SendWorkOrderNotification
2025-01-20 10:30:47 [1] Processed:  App\Jobs\SendWorkOrderNotification
```

### Test Case 4: Check Failed Jobs

Jika ada email yang gagal (misal SMTP error):

```sql
SELECT * FROM failed_jobs;
```

**Retry failed jobs:**

```bash
php artisan queue:retry all
```

---

## Troubleshooting

| Problem                  | Solution                                                       |
| ------------------------ | -------------------------------------------------------------- |
| Queue worker tidak jalan | `php artisan queue:work --queue=emails -vvv` untuk lihat error |
| Jobs tidak diproses      | Pastikan queue worker sedang berjalan di terminal              |
| Email tidak terkirim     | Check `storage/logs/laravel.log`                               |
| Jobs stuck di database   | `php artisan queue:restart` lalu run worker lagi               |

---

## File yang Dibuat/Diubah

| File                                              | Status                              |
| ------------------------------------------------- | ----------------------------------- |
| `app/Jobs/SendWorkOrderNotification.php`          | ✅ Created (Job handler)            |
| `app/Services/GeneralAffair/WorkOrderService.php` | ✅ Updated (dispatch to queue)      |
| `start-queue-worker.bat`                          | ✅ Created (Helper script)          |
| `QUEUE_EMAIL_SETUP.md`                            | ✅ Created (Detail guide)           |
| `EMAIL_QUEUE_SUMMARY.md`                          | ✅ Created (Implementation summary) |

---

## Commands Penting

```bash
# Jalankan queue worker (utama)
php artisan queue:work --queue=emails --timeout=60

# Lihat pending jobs
SELECT COUNT(*) FROM jobs;

# Lihat failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Restart queue workers
php artisan queue:restart

# Clear failed jobs
php artisan queue:flush

# Monitor verbose
php artisan queue:work --queue=emails -vvv
```

---

## Production Checklist

Untuk deploy ke production:

- [ ] Run `php artisan migrate` (jika belum)
- [ ] Set `QUEUE_CONNECTION=database` di `.env`
- [ ] Setup Supervisor atau Windows Task Scheduler untuk keep worker running
- [ ] Monitor `storage/logs/` untuk errors
- [ ] Setup cron untuk check `failed_jobs` periodically
- [ ] Test email dengan actual SMTP credentials

---

## Arsitektur

```
User Create WO
    ↓
SafeMail() → Dispatch Job
    ↓
Job saved in 'jobs' table
    ↓
queue:work picks up
    ↓
Mail::send() (via SMTP)
    ↓
Success: Remove from jobs | Fail: Retry or move to failed_jobs
```

---

**Ready to go!** 🎉

Tinggal run queue worker dan email notifikasi akan di-queue dengan reliable.
