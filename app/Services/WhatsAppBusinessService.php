<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppBusinessService
{
    /**
     * @return array{success: bool, message_id: string|null, error: string|null}
     */
    public function sendVerificationCode(string $phoneE164, string $code): array
    {
        $baseUrl = trim((string) config('services.whatsapp_business.base_url'));
        if ($baseUrl === '') {
            $baseUrl = trim((string) config('services.whatsapp.base_url', ''));
        }
        $endpoint = (string) config('services.whatsapp_business.messages_endpoint', '/messages');
        $token = trim((string) config('services.whatsapp_business.access_token'));
        if ($token === '') {
            $token = trim((string) config('services.whatsapp.token', ''));
        }
        $template = (string) config('services.whatsapp_business.template', 'login_verification');
        $enabled = (bool) config('services.whatsapp_business.enabled', false)
            || (bool) config('services.whatsapp.enabled', false);

        if (! $enabled || $baseUrl === '' || $token === '') {
            Log::warning('wa.verification.skipped', [
                'phone' => $phoneE164,
                'reason' => 'service_not_enabled_or_missing_config',
            ]);

            return ['success' => false, 'message_id' => null, 'error' => 'Service WhatsApp belum aktif atau konfigurasi belum lengkap.'];
        }

        $isWablas = str_contains(strtolower($baseUrl), 'wablas');
        if ($isWablas) {
            $payload = [
                'phone' => ltrim($phoneE164, '+'),
                'message' => "Kode verifikasi login Anda: {$code}. Berlaku 5 menit. Jangan bagikan kode ini.",
            ];

            $response = Http::timeout(12)
                ->withHeaders(['Authorization' => $token])
                ->asJson()
                ->post($baseUrl, $payload);
        } else {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $phoneE164,
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => 'id'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $code],
                                ['type' => 'text', 'text' => '5 menit'],
                            ],
                        ],
                    ],
                ],
            ];

            $response = Http::timeout(12)
                ->withToken($token)
                ->acceptJson()
                ->asJson()
                ->post(rtrim($baseUrl, '/').$endpoint, $payload);
        }

        if (! $response->successful()) {
            Log::error('wa.verification.send_failed', [
                'phone' => $phoneE164,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            return [
                'success' => false,
                'message_id' => null,
                'error' => (string) ($response->json('error.message') ?? $response->json('message') ?? 'Gagal mengirim WhatsApp.'),
            ];
        }

        $messageId = (string) ($response->json('messages.0.id') ?? $response->json('data.id') ?? $response->json('id') ?? '');
        Log::info('wa.verification.sent', [
            'phone' => $phoneE164,
            'message_id' => $messageId,
            'provider' => $isWablas ? 'wablas' : 'meta',
        ]);

        return ['success' => true, 'message_id' => ($messageId !== '' ? $messageId : null), 'error' => null];
    }
}
