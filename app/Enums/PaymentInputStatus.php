<?php

namespace App\Enums;

enum PaymentInputStatus: string
{
    case OVERPAID = 'overpaid';
    case EXACT = 'exact';
    case UNDERPAID = 'underpaid';
}
