<?php

return [
    'secret_key' => env('PAYMONGO_SECRET_KEY'),
    'public_key' => env('PAYMONGO_PUBLIC_KEY'),
    'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),
    'test_mode' => filter_var(env('PAYMONGO_TEST_MODE', true), FILTER_VALIDATE_BOOLEAN),
    'demo_auto_confirm' => filter_var(env('PAYMONGO_DEMO_AUTO_CONFIRM', false), FILTER_VALIDATE_BOOLEAN),
    'payment_methods' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('PAYMONGO_PAYMENT_METHODS', 'card,gcash,paymaya,grab_pay,qrph'))
    ))),
    'base_url' => env('PAYMONGO_BASE_URL', 'https://api.paymongo.com'),
];
