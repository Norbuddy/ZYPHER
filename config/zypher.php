<?php

return [
    // Default commission rate (%) applied to a shop when it's approved, if not overridden.
    'default_commission_rate' => env('ZYPHER_DEFAULT_COMMISSION_RATE', 5.00),

    // Minutes a checkout has to complete payment before its stock reservation is released.
    'stock_reservation_minutes' => env('ZYPHER_STOCK_RESERVATION_MINUTES', 30),

    // Days after shipping before an order auto-completes if the customer doesn't confirm.
    'auto_complete_days' => env('ZYPHER_AUTO_COMPLETE_DAYS', 7),
];
