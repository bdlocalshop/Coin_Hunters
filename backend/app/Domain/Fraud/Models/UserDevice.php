<?php

declare(strict_types=1);

namespace App\Domain\Fraud\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDevice extends Model
{
    use HasFactory;

    protected $table = 'user_devices';

    protected $fillable = [
        'user_id',
        'device_hash',
        'ip_address',
        'asn',
        'country_code',
        'is_proxy',
        'is_emulator',
        'risk_score',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'is_proxy' => 'boolean',
            'is_emulator' => 'boolean',
            'risk_score' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
