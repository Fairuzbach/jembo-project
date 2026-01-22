<?php

namespace App\Mail\Transport;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email; // Pastikan use ini ada
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Exception;

class MicrosoftGraphTransport extends AbstractTransport
{
    protected function doSend(SentMessage $message): void
    {
        // 1. Ambil Token dari Database
        $tokenData = DB::table('oauth_tokens')->where('provider', 'microsoft')->first();

        if (!$tokenData) {
            throw new Exception("Token Microsoft tidak ditemukan. Silakan login ulang di /auth/login-microsoft");
        }

        $accessToken = $tokenData->access_token;

        // 2. Cek Expired & Refresh Token Otomatis
        // Casting (int) agar Carbon tidak error
        $expiryTime = \Carbon\Carbon::parse($tokenData->updated_at)->addSeconds((int) $tokenData->expires_in);

        if (now()->greaterThanOrEqualTo($expiryTime->subMinutes(5))) {
            // Token mau habis/sudah habis, lakukan REFRESH
            $accessToken = $this->refreshToken($tokenData->refresh_token);
        }

        // 3. Ambil Object Email Asli
        $email = $message->getOriginalMessage();

        // Pastikan ini adalah instance Email yang valid
        if (!$email instanceof Email) {
            throw new Exception('Format email tidak didukung. Harap gunakan Mailable class.');
        }

        // Ambil Body (Prioritaskan HTML, kalau tidak ada pakai Text biasa)
        $bodyContent = $email->getHtmlBody();
        $bodyType = 'HTML';

        if (empty($bodyContent)) {
            $bodyContent = $email->getTextBody();
            $bodyType = 'Text';
        }

        // Susun Payload untuk Microsoft Graph API
        $payload = [
            'message' => [
                'subject' => $email->getSubject(),
                'body' => [
                    'contentType' => $bodyType,
                    'content' => $bodyContent,
                ],
                'toRecipients' => $this->formatRecipients($email->getTo()),
                'ccRecipients' => $this->formatRecipients($email->getCc()),
                'bccRecipients' => $this->formatRecipients($email->getBcc()),
            ],
            'saveToSentItems' => 'true',
        ];

        // 4. Kirim ke Microsoft Graph API
        $response = Http::withToken($accessToken)
            ->post('https://graph.microsoft.com/v1.0/me/sendMail', $payload);

        if (!$response->successful()) {
            throw new Exception("Gagal kirim email via Graph API: " . $response->body());
        }
    }

    // Helper: Format penerima sesuai maunya Microsoft
    private function formatRecipients(array $recipients)
    {
        return array_map(function ($recipient) {
            return [
                'emailAddress' => [
                    'address' => $recipient->getAddress(),
                    'name' => $recipient->getName(),
                ],
            ];
        }, $recipients);
    }

    // Helper: Refresh Token
    private function refreshToken($refreshToken)
    {
        $response = Http::asForm()->post("https://login.microsoftonline.com/" . env('O365_TENANT_ID') . "/oauth2/v2.0/token", [
            'client_id' => env('O365_CLIENT_ID'),
            'client_secret' => env('O365_CLIENT_SECRET'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        $data = $response->json();

        if (isset($data['error'])) {
            throw new Exception("Gagal Refresh Token: " . ($data['error_description'] ?? 'Unknown error'));
        }

        // Update Database dengan token baru
        DB::table('oauth_tokens')->where('provider', 'microsoft')->update([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $refreshToken,
            'expires_in' => $data['expires_in'],
            'updated_at' => now(),
        ]);

        return $data['access_token'];
    }

    public function __toString(): string
    {
        return 'microsoft-graph';
    }
}
