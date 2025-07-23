<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DiscountType: string implements HasLabel
{
    case FIXED = 'fixed';
    case PERCENTAGE = 'percentage';

    public function getLabel(): string
    {
        return match ($this) {
            self::FIXED => 'Nominal (Rp)',
            self::PERCENTAGE => 'Persen (%)',
        };
    }
}
