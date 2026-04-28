<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * =========================================================================
 * TrackUserActivity Middleware
 * =========================================================================
 * Mencatat aktivitas terakhir user di setiap request.
 * Menggunakan Cache untuk menghindari DB write di setiap request,
 * hanya update DB setiap 60 detik sekali per user.
 *
 * Cara kerja:
 * 1. Setiap request → simpan timestamp ke Cache (TTL 5 menit)
 * 2. Jika sudah >60 detik sejak update DB terakhir → update DB
 * 3. Halaman monitor membaca Cache untuk daftar user aktif
 *
 * Safety:
 * - Cache::put() tidak akan throw fatal exception
 * - DB save dibungkus try-catch agar error tidak mengganggu request user
 * =========================================================================
 */
class TrackUserActivity
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user     = Auth::user();
            $cacheKey = 'user_activity_' . $user->id;

            /**
             * Simpan info user ke Cache dengan TTL 5 menit.
             * Jika user tidak ada request selama 5 menit → dianggap offline.
             */
            Cache::put($cacheKey, [
                'id'            => $user->id,
                'name'          => $user->name,
                'divisi'        => $user->divisi ?? '-',
                'role'          => $user->role ?? '-',
                'last_activity' => now()->toDateTimeString(),
                'current_url'   => $request->path(),
            ], now()->addMinutes(5));

            /**
             * Update DB hanya setiap 60 detik untuk menghindari
             * query write yang terlalu sering ke database.
             *
             * Dibungkus try-catch agar jika DB error, request user
             * tetap berjalan normal dan error hanya dicatat ke log.
             */
            $dbUpdateKey = 'user_db_updated_' . $user->id;
            if (!Cache::has($dbUpdateKey)) {
                try {
                    $user->timestamps    = false; // Jangan update updated_at
                    $user->last_activity = now();
                    $user->save();

                    // Tandai bahwa DB sudah diupdate, tunggu 60 detik lagi
                    Cache::put($dbUpdateKey, true, now()->addSeconds(60));
                } catch (\Exception $e) {
                    // Error dicatat ke log, request tetap jalan normal
                    Log::error('TrackUserActivity DB Error: ' . $e->getMessage());
                }
            }
        }

        return $next($request);
    }
}
