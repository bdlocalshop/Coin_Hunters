<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\Ledger\Models\LedgerEntry;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'avatar_url',
        'referral_code',
        'referred_by_id',
        'referral_bonus_paid',
        'signup_bonus_paid',
        'available_coins',
        'pending_coins',
        'locked_withdrawal_coins',
        'lifetime_earned_coins',
        'is_admin',
        'status',
        'country_code',
        'fraud_score',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'referral_bonus_paid' => 'boolean',
            'signup_bonus_paid' => 'boolean',
            'available_coins' => 'integer',
            'pending_coins' => 'integer',
            'locked_withdrawal_coins' => 'integer',
            'lifetime_earned_coins' => 'integer',
            'fraud_score' => 'integer',
            'referred_by_id' => 'integer',
        ];
    }

    /**
     * Determine if user has permission to access Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin
            || str_ends_with($this->email, '@coinhunters.local')
            || $this->email === 'admin@coinhunters.com';
    }

    /**
     * Referrer of this user.
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_id');
    }

    /**
     * Users referred by this user.
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by_id');
    }

    /**
     * Devices registered to this user.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(\App\Domain\Fraud\Models\UserDevice::class);
    }

    /**
     * Ledger entries attributed to this user.
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
