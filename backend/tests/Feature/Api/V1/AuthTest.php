<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Fraud\Models\UserDevice;
use App\Models\User;
use Database\Seeders\LedgerAccountSeeder;
use Database\Seeders\PlatformSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LedgerAccountSeeder::class);
        $this->seed(PlatformSettingSeeder::class);
    }

    public function test_user_can_register_and_receives_signup_bonus_via_ledger(): void
    {
        $payload = [
            'name' => 'John Hunter',
            'username' => 'johnhunter',
            'email' => 'john@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'device_hash' => 'device_hash_john_unique',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Registration successful.')
            ->assertJsonPath('user.username', 'johnhunter')
            ->assertJsonPath('user.available_coins', 250) // 250 signup bonus coins
            ->assertJsonPath('signup_bonus_coins', 250)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'username',
                    'email',
                    'available_coins',
                    'pending_coins',
                    'referral_code',
                    'signup_bonus_paid',
                ],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'username' => 'johnhunter',
            'email' => 'john@example.com',
            'available_coins' => 250,
            'signup_bonus_paid' => true,
        ]);

        $this->assertDatabaseHas('user_devices', [
            'device_hash' => 'device_hash_john_unique',
        ]);

        $this->assertDatabaseHas('ledger_transactions', [
            'type' => 'signup_bonus',
        ]);
    }

    public function test_registration_with_referral_code_sets_referrer(): void
    {
        $referrer = User::factory()->create([
            'referral_code' => 'SPONSOR1',
        ]);

        $payload = [
            'name' => 'Referred Hunter',
            'username' => 'referredguy',
            'email' => 'referred@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'referral_code' => 'SPONSOR1',
            'device_hash' => 'device_hash_referred_unique',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'username' => 'referredguy',
            'referred_by_id' => $referrer->id,
        ]);
    }

    public function test_registration_blocked_if_device_hash_already_registered(): void
    {
        // First user registers
        $existing = User::factory()->create();
        UserDevice::create([
            'user_id' => $existing->id,
            'device_hash' => 'duplicate_device_collision_1',
            'ip_address' => '127.0.0.1',
        ]);

        // Second user attempts to register from same device
        $payload = [
            'name' => 'Second Account',
            'username' => 'secondacc',
            'email' => 'second@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'device_hash' => 'duplicate_device_collision_1',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['username' => 'secondacc']);
    }

    public function test_registration_blocked_if_webdriver_flag_is_true(): void
    {
        $payload = [
            'name' => 'Bot Account',
            'username' => 'botuser',
            'email' => 'bot@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'device_hash' => 'bot_device_hash',
            'is_webdriver' => true,
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['username' => 'botuser']);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'hunter@coinhunters.com',
            'username' => 'hunterpro',
            'password' => 'SecretPassword123',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'hunter@coinhunters.com',
            'password' => 'SecretPassword123',
            'device_hash' => 'login_device_hash',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Login successful.')
            ->assertJsonPath('user.username', 'hunterpro')
            ->assertJsonStructure(['user', 'token']);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'hunter@coinhunters.com',
            'password' => 'SecretPassword123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'hunter@coinhunters.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'The provided credentials do not match our records.');
    }

    public function test_login_blocked_if_user_is_banned(): void
    {
        User::factory()->create([
            'email' => 'banned@coinhunters.com',
            'password' => 'SecretPassword123',
            'status' => 'banned',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'banned@coinhunters.com',
            'password' => 'SecretPassword123',
        ]);

        $response->assertStatus(403);
    }

    public function test_authenticated_user_can_fetch_me_profile(): void
    {
        $user = User::factory()->create([
            'username' => 'profileuser',
            'available_coins' => 1250,
            'pending_coins' => 500,
            'lifetime_earned_coins' => 1750,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('user.username', 'profileuser')
            ->assertJsonPath('user.available_coins', 1250)
            ->assertJsonPath('user.available_usd', '1.25') // 1,250 coins = $1.25
            ->assertJsonPath('user.pending_coins', 500);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->assertCount(0, $user->tokens);
    }
}
