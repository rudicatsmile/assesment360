<?php

namespace Tests\Feature\Auth;

use App\Models\PhoneLoginVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhoneLoginVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_send_verification_code_with_valid_phone(): void
    {
        config()->set('services.whatsapp_business.enabled', true);
        config()->set('services.whatsapp_business.base_url', 'https://wa.test');
        config()->set('services.whatsapp_business.access_token', 'token');
        config()->set('services.whatsapp_business.messages_endpoint', '/messages');

        Http::fake([
            'https://wa.test/messages' => Http::response([
                'messages' => [['id' => 'wamid.test']],
            ], 200),
        ]);

        User::factory()->create([
            'phone_number' => '+6281234567890',
            'is_active' => true,
        ]);

        $response = $this->post(route('login.send_verification'), [
            'country_code' => '+62',
            'phone_number' => '81234567890',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('phone_login_verifications', [
            'phone_e164' => '+6281234567890',
            'provider_status' => 'sent',
        ]);
    }

    public function test_verification_fails_after_three_wrong_attempts(): void
    {
        $user = User::factory()->create([
            'phone_number' => '+628111111111',
            'is_active' => true,
        ]);

        $verification = PhoneLoginVerification::query()->create([
            'user_id' => $user->id,
            'country_code' => '+62',
            'phone_e164' => '+628111111111',
            'verification_code_hash' => Hash::make('123456'),
            'attempt_count' => 0,
            'max_attempts' => 3,
            'expires_at' => now()->addMinutes(5),
            'provider_status' => 'sent',
        ]);

        $this->withSession(['phone_login_verification_id' => $verification->id])
            ->post(route('login.verify_code'), ['verification_code' => '000000']);
        $this->withSession(['phone_login_verification_id' => $verification->id])
            ->post(route('login.verify_code'), ['verification_code' => '000000']);
        $response = $this->withSession(['phone_login_verification_id' => $verification->id])
            ->post(route('login.verify_code'), ['verification_code' => '000000']);

        $response->assertSessionHas('error');
        $this->assertGuest();
        $this->assertDatabaseHas('phone_login_verifications', [
            'id' => $verification->id,
            'provider_status' => 'locked',
        ]);
    }

    public function test_verification_code_expires_after_five_minutes(): void
    {
        $user = User::factory()->create([
            'phone_number' => '+628222222222',
            'is_active' => true,
        ]);

        $verification = PhoneLoginVerification::query()->create([
            'user_id' => $user->id,
            'country_code' => '+62',
            'phone_e164' => '+628222222222',
            'verification_code_hash' => Hash::make('123456'),
            'attempt_count' => 0,
            'max_attempts' => 3,
            'expires_at' => now()->subMinute(),
            'provider_status' => 'sent',
        ]);

        $response = $this->withSession(['phone_login_verification_id' => $verification->id])
            ->post(route('login.verify_code'), ['verification_code' => '123456']);

        $response->assertSessionHas('error');
        $this->assertGuest();
        $this->assertDatabaseHas('phone_login_verifications', [
            'id' => $verification->id,
            'provider_status' => 'expired',
        ]);
    }
}
