<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Models;

use App\Domain\Ledger\Exceptions\ImmutableRecordException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LedgerTransaction extends Model
{
    use HasFactory;

    protected $table = 'ledger_transactions';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'parent_transaction_id',
        'type',
        'reference_type',
        'reference_id',
        'idempotency_key',
        'description',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Enforce strict immutability on lifecycle hooks.
     */
    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new ImmutableRecordException('Ledger transactions are strictly append-only and cannot be updated.');
        });

        static::deleting(function (): void {
            throw new ImmutableRecordException('Ledger transactions are strictly append-only and cannot be deleted.');
        });
    }

    /**
     * Explicitly reject update calls.
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new ImmutableRecordException('Ledger transactions are strictly append-only and cannot be updated.');
    }

    /**
     * Explicitly reject delete calls.
     */
    public function delete(): ?bool
    {
        throw new ImmutableRecordException('Ledger transactions are strictly append-only and cannot be deleted.');
    }

    /**
     * Balanced entry line items of this transaction.
     */
    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'ledger_transaction_id');
    }

    /**
     * Original transaction if this is a reversal or compensating entry.
     */
    public function parentTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_transaction_id');
    }

    /**
     * Compensating transactions referencing this parent transaction.
     */
    public function childTransactions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_transaction_id');
    }
}
