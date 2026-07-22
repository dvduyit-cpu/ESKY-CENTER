<?php

namespace App\Support;

use Illuminate\Support\Str;

class TextNormalizer
{
    public static function name(?string $value): string
    {
        return Str::of((string) $value)->trim()->lower()->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();
    }

    public static function header(?string $value): string
    {
        return Str::of((string) $value)->trim()->upper()->ascii()
            ->replaceMatches('/[^A-Z0-9]+/', ' ')->squish()->toString();
    }
}
