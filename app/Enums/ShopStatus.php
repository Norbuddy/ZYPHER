<?php

namespace App\Enums;

enum ShopStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Suspended = 'suspended';
    case Rejected = 'rejected';
}
