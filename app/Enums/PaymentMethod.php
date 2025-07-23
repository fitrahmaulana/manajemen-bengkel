<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case CASH = 'cash';
    case TRANSFER = 'transfer';
    case QRIS = 'qris';

    public function getLabel(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::TRANSFER => 'Bank Transfer',
            self::QRIS => 'QRIS',
        };
    }
}
