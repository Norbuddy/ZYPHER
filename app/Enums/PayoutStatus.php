<?php

namespace App\Enums;

enum PayoutStatus: string
{
    case Unpaid = 'unpaid';
    case Processing = 'processing';
    case PaidOut = 'paid_out';
}
