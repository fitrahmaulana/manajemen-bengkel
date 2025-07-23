<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasColor, HasLabel
{
    case UNPAID = 'unpaid';
    case PARTIAL = 'partial';
    case PAID = 'paid';

    public function getLabel(): string
    {
        return match ($this) {
            self::UNPAID => 'Belum Dibayar',
            self::PARTIAL => 'Sebagian Dibayar',
            self::PAID => 'Lunas',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::UNPAID => 'danger',
            self::PARTIAL => 'warning',
            self::PAID => 'success',
        };
    }
}
