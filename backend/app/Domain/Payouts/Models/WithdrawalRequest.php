<?php

declare(strict_types=1);

namespace App\Domain\Payouts\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalRequest extends Model
{
    use HasFactory;

    protected $table = 'withdrawal_requests';

    protected $fillable = [
        'uuid',
        'user_id',
        'crypto_payout_method_id',
        'coins_amount',
        'usd_amount',
        'wallet_address',
        'status',
        'tx_hash',
        'rejection_reason',
        'reviewed_by_admin_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'coins_amount' => 'integer',
            'usd_amount' => 'float',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payoutMethod(): BelongsTo
    {
        return $this->belongsTo(CryptoPayoutMethod::class, 'crypto_payout_method_id');
    }

    public function reviewedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_admin_id');
    }
}
