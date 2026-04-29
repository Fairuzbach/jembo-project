<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request, $dept = 'default'): View
    {
        session(['login_dept' => $dept]);
        return view('auth.login', ['dept' => $dept]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        /**
         * Catat event LOGIN ke Cache.
         * Key: session_history_YYYY-MM-DD → array of events
         * TTL: 25 jam agar history hari ini tidak hilang sebelum tengah malam
         */
        $this->recordSessionEvent('login', Auth::user());

        $dept = session('login_dept');

        if ($dept === 'wo-eng' || $dept === 'eng') {
            return redirect()->route('eng.index');
        } elseif ($dept === 'wo-ga' || $dept === 'ga') {
            return redirect()->route('ga.index');
        } elseif ($dept === 'wo-fh' || $dept === 'fh') {
            return redirect()->route('fh.index');
        }

        return redirect()->intended(route('landing'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        /**
         * Catat event LOGOUT sebelum session di-invalidate
         * agar data user masih bisa dibaca
         */
        if (Auth::check()) {
            $this->recordSessionEvent('logout', Auth::user());
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Simpan event login/logout ke Cache sebagai list harian.
     * Menggunakan prepend agar event terbaru ada di atas.
     *
     * @param string $type  'login' | 'logout'
     * @param \App\Models\User $user
     */
    private function recordSessionEvent(string $type, $user): void
    {
        try {
            $today      = now()->format('Y-m-d');
            $cacheKey   = "session_history_{$today}";
            $history    = Cache::get($cacheKey, []);

            // Prepend — event terbaru di index 0
            array_unshift($history, [
                'type'    => $type,
                'user_id' => $user->id,
                'name'    => $user->name,
                'divisi'  => $user->divisi ?? '-',
                'role'    => $user->role ?? '-',
                'time'    => now()->format('H:i:s'),
            ]);

            // Batasi maksimal 100 event agar Cache tidak membengkak
            $history = array_slice($history, 0, 100);

            Cache::put($cacheKey, $history, now()->addHours(25));
        } catch (\Exception $e) {
            // Jangan sampai error Cache mengganggu proses login/logout
            \Log::error('recordSessionEvent error: ' . $e->getMessage());
        }
    }
}
