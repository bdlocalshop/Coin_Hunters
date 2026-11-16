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
        // 1. offerwall_providers
        Schema::create('offerwall_providers', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name', 128);
            $table->string('logo_url', 2048)->nullable();
            $table->unsignedTinyInteger('payout_margin_percent')->default(70);
            $table->decimal('bonus_multiplier', 4, 2)->default(1.00);
            $table->json('allowed_ips')->nullable();
            $table->string('secret_key', 255)->nullable();
            $table->string('api_key', 255)->nullable();
            $table->string('app_id', 128)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. offers
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('provider_slug', 64)->index();
            $table->string('external_offer_id', 128)->index();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->json('requirements')->nullable();
            $table->string('icon_url', 2048)->nullable();
            $table->decimal('network_payout_usd', 10, 4)->default(0.0000);
            $table->unsignedBigInteger('user_payout_coins')->default(0);
            $table->json('device_targets')->nullable(); // ["android", "ios", "desktop"]
            $table->json('countries')->nullable(); // ["US", "GB", "ALL"]
            $table->json('categories')->nullable(); // ["games", "apps", "surveys", "finance"]
            $table->text('click_url_template');
            $table->decimal('epc_score', 8, 4)->default(0.0000);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['provider_slug', 'external_offer_id']);
            $table->index(['is_active', 'is_featured']);
            $table->index(['provider_slug', 'is_active']);
        });

        // 3. offer_clicks
        Schema::create('offer_clicks', function (Blueprint $table) {
            $table->id();
            $table->uuid('click_uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('offer_id')->nullable()->constrained('offers')->nullOnDelete();
            $table->string('provider_slug', 64)->index();
            $table->string('ip_address', 45);
            $table->string('device_hash', 128)->nullable();
            $table->enum('status', ['clicked', 'pending', 'completed', 'reversed'])->default('clicked');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'status']);
        });

        // 4. offer_holds (high-value conversion holding queue)
        Schema::create('offer_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ledger_transaction_id')->constrained('ledger_transactions')->cascadeOnDelete();
            $table->foreignId('offer_id')->nullable()->constrained('offers')->nullOnDelete();
            $table->string('provider_slug', 64)->index();
            $table->string('external_transaction_id', 128)->index();
            $table->unsignedBigInteger('coins');
            $table->timestamp('release_at');
            $table->enum('status', ['pending', 'released', 'reversed'])->default('pending');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['status', 'release_at']);
        });

        // 5. postback_logs
        Schema::create('postback_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider_slug', 64)->index();
            $table->string('external_transaction_id', 128)->nullable()->index();
            $table->string('ip_address', 45);
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->enum('status', [
                'credited',
                'held',
                'duplicate',
                'rejected_ip',
                'rejected_sig',
                'reversed',
                'error',
            ]);
            $table->string('message', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['provider_slug', 'external_transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('postback_logs');
        Schema::dropIfExists('offer_holds');
        Schema::dropIfExists('offer_clicks');
        Schema::dropIfExists('offers');
        Schema::dropIfExists('offerwall_providers');
    }
};
