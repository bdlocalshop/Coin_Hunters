<?php

declare(strict_types=1);

namespace App\Domain\Payouts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CryptoPayoutMethod extends Model
{
    use HasFactory;

    protected $table = 'crypto_payout_methods';

    protected $fillable = [
        'symbol',
        'name',
        'network',
        'icon_url',
        'min_coins',
        'fee_coins',
        'address_regex',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_coins' => 'integer',
            'fee_coins' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class);
    }
}
