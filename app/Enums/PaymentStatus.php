<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';
}
