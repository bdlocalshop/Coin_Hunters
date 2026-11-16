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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username', 50)->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('avatar_url', 2048)->nullable();
            $table->string('referral_code', 32)->unique();
            $table->foreignId('referred_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('referral_bonus_paid')->default(false);
            $table->boolean('signup_bonus_paid')->default(false);
            $table->unsignedBigInteger('available_coins')->default(0);
            $table->unsignedBigInteger('pending_coins')->default(0);
            $table->unsignedBigInteger('locked_withdrawal_coins')->default(0);
            $table->unsignedBigInteger('lifetime_earned_coins')->default(0);
            $table->boolean('is_admin')->default(false);
            $table->string('status', 20)->default('active'); // active | suspended | banned
            $table->char('country_code', 2)->nullable();
            $table->unsignedInteger('fraud_score')->default(0);
            $table->rememberToken();
            $table->timestamps();

            $table->index(['status', 'country_code']);
            $table->index('fraud_score');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
