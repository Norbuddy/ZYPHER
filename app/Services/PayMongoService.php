<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PosSale;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * PayMongo API client (test keys = no real money).
 *
 * Keys are read ONLY via config() so they work with config:cache.
 * After changing .env always run: php artisan config:clear
 *
 * @see https://docs.paymongo.com/docs/payment-acceptance-testing
 */
class PayMongoService
{
    protected string $secretKey;
    protected string $publicKey;
    protected string $baseUrl;
    protected bool $isTestMode;

    public function __construct()
    {
        // NEVER call env() here — when config is cached, env() is null outside config/*.php
        $this->secretKey = trim((string) config('paymongo.secret_key', config('services.paymongo.secret_key', '')));
        $this->publicKey = trim((string) config('paymongo.public_key', config('services.paymongo.public_key', '')));
        $this->isTestMode = str_starts_with($this->secretKey, 'sk_test_')
            || (bool) config('paymongo.test_mode', config('services.paymongo.test_mode', true));
        $this->baseUrl = rtrim((string) config('paymongo.base_url', 'https://api.paymongo.com'), '/');
    }

    public function isConfigured(): bool
    {
        $key = $this->secretKey;

        if ($key === '' || ! preg_match('/^sk_(test|live)_[A-Za-z0-9]+$/', $key)) {
            return false;
        }

        if (preg_match('/x{4,}|your_?real_?key|paste_/i', $key)) {
            return false;
        }

        if (strlen($key) < 25) {
            return false;
        }

        return true;
    }

    public function configurationError(): ?string
    {
        if ($this->isConfigured()) {
            return null;
        }

        $key = $this->secretKey;
        $fromConfig = (string) config('paymongo.secret_key', '');
        $fromServices = (string) config('services.paymongo.secret_key', '');

        if ($key === '' && $fromConfig === '' && $fromServices === '') {
            return 'PayMongo secret key is empty. Put PAYMONGO_SECRET_KEY=sk_test_… in the project .env (next to artisan), then run: php artisan config:clear';
        }

        if (preg_match('/x{4,}|your_?real_?key|paste_/i', $key) || strlen($key) < 25) {
            return 'PAYMONGO_SECRET_KEY still looks invalid or too short. Paste the full sk_test_ key from PayMongo → Settings → Developers → API Keys, then: php artisan config:clear';
        }

        if (! str_starts_with($key, 'sk_')) {
            return 'Secret key must start with sk_test_ or sk_live_.';
        }

        return 'PayMongo is not configured. Run: php artisan config:clear && php artisan config:show paymongo';
    }

    public function isTestMode(): bool
    {
        return $this->isTestMode;
    }

    public function publicKey(): string
    {
        return $this->publicKey;
    }

    /** Masked key for diagnostics (never logs full secret). */
    public function maskedSecret(): string
    {
        $key = $this->secretKey;
        if ($key === '') {
            return '(empty)';
        }
        if (strlen($key) < 12) {
            return $key[0].'***';
        }

        return substr($key, 0, 8).'…'.substr($key, -4).' (len='.strlen($key).')';
    }

    public function createCheckoutSession(
        Order $order,
        array $lineItems,
        string $successUrl,
        string $cancelUrl,
        ?array $preferredMethods = null
    ): array {
        $this->assertConfigured();

        $amountCentavos = (int) round(((float) $order->total_amount) * 100);

        $payload = [
            'data' => [
                'attributes' => [
                    'send_email_receipt' => false,
                    'show_description' => true,
                    'show_line_items' => true,
                    'description' => 'Zypher Order '.$order->order_number,
                    'line_items' => $lineItems ?: [[
                        'name' => 'Order '.$order->order_number,
                        'quantity' => 1,
                        'amount' => max(100, $amountCentavos),
                        'currency' => 'PHP',
                    ]],
                    'payment_method_types' => $preferredMethods
                        ?: config('paymongo.payment_methods', ['card', 'gcash', 'paymaya', 'grab_pay', 'qrph']),
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    'reference_number' => $order->order_number,
                    'metadata' => [
                        'order_id' => (string) $order->id,
                        'order_number' => $order->order_number,
                        'user_id' => (string) $order->user_id,
                        'environment' => $this->isTestMode ? 'test' : 'live',
                    ],
                ],
            ],
        ];

        return $this->createSession($payload);
    }

