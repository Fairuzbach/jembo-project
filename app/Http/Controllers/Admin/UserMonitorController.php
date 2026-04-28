<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

/**
 * =========================================================================
 * UserMonitorController
 * =========================================================================
 * Controller untuk halaman monitoring user aktif secara realtime.
 * Hanya bisa diakses oleh super.admin.
 *
 * Endpoint:
 * - GET /admin/monitor         → Halaman monitor
 * - GET /admin/monitor/data    → JSON data user aktif (dipanggil via polling AJAX)
 * =========================================================================
 */
class UserMonitorController extends Controller
{
    /**
     * Tampilkan halaman monitor.
     * Data awal diambil langsung, selanjutnya di-update via polling.
     */
    public function index()
    {
        // Guard: hanya super.admin
        if (Auth::user()->role !== 'SuperrrAdmin') {
            abort(403, 'Akses Ditolak.');
        }

        $activeUsers = $this->getActiveUsers();

        return view('admin.monitor', compact('activeUsers'));
    }

    /**
     * Endpoint JSON untuk polling AJAX.
     * Dipanggil setiap 5 detik dari halaman monitor.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function data()
    {

        // Guard: hanya super.admin
        if (Auth::user()->role !== 'SuperrrAdmin') {
            abort(403, 'Akses Ditolak.');
        }

        return response()->json([
            'active_users' => $this->getActiveUsers(),
            'total'        => count($this->getActiveUsers()),
            'timestamp'    => now()->format('H:i:s'),
        ]);
    }

    /**
     * Ambil semua user yang aktif dalam 5 menit terakhir dari Cache.
     * Jika Cache miss (server restart dll), fallback ke DB via last_activity.
     *
     * @return array
     */
    private function getActiveUsers(): array
    {
        $activeUsers = [];

        // Ambil semua user yang punya kolom last_activity
        $users = User::whereNotNull('last_activity')
            ->where('last_activity', '>=', now()->subMinutes(5))
            ->where('is_active', 1)
            ->get();

        foreach ($users as $user) {
            // Cek apakah ada data cache yang lebih fresh
            $cacheData = Cache::get('user_activity_' . $user->id);

            $activeUsers[] = [
                'id'            => $user->id,
                'name'          => $user->name,
                'divisi'        => $user->divisi ?? '-',
                'role'          => $user->role ?? '-',
                'last_activity' => $cacheData['last_activity'] ?? $user->last_activity,
                'current_url'   => $cacheData['current_url'] ?? '-',
            ];
        }

        // Urutkan berdasarkan last_activity terbaru
        usort($activeUsers, fn($a, $b) => $b['last_activity'] <=> $a['last_activity']);

        return $activeUsers;
    }
}
