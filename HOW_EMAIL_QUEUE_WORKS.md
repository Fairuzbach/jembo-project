# 📧 Email Queue - How It Works

## Flow Lengkap Email Notification

### Kapan Email Dikirim?

Email akan dikirim melalui **queue worker** dalam 2 tahap:

#### **Tahap 1: Ketika WO Dibuat (Immediate - Queue ke Database)**

```
User create WO
    ↓
safeMail() dipanggil
    ↓
SendWorkOrderNotification::dispatch()
    ↓
Job disimpan ke tabel `jobs` (LANGSUNG SELESAI - tidak menunggu)
```

✅ **Respon ke user**: "Permintaan Berhasil Dibuat" (LANGSUNG)
⏱️ **Job masuk queue**: dalam hitungan detik

#### **Tahap 2: Queue Worker Memproses Email (Background - Sesuai Queue)**

```
Queue worker running (php artisan queue:work --queue=emails)
    ↓
Worker cek tabel `jobs` setiap beberapa detik
    ↓
Ada job? → Ambil dan proses
    ↓
Mail::to($email)->send($mailable)
    ↓
Success? → Hapus dari `jobs` ✅
Failed?  → Mark as failed & retry nanti
```

⏱️ **Email dikirim**: dalam waktu kurang dari 30 detik (biasanya)

---

## Konfigurasi Email Saat Ini

### Setting di .env (Sekarang)

```
MAIL_MAILER=log          ← Untuk testing (email masuk ke logs)
MAIL_FROM_ADDRESS=pklit@jembo.com
MAIL_FROM_NAME=Work Order System
```

### Artinya:

- 📝 Email **tidak benar-benar dikirim** tapi tercatat di **logs**
- 📍 Lokasi: `storage/logs/laravel.log`
- ✅ Untuk testing dan development, ini ideal

---

## Cara Testing Email Queue

### Step 1: Ensure Queue Worker Running

```bash
php artisan queue:work --queue=emails -vvv
```

Output yang benar:

```
   INFO  Processing jobs from the [emails] queue.
```

**Jangan close terminal ini!** Worker harus tetap berjalan.

### Step 2: Create Work Order

1. Login aplikasi (sebagai regular user, bukan GA Admin)
2. Buat Work Order baru
3. Lihat pesan: "Permintaan Berhasil Dibuat" ✅

### Step 3: Check Database untuk Job

```sql
SELECT COUNT(*) as pending FROM jobs WHERE queue = 'emails';
```

Harusnya ada 1-2 records (ada job yang pending).

### Step 4: Lihat Queue Worker Memproses Job

Lihat di terminal queue worker, akan muncul:

```
2025-01-20 14:30:45 [1] Processing: App\Jobs\SendWorkOrderNotification
2025-01-20 14:30:46 [1] Processed:  App\Jobs\SendWorkOrderNotification
```

Dan job dihapus dari tabel `jobs`.

### Step 5: Check Email di Logs

```bash
tail -f storage/logs/laravel.log
```

Cari baris berisi:

```
[2025-01-20 14:30:46] local.INFO: Email notification queued for: manager@company.com
[2025-01-20 14:30:47] local.INFO: Email sent via queue: manager@company.com
```

---

## Troubleshooting

### Problem 1: Job Tidak Masuk Ke Queue

**Cek apakah table `jobs` ada:**

```sql
SHOW TABLES LIKE 'jobs';
```

Jika tidak ada, jalankan:

```bash
php artisan migrate
```

### Problem 2: Queue Worker Tidak Berjalan

Lihat di terminal:

- Ada output `Processing jobs from the [emails] queue.`?
- Jika tidak, jalankan: `php artisan queue:work --queue=emails -vvv`

### Problem 3: Job Masuk `failed_jobs`

```bash
php artisan queue:failed
```

Untuk melihat error:

```sql
SELECT * FROM failed_jobs\G
```

Cek kolom `exception` untuk error message.

### Problem 4: Email Log Tidak Muncul

1. Cek `.env`: `MAIL_MAILER=log` ✓
2. Clear config: `php artisan config:clear`
3. Check logs sudah di-write: `tail -f storage/logs/laravel.log`
4. Restart queue: `php artisan queue:restart`

---

## Timeline Email

| Waktu | Event                                    | Status                    |
| ----- | ---------------------------------------- | ------------------------- |
| 0s    | User click "Buat WO"                     | Request dimulai           |
| 0.5s  | Job dispatch ke queue                    | ✅ Job masuk tabel `jobs` |
| 1s    | Request selesai, user lihat pesan sukses | User happy ✓              |
| 1-5s  | Queue worker pick up job                 | Proses email              |
| 3-10s | Email log ditulis ke file                | 📝 Log tercatat           |
| 5-30s | Total waktu hingga selesai               | Email fully processed     |

**User tidak perlu menunggu!** Email diproses di background.

---

## Email Queue Database

### Tabel `jobs` (Pending Queue)

```
Ketika WO dibuat → Job masuk sini
Queue worker picks up → Proses email
Success → Dihapus dari sini
Fail → Mark attempts & retry
```

### Tabel `failed_jobs` (Failed Permanently)

```
Setelah 3 kali retry gagal → Masuk sini
Untuk manual retry: php artisan queue:retry all
```

---

## Next: Setup SMTP Real

Ketika siap pakai email real (Microsoft/Gmail/etc), ubah:

```env
MAIL_MAILER=smtp              ← Ganti dari 'log'
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_USERNAME=pklit@jembo.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
```

**Queue system tetap sama!** Hanya config MAIL yang berubah.

---

## Command Reference

```bash
# Start queue worker (IMPORTANT - harus berjalan!)
php artisan queue:work --queue=emails -vvv

# Check pending jobs
SELECT * FROM jobs WHERE queue='emails';

# Check email in logs
tail -f storage/logs/laravel.log | grep "Email"

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear all failed jobs
php artisan queue:flush

# Restart queue
php artisan queue:restart
```

---

## Summary

✅ **Queue system** → Sudah siap (jobs masuk database)
✅ **Email driver** → Sekarang pakai `log` (untuk testing)
✅ **Queue worker** → Harus running `php artisan queue:work --queue=emails`

**Email akan terkirim (ke logs) dalam 1-30 detik** setelah user membuat WO.

---

## Untuk Production

Ketika sudah real, setup:

1. ✅ SMTP credentials di `.env`
2. ✅ Queue worker berjalan 24/7 (via Supervisor)
3. ✅ Monitor `failed_jobs` (via cron job)
4. ✅ Log rotation (untuk storage/logs)

Sistem queue akan handle retry otomatis + reliability.
