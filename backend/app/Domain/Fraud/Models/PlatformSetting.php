<?php

declare(strict_types=1);

namespace App\Domain\Fraud\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformSetting extends Model
{
    use HasFactory;

    protected $table = 'platform_settings';

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    /**
     * Get a setting value by key with cache.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            return Cache::remember("platform_setting_{$key}", 300, function () use ($key, $default) {
                $setting = self::where('key', $key)->first();
                return $setting ? $setting->value : $default;
            });
        } catch (\Throwable) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        }
    }

    /**
     * Get a setting as integer.
     */
    public static function getInt(string $key, int $default = 0): int
    {
        $val = self::get($key, $default);
        return (int) $val;
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, mixed $value, ?string $description = null): void
    {
        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => (string) $value,
                'description' => $description,
            ]
        );
        try {
            Cache::forget("platform_setting_{$key}");
        } catch (\Throwable) {
            // cache offline
        }
    }
}
