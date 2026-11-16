<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Fraud\DTOs\DeviceInspectionDTO;
use App\Domain\Fraud\Models\PlatformSetting;
use App\Domain\Fraud\Services\FraudGuardService;
use App\Domain\Ledger\Enums\TransactionType;
use App\Domain\Ledger\Services\LedgerService;
use App\Domain\Referrals\Services\ReferralService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private readonly FraudGuardService $fraudGuard,
        private readonly LedgerService $ledgerService,
        private readonly ReferralService $referralService
    ) {}

    /**
     * Register a new user with anti-fraud screening and instant welcome bonus.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $deviceDto = new DeviceInspectionDTO(
            ipAddress: $request->ip() ?: '127.0.0.1',
            deviceHash: (string) $request->validated('device_hash'),
            userAgent: $request->userAgent(),
            isWebdriver: (bool) $request->validated('is_webdriver', false),
            isEmulator: (bool) $request->validated('is_emulator', false),
            screenResolution: $request->validated('screen_resolution'),
            timezone: $request->validated('timezone'),
        );

        // 1. Mandatory Fraud Screening (throws FraudDetectedException if blocked)
        $inspection = $this->fraudGuard->inspectAndEnforce($deviceDto, isRegistration: true);

        // 2. Resolve Referrer if code supplied
        $referrer = $this->referralService->resolveReferrerByCode($request->validated('referral_code'));

        // 3. Create user
        $referralCode = $this->referralService->generateUniqueReferralCode();
        $username = strtolower((string) $request->validated('username'));

        $user = User::create([
            'name' => $request->validated('name'),
            'username' => $username,
            'email' => strtolower((string) $request->validated('email')),
            'password' => Hash::make((string) $request->validated('password')),
            'avatar_url' => 'https://api.dicebear.com/7.x/bottts/svg?seed=' . urlencode($username),
            'referral_code' => $referralCode,
            'referred_by_id' => $referrer?->id,
            'status' => 'active',
            'country_code' => $inspection->countryCode ?? 'US',
            'fraud_score' => $inspection->riskScore,
        ]);

        // 4. Record device fingerprint telemetry
        $this->fraudGuard->recordDevice($user, $deviceDto, $inspection);

        // 5. Credit Signup Welcome Bonus via Double-Entry Ledger
        $signupBonusCoins = PlatformSetting::getInt('signup_bonus_coins', 250);
        if ($signupBonusCoins > 0) {
            $this->ledgerService->creditBonus(
                user: $user,
                coins: $signupBonusCoins,
                type: TransactionType::SIGNUP_BONUS,
                idempotencyKey: 'signup_bonus_user_' . $user->id,
                description: sprintf('Welcome signup bonus (%d Coins)', $signupBonusCoins),
                metadata: [
                    'ip' => $deviceDto->ipAddress,
                    'device_hash' => $deviceDto->deviceHash,
                ]
            );

            $user->signup_bonus_paid = true;
            $user->save();
        }

        // 6. Issue Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful.',
            'user' => new UserResource($user->fresh()),
            'token' => $token,
            'signup_bonus_coins' => $signupBonusCoins,
        ], 201);
    }

    /**
     * Authenticate an existing user.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $login = strtolower((string) $request->validated('login'));
        $password = (string) $request->validated('password');

        $user = User::where('email', $login)
            ->orWhere('username', $login)
            ->first();

        if ($user === null || !Hash::check($password, $user->password)) {
            return response()->json([
                'message' => 'The provided credentials do not match our records.',
            ], 401);
        }

        if ($user->status === 'banned') {
            return response()->json([
                'message' => 'This account has been banned due to security and policy violations.',
            ], 403);
        }

        // Run anti-fraud inspection if device hash provided
        $deviceHash = $request->validated('device_hash');
        if (!empty($deviceHash)) {
            $deviceDto = new DeviceInspectionDTO(
                ipAddress: $request->ip() ?: '127.0.0.1',
                deviceHash: (string) $deviceHash,
                userAgent: $request->userAgent(),
                isWebdriver: (bool) $request->validated('is_webdriver', false),
                isEmulator: (bool) $request->validated('is_emulator', false),
            );

            $inspection = $this->fraudGuard->inspectAndEnforce($deviceDto, currentUserId: $user->id, isRegistration: false);
            $this->fraudGuard->recordDevice($user, $deviceDto, $inspection);
        }

        // Generate Sanctum access token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user' => new UserResource($user->fresh()),
            'token' => $token,
        ]);
    }

    /**
     * Terminate the authenticated user's current session token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Get the authenticated user's profile and live balance projections.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()->fresh()),
        ]);
    }

    /**
     * Get the authenticated user's referral stats and referred users list.
     */
    public function referrals(Request $request): JsonResponse
    {
        $user = $request->user();
        $qualificationThreshold = PlatformSetting::getInt('referral_qualification_coins', 1000);
        $referralBonus = PlatformSetting::getInt('referral_bonus_coins', 500);

        $referrals = $user->referrals()
            ->select(['id', 'username', 'created_at', 'lifetime_earned_coins', 'referral_bonus_paid'])
            ->latest()
            ->get();

        $totalCount = $referrals->count();
        $qualifiedCount = $referrals->filter(
            fn ($ref) => $ref->referral_bonus_paid || $ref->lifetime_earned_coins >= $qualificationThreshold
        )->count();

        $lifetimeCoins = $qualifiedCount * $referralBonus;

        return response()->json([
            'referral_code' => $user->referral_code,
            'stats' => [
                'total_referrals' => $totalCount,
                'qualified_referrals' => $qualifiedCount,
                'pending_referrals' => $totalCount - $qualifiedCount,
                'lifetime_referral_coins' => $lifetimeCoins,
                'qualification_coins' => $qualificationThreshold,
                'bonus_per_qualified' => $referralBonus,
            ],
            'referred_users' => $referrals->map(function ($ref) use ($qualificationThreshold) {
                return [
                    'id' => $ref->id,
                    'username' => substr($ref->username, 0, 3) . '***',
                    'joined_at' => $ref->created_at->toIso8601String(),
                    'lifetime_earned_coins' => $ref->lifetime_earned_coins,
                    'is_qualified' => $ref->referral_bonus_paid || $ref->lifetime_earned_coins >= $qualificationThreshold,
                    'bonus_paid' => (bool) $ref->referral_bonus_paid,
                ];
            }),
        ]);
    }
}
