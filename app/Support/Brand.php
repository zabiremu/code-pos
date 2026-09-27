<?php

namespace App\Support;

/** The product's own name, used whenever the shop hasn't set a real one. */
class Brand
{
    public const NAME = 'ShopPulse POS';

    /** Placeholder names from older versions / the Laravel skeleton - treated as "not set". */
    public const PLACEHOLDERS = ['', 'pos', 'restaurant pos', 'laravel', 'code-pos', 'restaurant_pos'];

    public static function isPlaceholder(?string $name): bool
    {
        return in_array(mb_strtolower(trim((string) $name)), self::PLACEHOLDERS, true);
    }

    /** The given name, unless it's empty or a leftover placeholder. */
    public static function resolve(?string $name): string
    {
        return self::isPlaceholder($name) ? self::NAME : trim((string) $name);
    }
}
