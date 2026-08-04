<?php

namespace App\Support;

use App\Models\LanguageDiscountPolicy;

class LanguageDiscountResolver
{
    public static function highest(
        ?LanguageDiscountPolicy $classDiscount,
        ?LanguageDiscountPolicy $studentDiscount
    ): ?LanguageDiscountPolicy {
        if (! $classDiscount) {
            return $studentDiscount;
        }
        if (! $studentDiscount) {
            return $classDiscount;
        }

        return (float) $studentDiscount->percentage >= (float) $classDiscount->percentage
            ? $studentDiscount
            : $classDiscount;
    }
}
