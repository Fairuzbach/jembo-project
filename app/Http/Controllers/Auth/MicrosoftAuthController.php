<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class MicrosoftAuthController extends Controller
{
    // 1. Redirect User ke Halaman Login Microsoft
    public function login()
    {
        $tenant = env('O365_TENANT_ID');
        $clientId = env('O365_CLIENT_ID');
        $redirectUri = env('O365_REDIRECT_URI');

        // Scope Wajib: 
        // offline_access = Agar dapat Refresh Token (Login awet)
        // Mail.Send = Izin kirim email
        $scopes = 'offline_access Mail.Send User.Read';

        $url = "https://login.microsoftonline.com/$tenant/oauth2/v2.0/authorize?" . http_build_query([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'response_mode' => 'query',
            'scope' => $scopes,
            'state' => '12345', // Opsional, untuk keamanan CSRF
        ]);

        return redirect($url);
    }

    // 2. Terima 'Code', Tukar Jadi 'Token', Lalu Simpan
    public function callback(Request $request)
    {
        $code = $request->input('code');

        if (!$code) {
            return 'Error: Tidak ada code yang diterima dari Microsoft.';
        }

        $tenant = env('O365_TENANT_ID');
        $clientId = env('O365_CLIENT_ID');
        $clientSecret = env('O365_CLIENT_SECRET');
        $redirectUri = env('O365_REDIRECT_URI');

        // Request Token ke Microsoft (Sesuai artikel: /token endpoint)
        $response = Http::asForm()->post("https://login.microsoftonline.com/$tenant/oauth2/v2.0/token", [
            'client_id' => $clientId,
            'scope' => 'offline_access Mail.Send User.Read',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
            'client_secret' => $clientSecret,
        ]);

        $data = $response->json();

        if (isset($data['error'])) {
            return response()->json($data);
        }

        // Simpan Token ke Database (Update jika ada, Insert jika belum)
        // Kita pakai provider 'microsoft' sebagai kunci
        DB::table('oauth_tokens')->updateOrInsert(
            ['provider' => 'microsoft'],
            [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null, // Simpan Refresh Token!
                'expires_in' => $data['expires_in'],
                'updated_at' => now(),
            ]
        );

        return "BERHASIL! Token sudah disimpan. Sistem GA siap mengirim email.";
    }
}
