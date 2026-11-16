<?php

declare(strict_types=1);

namespace App\Domain\Offerwalls\Models;

use App\Domain\Ledger\Models\LedgerTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferHold extends Model
{
    use HasFactory;

    protected $table = 'offer_holds';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'ledger_transaction_id',
        'offer_id',
        'provider_slug',
        'external_transaction_id',
        'coins',
        'release_at',
        'status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'coins' => 'integer',
            'release_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(LedgerTransaction::class, 'ledger_transaction_id');
    }
}
