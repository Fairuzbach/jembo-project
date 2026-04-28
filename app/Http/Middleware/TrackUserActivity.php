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
            $user = Auth::user();

            /**
             * Exclude SuperrrAdmin dari tracking — tidak perlu muncul di monitor.
             * Exclude juga halaman monitor itu sendiri agar tidak tercatat.
             */
            $isSuper          = $user->role === 'SuperrrAdmin';
            $isMonitorPage    = str_starts_with($request->path(), 'superadmin/monitor');

            if (!$isSuper && !$isMonitorPage) {
                $cacheKey = 'user_activity_' . $user->id;

                Cache::put($cacheKey, [
                    'id'            => $user->id,
                    'name'          => $user->name,
                    'divisi'        => $user->divisi ?? '-',
                    'role'          => $user->role ?? '-',
                    'last_activity' => now()->toDateTimeString(),
                    'current_url'   => $request->path(),
                ], now()->addMinutes(5));

                $dbUpdateKey = 'user_db_updated_' . $user->id;
                if (!Cache::has($dbUpdateKey)) {
                    try {
                        $user->timestamps    = false;
                        $user->last_activity = now();
                        $user->save();
                        Cache::put($dbUpdateKey, true, now()->addSeconds(60));
                    } catch (\Exception $e) {
                        Log::error('TrackUserActivity DB Error: ' . $e->getMessage());
                    }
                }
            }
        }

        return $next($request);
    }
}
