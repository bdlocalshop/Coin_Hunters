<?php

declare(strict_types=1);

namespace App\Domain\Offerwalls\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfferwallProvider extends Model
{
    use HasFactory;

    protected $table = 'offerwall_providers';

    protected $fillable = [
        'slug',
        'name',
        'logo_url',
        'payout_margin_percent',
        'bonus_multiplier',
        'allowed_ips',
        'secret_key',
        'api_key',
        'app_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'payout_margin_percent' => 'integer',
            'bonus_multiplier' => 'float',
            'allowed_ips' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class, 'provider_slug', 'slug');
    }
}
