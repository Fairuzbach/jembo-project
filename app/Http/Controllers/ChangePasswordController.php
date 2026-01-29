<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ChangePasswordController extends Controller
{
    /**
     * Tampilkan form ganti password
     */
    public function index()
    {
        return view('auth.change-password');
    }

    /**
     * Proses update password
     */
    public function update(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:6|confirmed|different:current_password',
        ], [
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'new_password.min' => 'Password baru minimal 6 karakter.',
            'new_password.different' => 'Password baru tidak boleh sama dengan password lama.',
        ]);

        // 2. Cek apakah Password Lama Benar?
        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password lama yang Anda masukkan salah.']);
        }

        // 3. Update Password
        /** @var \App\Models\User $user */
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return back()->with('success', 'Password berhasil diperbarui! Silakan login ulang dengan password baru nanti.');
    }
}
