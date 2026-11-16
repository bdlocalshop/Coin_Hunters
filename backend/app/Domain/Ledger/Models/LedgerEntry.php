<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Models;

use App\Domain\Ledger\Enums\EntryDirection;
use App\Domain\Ledger\Exceptions\ImmutableRecordException;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends Model
{
    use HasFactory;

    protected $table = 'ledger_entries';

    public $timestamps = false;

    protected $fillable = [
        'ledger_transaction_id',
        'ledger_account_id',
        'user_id',
        'direction',
        'amount',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'direction' => EntryDirection::class,
            'amount' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Enforce strict immutability on lifecycle hooks.
     */
    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new ImmutableRecordException('Ledger entries are strictly append-only and cannot be updated.');
        });

        static::deleting(function (): void {
            throw new ImmutableRecordException('Ledger entries are strictly append-only and cannot be deleted.');
        });
    }

    /**
     * Explicitly reject update calls.
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new ImmutableRecordException('Ledger entries are strictly append-only and cannot be updated.');
    }

    /**
     * Explicitly reject delete calls.
     */
    public function delete(): ?bool
    {
        throw new ImmutableRecordException('Ledger entries are strictly append-only and cannot be deleted.');
    }

    /**
     * Parent transaction header.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(LedgerTransaction::class, 'ledger_transaction_id');
    }

    /**
     * Target ledger chart of accounts item.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'ledger_account_id');
    }

    /**
     * User associated with this balance movement, if applicable.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
