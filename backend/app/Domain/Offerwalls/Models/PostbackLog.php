<?php

declare(strict_types=1);

namespace App\Domain\Offerwalls\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostbackLog extends Model
{
    use HasFactory;

    protected $table = 'postback_logs';

    public $timestamps = false;

    protected $fillable = [
        'provider_slug',
        'external_transaction_id',
        'ip_address',
        'headers',
        'payload',
        'status',
        'message',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
