<?php

namespace App\Enums;

enum VoucherDiscountType: string
{
    case Percentage = 'percentage';
    case FixedAmount = 'fixed_amount';
}
