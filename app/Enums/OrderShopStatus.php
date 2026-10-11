<?php

namespace App\Enums;

enum OrderShopStatus: string
{
    case PendingPayment = 'pending_payment';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case RefundRequested = 'refund_requested';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Pending Payment',
            self::Processing => 'Processing',
            self::Shipped => 'Shipped',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::RefundRequested => 'Refund Requested',
            self::Refunded => 'Refunded',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingPayment, self::Processing => 'amber',
            self::Shipped => 'blue',
            self::Completed => 'green',
            self::Cancelled, self::RefundRequested, self::Refunded => 'red',
        };
    }
}
