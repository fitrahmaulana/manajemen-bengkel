<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case UNPAID = 'unpaid';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';
    case PENDING = 'pending';

    public function getLabel(): string
    {
        return match ($this) {
            self::UNPAID => 'Belum Dibayar',
            self::PARTIALLY_PAID => 'Sebagian Dibayar',
            self::PAID => 'Lunas',
            self::OVERDUE => 'Jatuh Tempo',
            self::CANCELLED => 'Dibatalkan',
            self::PENDING => 'Pending',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::UNPAID => 'gray',
            self::PARTIALLY_PAID => 'info',
            self::PAID => 'success',
            self::OVERDUE => 'danger',
            self::CANCELLED => 'warning',
        };
    }
}
