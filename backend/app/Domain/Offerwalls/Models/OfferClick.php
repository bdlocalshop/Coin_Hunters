<?php

declare(strict_types=1);

namespace App\Domain\Offerwalls\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferClick extends Model
{
    use HasFactory;

    protected $table = 'offer_clicks';

    public $timestamps = false;

    protected $fillable = [
        'click_uuid',
        'user_id',
        'offer_id',
        'provider_slug',
        'ip_address',
        'device_hash',
        'status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
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
}
