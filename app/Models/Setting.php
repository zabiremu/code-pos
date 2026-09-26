<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Key/value store behind Admin > Settings. Values are cached as one array
 * and the cache is dropped on every write. Keys listed in ENCRYPTED are
 * stored encrypted with APP_KEY (the SMTP password).
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public const CACHE_KEY = 'app_settings';

    public const ENCRYPTED = ['mail_password'];

    /** @return array<string, string|null> */
    public static function allValues(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
        } catch (Throwable) {
            // No database yet (fresh install) or the settings table hasn't
            // been migrated: behave as if nothing is set.
            return [];
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::allValues()[$key] ?? null;

        if ($value === null || $value === '') {
            return $default;
        }

        if (in_array($key, self::ENCRYPTED, true)) {
            try {
                return Crypt::decryptString($value);
            } catch (Throwable) {
                return $default; // APP_KEY changed since it was saved
            }
        }

        return $value;
    }

    /** @param array<string, mixed> $values */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            if ($value !== null && $value !== '' && in_array($key, self::ENCRYPTED, true)) {
                $value = Crypt::encryptString((string) $value);
            }

            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }
}
