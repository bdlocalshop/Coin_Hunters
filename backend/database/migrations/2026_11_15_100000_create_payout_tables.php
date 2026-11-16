<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. crypto_payout_methods
        Schema::create('crypto_payout_methods', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 32)->unique(); // LTC, USDT_TRC20, USDT_BEP20, BTC, SOL
            $table->string('name', 128);
            $table->string('network', 64);
            $table->string('icon_url', 2048)->nullable();
            $table->unsignedBigInteger('min_coins')->default(1000);
            $table->unsignedBigInteger('fee_coins')->default(0);
            $table->string('address_regex', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. withdrawal_requests
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('crypto_payout_method_id')->constrained('crypto_payout_methods')->restrictOnDelete();
            $table->unsignedBigInteger('coins_amount');
            $table->decimal('usd_amount', 10, 2);
            $table->string('wallet_address', 255);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('tx_hash', 255)->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
        Schema::dropIfExists('crypto_payout_methods');
    }
};
