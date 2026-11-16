<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OfferController;
use App\Http\Controllers\Api\V1\PostbackWebhookController;
use App\Http\Controllers\Api\V1\PublicStatsController;
use App\Http\Controllers\Api\V1\ShopController;
use App\Http\Middleware\VerifyProviderIpMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // 1. S2S Postback Webhook Endpoint (Protected by IP allowlist middleware)
    Route::any('/postbacks/{provider}', [PostbackWebhookController::class, 'handle'])
        ->middleware(VerifyProviderIpMiddleware::class);

    // 2. Public Platform Metrics & Leaderboard
    Route::get('/public/stats', [PublicStatsController::class, 'stats']);
    Route::get('/leaderboard', [PublicStatsController::class, 'leaderboard']);
    Route::get('/shop/methods', [ShopController::class, 'methods']);

    // 3. Authentication Routes
    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    // 4. Offerwall & Catalog Public Feed Routes
    Route::get('/offers', [OfferController::class, 'index']);
    Route::get('/surveys', [OfferController::class, 'surveys']);
    Route::get('/offer-walls', [OfferController::class, 'offerwalls']);

    // 5. Authenticated Actions & Tracking
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/referrals', [AuthController::class, 'referrals']);
        Route::get('/my-offers', [OfferController::class, 'myOffers']);
        Route::post('/offers/{offer}/click', [OfferController::class, 'click'])->middleware('throttle:20,1');

        // Crypto Shop Cashout Routes
        Route::post('/shop/withdraw', [ShopController::class, 'withdraw']);
        Route::get('/shop/withdrawals', [ShopController::class, 'withdrawals']);
    });
});
