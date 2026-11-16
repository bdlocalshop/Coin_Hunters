<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Fraud;

use App\Domain\Fraud\DTOs\DeviceInspectionDTO;
use App\Domain\Fraud\Exceptions\FraudDetectedException;
use App\Domain\Fraud\Models\UserDevice;
use App\Domain\Fraud\Services\FraudGuardService;
use App\Models\User;
use Database\Seeders\LedgerAccountSeeder;
use Database\Seeders\PlatformSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FraudGuardServiceTest extends TestCase
{
    use RefreshDatabase;

    private FraudGuardService $fraudGuard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LedgerAccountSeeder::class);
        $this->seed(PlatformSettingSeeder::class);
        $this->fraudGuard = new FraudGuardService();
    }

    public function test_clean_device_and_local_ip_is_allowed(): void
    {
        $dto = new DeviceInspectionDTO(
            ipAddress: '127.0.0.1',
            deviceHash: 'clean_hash_123',
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        );

        $result = $this->fraudGuard->inspect($dto, isRegistration: true);

        $this->assertTrue($result->isAllowed);
        $this->assertEquals(0, $result->riskScore);
        $this->assertEmpty($result->reasons);
    }

    public function test_headless_webdriver_is_blocked(): void
    {
        $dto = new DeviceInspectionDTO(
            ipAddress: '127.0.0.1',
            deviceHash: 'webdriver_hash',
            isWebdriver: true
        );

        $this->expectException(FraudDetectedException::class);
        $this->fraudGuard->inspectAndEnforce($dto, isRegistration: true);
    }

    public function test_android_emulator_is_blocked(): void
    {
        $dto = new DeviceInspectionDTO(
            ipAddress: '127.0.0.1',
            deviceHash: 'emulator_hash',
            userAgent: 'Mozilla/5.0 (Linux; Android 9; NoxPlayer Build/PI) AppleWebKit/537.36',
            isEmulator: true
        );

        $this->expectException(FraudDetectedException::class);
        $this->fraudGuard->inspectAndEnforce($dto, isRegistration: true);
    }

    public function test_duplicate_device_collision_is_blocked_on_registration(): void
    {
        $existingUser = User::factory()->create();

        UserDevice::create([
            'user_id' => $existingUser->id,
            'device_hash' => 'shared_device_hash_999',
            'ip_address' => '127.0.0.1',
        ]);

        $dto = new DeviceInspectionDTO(
            ipAddress: '127.0.0.1',
            deviceHash: 'shared_device_hash_999'
        );

        // Attempt new registration with same device hash
        $result = $this->fraudGuard->inspect($dto, isRegistration: true);

        $this->assertFalse($result->isAllowed);
        $this->assertContains('Device fingerprint is already registered to another account (1 account per device policy).', $result->reasons);

        $this->expectException(FraudDetectedException::class);
        $this->fraudGuard->inspectAndEnforce($dto, isRegistration: true);
    }

    public function test_banned_device_collision_is_blocked(): void
    {
        $bannedUser = User::factory()->create([
            'status' => 'banned',
        ]);

        UserDevice::create([
            'user_id' => $bannedUser->id,
            'device_hash' => 'blacklisted_banned_device',
            'ip_address' => '127.0.0.1',
        ]);

        $dto = new DeviceInspectionDTO(
            ipAddress: '127.0.0.1',
            deviceHash: 'blacklisted_banned_device'
        );

        $result = $this->fraudGuard->inspect($dto, isRegistration: false);

        $this->assertFalse($result->isAllowed);
        $this->assertEquals(100, $result->riskScore);
        $this->assertContains('Device fingerprint is permanently blacklisted due to prior fraud/ban.', $result->reasons);
    }

    public function test_record_device_persists_telemetry(): void
    {
        $user = User::factory()->create();

        $dto = new DeviceInspectionDTO(
            ipAddress: '127.0.0.1',
            deviceHash: 'recorded_hash_abc',
            userAgent: 'Chrome/120.0',
            screenResolution: '1920x1080',
            timezone: 'America/New_York'
        );

        $result = $this->fraudGuard->inspect($dto);
        $device = $this->fraudGuard->recordDevice($user, $dto, $result);

        $this->assertDatabaseHas('user_devices', [
            'id' => $device->id,
            'user_id' => $user->id,
            'device_hash' => 'recorded_hash_abc',
            'ip_address' => '127.0.0.1',
        ]);
    }
}
