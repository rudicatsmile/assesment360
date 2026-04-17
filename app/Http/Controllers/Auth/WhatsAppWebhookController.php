<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PhoneLoginVerification;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if ($request->isMethod('get')) {
            return $this->verifyHandshake($request);
        }

        $payload = $request->all();
        $statusItems = $payload['entry'][0]['changes'][0]['value']['statuses'] ?? [];

        foreach ($statusItems as $statusItem) {
            $messageId = (string) ($statusItem['id'] ?? '');
            $status = (string) ($statusItem['status'] ?? '');
            if ($messageId === '') {
                continue;
            }

            PhoneLoginVerification::query()
                ->where('provider_message_id', $messageId)
                ->update(['provider_status' => $status]);
        }

        Log::info('wa.webhook.received', [
            'status_count' => count($statusItems),
            'payload' => $payload,
        ]);

        return response('EVENT_RECEIVED', 200);
    }

    private function verifyHandshake(Request $request): Response
    {
        $mode = (string) $request->query('hub_mode', $request->query('hub.mode', ''));
        $verifyToken = (string) $request->query('hub_verify_token', $request->query('hub.verify_token', ''));
        $challenge = (string) $request->query('hub_challenge', $request->query('hub.challenge', ''));

        if ($mode === 'subscribe' && $verifyToken !== '' && hash_equals((string) config('services.whatsapp_business.webhook_verify_token'), $verifyToken)) {
            return response($challenge, 200);
        }

        return response('FORBIDDEN', 403);
    }
}
