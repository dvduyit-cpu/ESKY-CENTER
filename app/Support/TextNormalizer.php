<?php

namespace App\Support;

use Illuminate\Support\Str;

class TextNormalizer
{
    public static function exactName(?string $value): string
    {
        return Str::of((string) $value)->trim()->lower()
            ->replaceMatches('/[^\pL\pN]+/u', ' ')->squish()->toString();
    }

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

    public static function phone(mixed $value): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) $value) ?? '';
        if (str_starts_with($phone, '0084')) {
            $phone = substr($phone, 2);
        }
        if (str_starts_with($phone, '84') && strlen($phone) >= 10) {
            $phone = '0'.substr($phone, 2);
        }

        return $phone === '' ? null : $phone;
    }
}
