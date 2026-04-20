<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request, $dept = 'default'): View
    {
        session(['login_dept' => $dept]);
        return view('auth.login', ['dept' => $dept]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $dept = session('login_dept');

        // Jika masuk lewat /wo-eng, session 'login_dept' akan mengarahkan ke sini
        if ($dept === 'wo-eng' || $dept === 'eng') {
            return redirect()->route('eng.index');
        } elseif ($dept === 'wo-ga' || $dept === 'ga') {
            return redirect()->route('ga.index');
        } elseif ($dept === 'wo-fh' || $dept === 'fh') {
            return redirect()->route('fh.index');
        }

        return redirect()->intended(route('landing'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
