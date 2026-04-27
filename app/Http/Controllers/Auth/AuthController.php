<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PhoneLoginVerification;
use App\Models\User;
use App\Services\WhatsAppBusinessService;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        $mode = $this->resolveLoginMode();

        return view('auth.login', [
            'verificationPending' => (bool) session('phone_login_verification_id'),
            'maskedPhone' => session('phone_login_masked'),
            'loginMode' => $mode,
            'passwordLoginEnabled' => in_array($mode, ['password', 'both'], true),
            'whatsAppLoginEnabled' => in_array($mode, ['whatsapp', 'both'], true),
        ]);
    }

    public function showAdminLogin(): View
    {
        return view('auth.login', [
            'verificationPending' => false,
            'maskedPhone' => null,
            'loginMode' => 'password',
            'passwordLoginEnabled' => true,
            'whatsAppLoginEnabled' => false,
        ]);
    }

    public function adminLoginWithPassword(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = (bool) $request->boolean('remember');
        if (! Auth::attempt($credentials, $remember)) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->with('error', 'Email atau password tidak valid.');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('role.dashboard'));
    }

    public function loginWithPassword(Request $request): RedirectResponse
    {
        if (! in_array($this->resolveLoginMode(), ['password', 'both'], true)) {
            return back()->with('error', 'Mode login email/password saat ini dinonaktifkan.');
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = (bool) $request->boolean('remember');
        if (! Auth::attempt($credentials, $remember)) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->with('error', 'Email atau password tidak valid.');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('role.dashboard'));
    }

    public function sendVerification(Request $request, WhatsAppBusinessService $service): RedirectResponse
    {
        if (! in_array($this->resolveLoginMode(), ['whatsapp', 'both'], true)) {
            return back()->with('error', 'Mode login verifikasi WhatsApp saat ini dinonaktifkan.');
        }

        $validated = $request->validate([
            'phone_number' => ['required', 'regex:/^0[0-9]{6,14}$/'],
        ], [
            'phone_number.regex' => 'Format nomor telepon tidak valid. Gunakan format 08xxxxxxxxxx.',
        ]);

        $countryCode = '+62';
        $phoneE164 = $this->normalizePhone($countryCode, $validated['phone_number']);
        $user = $this->findUserByPhone($phoneE164, $countryCode, $validated['phone_number']);

        if (!$user || !$user->is_active) {
            Log::warning('auth.phone_login.invalid_number', ['phone' => $phoneE164]);

            return back()
                ->withInput($request->only('phone_number'))
                ->with('error', 'Nomor telepon tidak ditemukan atau tidak aktif.');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $verification = PhoneLoginVerification::query()->create([
            'user_id' => $user->id,
            'country_code' => $countryCode,
            'phone_e164' => $phoneE164,
            'verification_code_hash' => Hash::make($code),
            'attempt_count' => 0,
            'max_attempts' => 3,
            'expires_at' => now()->addMinutes(5),
            'provider_status' => 'pending',
        ]);

        $sendResult = $service->sendVerificationCode($phoneE164, $code);
        if (!$sendResult['success']) {
            $verification->update([
                'provider_status' => 'send_failed',
                'last_error' => $sendResult['error'],
            ]);

            Log::error('auth.phone_login.send_failed', [
                'verification_id' => $verification->id,
                'phone' => $phoneE164,
                'error' => $sendResult['error'],
            ]);

            return back()
                ->withInput($request->only('phone_number'))
                ->with('error', 'Gagal mengirim kode verifikasi. Silakan coba lagi.');
        }

        $verification->update([
            'provider_status' => 'sent',
            'provider_message_id' => $sendResult['message_id'],
            'sent_at' => now(),
        ]);

        session([
            'phone_login_verification_id' => $verification->id,
            'phone_login_masked' => $this->maskPhone($phoneE164),
            'phone_login_number' => $validated['phone_number'],
        ]);

        Log::info('auth.phone_login.code_sent', [
            'verification_id' => $verification->id,
            'user_id' => $user->id,
            'phone' => $phoneE164,
            'expires_at' => $verification->expires_at?->toISOString(),
        ]);

        return back()->with('success', 'Kode verifikasi telah dikirim ke WhatsApp Anda.');
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        if (! in_array($this->resolveLoginMode(), ['whatsapp', 'both'], true)) {
            return back()->with('error', 'Mode login verifikasi WhatsApp saat ini dinonaktifkan.');
        }

        $validated = $request->validate([
            'verification_code' => ['required', 'digits:6'],
        ]);

        $verificationId = (int) session('phone_login_verification_id', 0);
        if ($verificationId <= 0) {
            return back()
                ->with('error', 'Sesi verifikasi tidak ditemukan. Silakan kirim ulang kode.');
        }

        $verification = PhoneLoginVerification::query()->with('user')->find($verificationId);
        if (!$verification) {
            $this->clearVerificationSession();

            return back()->with('error', 'Data verifikasi tidak valid. Silakan kirim ulang kode.');
        }

        if ($this->isExpired($verification->expires_at)) {
            $verification->update(['provider_status' => 'expired']);
            $this->clearVerificationSession();
            Log::warning('auth.phone_login.expired', ['verification_id' => $verification->id]);

            return back()->with('error', 'Kode verifikasi sudah kedaluwarsa (maksimal 5 menit).');
        }

        if ($verification->attempt_count >= $verification->max_attempts) {
            $verification->update(['provider_status' => 'locked']);
            $this->clearVerificationSession();
            Log::warning('auth.phone_login.max_attempts', ['verification_id' => $verification->id]);

            return back()->with('error', 'Percobaan verifikasi melebihi batas (maksimal 3 kali).');
        }

        if (!Hash::check($validated['verification_code'], $verification->verification_code_hash)) {
            $remaining = max(0, $verification->max_attempts - ($verification->attempt_count + 1));
            $verification->increment('attempt_count');
            if (($verification->attempt_count + 1) >= $verification->max_attempts) {
                $verification->update(['provider_status' => 'locked']);
            }

            Log::warning('auth.phone_login.invalid_code', [
                'verification_id' => $verification->id,
                'remaining_attempts' => $remaining,
            ]);

            return back()->with('error', 'Kode verifikasi salah. Sisa percobaan: ' . $remaining . '.');
        }

        $verification->update([
            'attempt_count' => $verification->attempt_count + 1,
            'provider_status' => 'verified',
            'verified_at' => now(),
        ]);

        Auth::login($verification->user, true);
        $request->session()->regenerate();
        $this->clearVerificationSession();

        Log::info('auth.phone_login.success', [
            'verification_id' => $verification->id,
            'user_id' => $verification->user_id,
        ]);

        return redirect()->intended(route('role.dashboard'));
    }

    private function normalizePhone(string $countryCode, string $phoneNumber): string
    {
        $digits = ltrim($phoneNumber, '0');

        return $countryCode . $digits;
    }

    private function findUserByPhone(string $phoneE164, string $countryCode, string $phoneNumber): ?User
    {
        $digits = ltrim($phoneNumber, '0');
        $candidates = array_unique([
            $phoneE164,
            $countryCode . $digits,
            '0' . $digits,
            $phoneNumber,
        ]);

        return User::query()
            ->whereIn('phone_number', $candidates)
            ->first();
    }

    private function maskPhone(string $phone): string
    {
        if (strlen($phone) <= 6) {
            return $phone;
        }

        return substr($phone, 0, 4) . str_repeat('*', max(0, strlen($phone) - 6)) . substr($phone, -2);
    }

    private function isExpired(?CarbonInterface $expiresAt): bool
    {
        return !$expiresAt || $expiresAt->isPast();
    }

    private function clearVerificationSession(): void
    {
        session()->forget([
            'phone_login_verification_id',
            'phone_login_masked',
            'phone_login_number',
        ]);
    }

    private function resolveLoginMode(): string
    {
        $mode = strtolower((string) config('features.login_mode', 'both'));

        return in_array($mode, ['password', 'whatsapp', 'both'], true) ? $mode : 'both';
    }
}