    public function createCheckoutSessionForPos(
        PosSale $sale,
        array $lineItems,
        array $paymentMethods,
        string $successUrl,
        string $cancelUrl
    ): array {
        $this->assertConfigured();

        $amountCentavos = (int) round(((float) $sale->total_amount) * 100);

        $payload = [
            'data' => [
                'attributes' => [
                    'send_email_receipt' => false,
                    'show_description' => true,
                    'show_line_items' => true,
                    'description' => 'POS Sale '.$sale->sale_number,
                    'line_items' => $lineItems ?: [[
                        'name' => 'POS '.$sale->sale_number,
                        'quantity' => 1,
                        'amount' => max(100, $amountCentavos),
                        'currency' => 'PHP',
                    ]],
                    'payment_method_types' => $paymentMethods ?: ['card', 'gcash', 'paymaya'],
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    'reference_number' => $sale->sale_number,
                    'metadata' => [
                        'pos_sale_id' => (string) $sale->id,
                        'sale_number' => $sale->sale_number,
                        'type' => 'pos',
                        'environment' => $this->isTestMode ? 'test' : 'live',
                    ],
                ],
            ],
        ];

        return $this->createSession($payload);
    }

    /**
     * Checkout session for seller restock / supplier orders.
     */
    public function createCheckoutSessionForSupplier(
        \App\Models\SupplierOrder $order,
        array $lineItems,
        string $successUrl,
        string $cancelUrl,
        ?array $preferredMethods = null
    ): array {
        $this->assertConfigured();

        $amountCentavos = (int) round(((float) $order->total_amount) * 100);

        $payload = [
            'data' => [
                'attributes' => [
                    'send_email_receipt' => false,
                    'show_description' => true,
                    'show_line_items' => true,
                    'description' => 'Supplier Order '.$order->order_number,
                    'line_items' => $lineItems ?: [[
                        'name' => 'Supplier Order '.$order->order_number,
                        'quantity' => 1,
                        'amount' => max(100, $amountCentavos),
                        'currency' => 'PHP',
                    ]],
                    'payment_method_types' => $preferredMethods
                        ?: config('paymongo.payment_methods', ['card', 'gcash', 'paymaya', 'grab_pay', 'qrph']),
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    'reference_number' => $order->order_number,
                    'metadata' => [
                        'supplier_order_id' => (string) $order->id,
                        'order_number' => $order->order_number,
                        'team_id' => (string) $order->team_id,
                        'type' => 'supplier_order',
                        'environment' => $this->isTestMode ? 'test' : 'live',
                    ],
                ],
            ],
        ];

        return $this->createSession($payload);
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        $this->assertConfigured();

        try {
            return $this->request('GET', '/v1/checkout_sessions/'.$sessionId);
        } catch (RuntimeException $e) {
            return $this->request('GET', '/v2/checkout_sessions/'.$sessionId);
        }
    }

    public function verifyWebhookSignature(string $payload, ?string $signatureHeader): bool
    {
        $webhookSecret = trim((string) config('paymongo.webhook_secret', ''));

        if ($webhookSecret === '' || $signatureHeader === null || $signatureHeader === '') {
            if (app()->environment('local')) {
                Log::warning('PayMongo webhook signature skipped in local environment (no secret configured).');

                return true;
            }

            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$k, $v] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($k && $v) {
                $parts[$k] = $v;
            }
        }

        $timestamp = $parts['t'] ?? '';
        $sig = $this->isTestMode ? ($parts['te'] ?? '') : ($parts['li'] ?? '');

        if ($timestamp === '' || $sig === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $webhookSecret);

        return hash_equals($expected, $sig);
    }

    protected function createSession(array $payload): array
    {
        try {
            $response = $this->request('POST', '/v1/checkout_sessions', $payload);
        } catch (RuntimeException $e) {
            Log::info('PayMongo v1 checkout failed, trying v2', ['error' => $e->getMessage()]);
            $response = $this->request('POST', '/v2/checkout_sessions', $payload);
        }

        $attrs = $response['data']['attributes'] ?? [];
        $checkoutUrl = $attrs['checkout_url'] ?? null;

        if (! $checkoutUrl) {
            Log::error('PayMongo checkout session missing URL', ['response' => $response]);
            throw new RuntimeException('PayMongo did not return a checkout URL. See storage/logs/laravel.log');
        }

        return [
            'id' => $response['data']['id'] ?? '',
            'checkout_url' => $checkoutUrl,
            'client_key' => $attrs['client_key'] ?? null,
            'raw' => $response,
        ];
    }

    protected function request(string $method, string $path, ?array $body = null): array
    {
        $http = Http::withBasicAuth($this->secretKey, '')
            ->acceptJson()
            ->asJson()
            ->timeout(30);

        $url = $this->baseUrl.$path;

        $response = match (strtoupper($method)) {
            'GET' => $http->get($url),
            'POST' => $http->post($url, $body ?? []),
            'PUT' => $http->put($url, $body ?? []),
            default => throw new RuntimeException("Unsupported HTTP method: {$method}"),
        };

        if ($response->failed()) {
            Log::error('PayMongo API error', [
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            $message = data_get($response->json(), 'errors.0.detail')
                ?? data_get($response->json(), 'errors.0.title')
                ?? 'PayMongo request failed (HTTP '.$response->status().').';

            throw new RuntimeException($message);
        }

        return $response->json() ?? [];
    }

    protected function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException($this->configurationError() ?? 'PayMongo is not configured.');
        }
    }
}
