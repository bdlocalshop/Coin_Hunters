<?php

declare(strict_types=1);

namespace App\Domain\Fraud\Services;

use App\Domain\Fraud\DTOs\DeviceInspectionDTO;
use App\Domain\Fraud\DTOs\FraudInspectionResult;
use App\Domain\Fraud\Exceptions\FraudDetectedException;
use App\Domain\Fraud\Models\UserDevice;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FraudGuardService
{
    private ?string $maxmindAccountId;
    private ?string $maxmindLicenseKey;
    private ?string $proxycheckApiKey;

    public function __construct()
    {
        $this->maxmindAccountId = config('services.maxmind.account_id', env('MAXMIND_ACCOUNT_ID'));
        $this->maxmindLicenseKey = config('services.maxmind.license_key', env('MAXMIND_LICENSE_KEY'));
        $this->proxycheckApiKey = config('services.proxycheck.api_key', env('PROXYCHECK_API_KEY'));
    }

    /**
     * Inspect network and device parameters against fraud heuristics and external APIs.
     */
    public function inspect(
        DeviceInspectionDTO $dto,
        ?int $currentUserId = null,
        bool $isRegistration = false,
    ): FraudInspectionResult {
        $reasons = [];
        $riskScore = 0;
        $isProxy = false;
        $isVpn = false;
        $isTor = false;
        $isHosting = false;
        $isEmulator = $dto->isEmulator;
        $countryCode = 'US';
        $asn = null;

        // 1. Check Webdriver / Headless automation flags
        if ($dto->isWebdriver) {
            $reasons[] = 'Headless browser automation detected (navigator.webdriver = true).';
            $riskScore = 100;
        }

        // 2. Check Android Emulator signatures
        if ($this->detectEmulator($dto)) {
            $isEmulator = true;
            $reasons[] = 'Known Android emulator environment detected.';
            $riskScore = max($riskScore, 95);
        }

        // 3. Check Banned Device Collision
        $bannedCollision = UserDevice::where('device_hash', $dto->deviceHash)
            ->whereHas('user', fn ($query) => $query->where('status', 'banned'))
            ->exists();

        if ($bannedCollision) {
            $reasons[] = 'Device fingerprint is permanently blacklisted due to prior fraud/ban.';
            $riskScore = 100;
        }

        // 4. Check 1 Account Per Device rule on registration
        if ($isRegistration && !$bannedCollision) {
            $deviceCollision = UserDevice::where('device_hash', $dto->deviceHash)->exists();
            if ($deviceCollision) {
                $reasons[] = 'Device fingerprint is already registered to another account (1 account per device policy).';
                $riskScore = max($riskScore, 85);
            }
        }

        // 5. External ProxyCheck.io and MaxMind screening (or local mock fallback)
        $networkResult = $this->queryNetworkIntelligence($dto->ipAddress);
        $countryCode = $networkResult['countryCode'] ?? $countryCode;
        $asn = $networkResult['asn'] ?? $asn;

        if ($networkResult['isProxy']) {
            $isProxy = true;
            $reasons[] = 'Proxy or anonymizer connection detected.';
            $riskScore = max($riskScore, 90);
        }

        if ($networkResult['isVpn']) {
            $isVpn = true;
            $reasons[] = 'Commercial or residential VPN connection detected.';
            $riskScore = max($riskScore, 90);
        }

        if ($networkResult['isTor']) {
            $isTor = true;
            $reasons[] = 'Tor exit node connection detected.';
            $riskScore = 100;
        }

        if ($networkResult['isHosting']) {
            $isHosting = true;
            $reasons[] = 'Datacenter or web hosting ASN detected (residential IP required).';
            $riskScore = max($riskScore, 85);
        }

        $riskScore = max($riskScore, (int) ($networkResult['riskScore'] ?? 0));

        $isAllowed = empty($reasons) && $riskScore < 70;

        return new FraudInspectionResult(
            isAllowed: $isAllowed,
            riskScore: $riskScore,
            isProxy: $isProxy,
            isVpn: $isVpn,
            isTor: $isTor,
            isHosting: $isHosting,
            isEmulator: $isEmulator,
            countryCode: $countryCode,
            asn: $asn,
            reasons: $reasons,
        );
    }

    /**
     * Inspect and throw FraudDetectedException if blocked.
     *
     * @throws FraudDetectedException
     */
    public function inspectAndEnforce(
        DeviceInspectionDTO $dto,
        ?int $currentUserId = null,
        bool $isRegistration = false,
    ): FraudInspectionResult {
        $result = $this->inspect($dto, $currentUserId, $isRegistration);

        if (!$result->isAllowed) {
            $message = !empty($result->reasons)
                ? $result->reasons[0]
                : 'Security screening failed: suspicious connection or device profile.';

            throw new FraudDetectedException($result, $message);
        }

        return $result;
    }

    /**
     * Persist inspection telemetry in user_devices table.
     */
    public function recordDevice(
        User $user,
        DeviceInspectionDTO $dto,
        FraudInspectionResult $result,
    ): UserDevice {
        return UserDevice::create([
            'user_id' => $user->id,
            'device_hash' => $dto->deviceHash,
            'ip_address' => $dto->ipAddress,
            'asn' => $result->asn,
            'country_code' => $result->countryCode ?? $user->country_code,
            'is_proxy' => $result->isProxy || $result->isVpn || $result->isTor || $result->isHosting,
            'is_emulator' => $result->isEmulator,
            'risk_score' => $result->riskScore,
            'user_agent' => $dto->userAgent,
        ]);
    }

    /**
     * Detect emulator signatures in User-Agent or device telemetry.
     */
    protected function detectEmulator(DeviceInspectionDTO $dto): bool
    {
        if ($dto->isEmulator) {
            return true;
        }

        if (empty($dto->userAgent)) {
            return false;
        }

        $ua = strtolower($dto->userAgent);
        $emulatorPatterns = [
            'bluestacks',
            'nox',
            'andy',
            'genymotion',
            'droid4x',
            'vbox86p',
            'sdk_gphone',
            'goldfish',
            'emulator',
            'qemu',
            'ttvm',
        ];

        foreach ($emulatorPatterns as $pattern) {
            if (str_contains($ua, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Query ProxyCheck.io / MaxMind or return local development mock data.
     */
    protected function queryNetworkIntelligence(string $ip): array
    {
        // Check for local loopback / private RFC1918 IPs
        if ($this->isLocalOrPrivateIp($ip)) {
            return [
                'isProxy' => false,
                'isVpn' => false,
                'isTor' => false,
                'isHosting' => false,
                'riskScore' => 0,
                'countryCode' => 'US',
                'asn' => 'AS0000 Localhost',
            ];
        }

        // 1. Query ProxyCheck.io if API key configured
        if (!empty($this->proxycheckApiKey) && !str_starts_with($this->proxycheckApiKey, 'your_')) {
            try {
                $response = Http::timeout(3)->get("https://proxycheck.io/v2/{$ip}", [
                    'key' => $this->proxycheckApiKey,
                    'vpn' => 1,
                    'asn' => 1,
                    'risk' => 1,
                ]);

                if ($response->successful()) {
                    $data = $response->json($ip) ?? [];
                    $isProxy = ($data['proxy'] ?? 'no') === 'yes';
                    $type = strtoupper((string) ($data['type'] ?? ''));
                    $risk = (int) ($data['risk'] ?? 0);

                    return [
                        'isProxy' => $isProxy,
                        'isVpn' => $type === 'VPN',
                        'isTor' => $type === 'TOR',
                        'isHosting' => $type === 'HOSTING' || $type === 'DATACENTER',
                        'riskScore' => $risk,
                        'countryCode' => $data['isocode'] ?? 'US',
                        'asn' => $data['asn'] ?? null,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning("ProxyCheck.io query failed for IP {$ip}: " . $e->getMessage());
            }
        }

        // 2. Query MaxMind minFraud / GeoIP2 if configured
        if (!empty($this->maxmindLicenseKey) && !str_starts_with($this->maxmindLicenseKey, 'your_')) {
            try {
                $response = Http::withBasicAuth((string) $this->maxmindAccountId, $this->maxmindLicenseKey)
                    ->timeout(3)
                    ->get("https://geoip.maxmind.com/geoip/v2.1/insights/{$ip}");

                if ($response->successful()) {
                    $data = $response->json();
                    $traits = $data['traits'] ?? [];

                    return [
                        'isProxy' => (bool) ($traits['is_anonymous_proxy'] ?? false),
                        'isVpn' => (bool) ($traits['is_anonymous_vpn'] ?? false),
                        'isTor' => (bool) ($traits['is_tor_exit_node'] ?? false),
                        'isHosting' => (bool) ($traits['is_hosting_provider'] ?? false),
                        'riskScore' => (int) ($traits['risk_score'] ?? 0),
                        'countryCode' => $data['country']['iso_code'] ?? 'US',
                        'asn' => isset($traits['autonomous_system_number']) ? 'AS' . $traits['autonomous_system_number'] : null,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning("MaxMind query failed for IP {$ip}: " . $e->getMessage());
            }
        }

        // Default mock / clean development fallback
        return [
            'isProxy' => false,
            'isVpn' => false,
            'isTor' => false,
            'isHosting' => false,
            'riskScore' => 0,
            'countryCode' => 'US',
            'asn' => 'AS15169 Google LLC',
        ];
    }

    /**
     * Check if IP is loopback or private network.
     */
    protected function isLocalOrPrivateIp(string $ip): bool
    {
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost', '0.0.0.0'], true)) {
            return true;
        }

        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
