<?php

declare(strict_types=1);

namespace App\Domain\Offerwalls\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offer extends Model
{
    use HasFactory;

    protected $table = 'offers';

    protected $fillable = [
        'provider_slug',
        'external_offer_id',
        'title',
        'description',
        'requirements',
        'icon_url',
        'network_payout_usd',
        'user_payout_coins',
        'device_targets',
        'countries',
        'categories',
        'click_url_template',
        'epc_score',
        'is_featured',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requirements' => 'array',
            'device_targets' => 'array',
            'countries' => 'array',
            'categories' => 'array',
            'network_payout_usd' => 'float',
            'user_payout_coins' => 'integer',
            'epc_score' => 'float',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(OfferwallProvider::class, 'provider_slug', 'slug');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(OfferClick::class);
    }
}
