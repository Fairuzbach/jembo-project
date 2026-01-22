<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail; // Uncomment jika butuh verifikasi email
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected static function booted()
    {
        static::created(function ($user) {
            // 1. CEK KASUS DEFAULT (Jika user dibuat tanpa role spesifik)
            // Contoh: Register biasa atau Import tanpa kolom role
            if (empty($user->role)) {

                // A. Assign ke Spatie (tabel model_has_roles)
                // Pastikan role 'User' (atau 'user') sudah ada di tabel roles
                $user->assignRole(self::ROLE_USER);

                // B. Assign ke Kolom 'role' (tabel users)
                // Ini PENTING karena function isGaAdmin() Anda membaca kolom ini
                $user->role = self::ROLE_USER;
                $user->saveQuietly(); // Simpan tanpa mentrigger event loop
            }

            // 2. CEK KASUS KHUSUS (Jika user dibuat dengan role tertentu)
            // Contoh: Dibuat lewat Seeder atau Admin Panel dengan role 'ga.admin'
            else {
                // Kita harus pastikan Spatie juga tahu role ini
                // Agar sinkron antara kolom 'role' dan sistem permission Spatie
                if (!$user->hasRole($user->role)) {
                    try {
                        $user->assignRole($user->role);
                    } catch (\Exception $e) {
                        // Fallback: Jika nama role di kolom tidak ditemukan di tabel roles spatie
                        // Defaultkan ke User biasa agar tidak error
                        $user->assignRole(self::ROLE_USER);
                    }
                }
            }
        });
    }

    // =========================================================================
    // 1. KONSTANTA ROLE (KAMUS ROLE)
    // =========================================================================
    // Gunakan ini di Controller/Blade daripada mengetik string manual.
    // Contoh: User::ROLE_GA_ADMIN

    const ROLE_USER      = 'user';
    const ROLE_GA_ADMIN  = 'ga.admin';
    const ROLE_ENG_ADMIN = 'eng.admin'; // Engineering
    const ROLE_MT_ADMIN  = 'mt.admin';  // Maintenance
    const ROLE_FH_ADMIN  = 'fh.admin';  // Facility (Konstruksi)

    // =========================================================================
    // 2. CONFIGURATION
    // =========================================================================

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nik',      // Login utama (pengganti username)
        'name',
        'email',
        'password',
        'role',     // Menyimpan salah satu konstanta di atas
        'is_active',
        'divisi',   // Menyimpan departemen user (e.g., 'Engineering', 'Maintenance')
        'jabatan',
        'job_level'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean'
    ];

    // =========================================================================
    // 3. RELATIONSHIPS
    // =========================================================================

    /**
     * Relasi ke data Karyawan (Master Data)
     * Menghubungkan kolom 'nik' di users dengan 'nik' di employees.
     */
    public function employee()
    {
        return $this->belongsTo(\App\Models\Employee::class, 'nik', 'nik');
    }

    // =========================================================================
    // 4. HELPER METHODS (LOGIKA PINTAS)
    // =========================================================================

    /**
     * Cek apakah user adalah salah satu dari Admin Teknis (Eng, MT, atau FH)
     * Berguna untuk tombol Approval.
     */
    public function isTeknisAdmin()
    {
        return in_array($this->role, [
            self::ROLE_ENG_ADMIN,
            self::ROLE_MT_ADMIN,
            self::ROLE_FH_ADMIN
        ]);
    }

    /**
     * Cek apakah user adalah GA Admin
     */
    public function isGaAdmin()
    {
        return $this->role === self::ROLE_GA_ADMIN;
    }

    /**
     * Cek apakah user adalah Boss (Manager/SPV)
     * Mengambil data dari tabel Employee.
     */
    public function isBoss()
    {
        // Jika data employee tidak ada, otomatis bukan boss
        if (!$this->employee) return false;

        $position = strtoupper($this->employee->position);

        // Cek kata kunci jabatan
        return str_contains($position, 'SPV') ||
            str_contains($position, 'MANAGER') ||
            str_contains($position, 'CHIEF') ||
            str_contains($position, 'HEAD');
    }

    /**
     * Helper untuk mengambil nama divisi user dengan aman
     */
    public function getDivisionName()
    {
        // Prioritas 1: Ambil dari tabel users kolom 'divisi'
        if ($this->divisi) {
            return $this->divisi;
        }

        // Prioritas 2: Ambil dari tabel employee kolom 'department'
        if ($this->employee) {
            return $this->employee->department;
        }

        return 'General';
    }
}
