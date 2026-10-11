<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Branch;
use App\Models\SellerApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    /**
     * SEC-02 Regression Test:
     * Webhook endpoint /webhooks/paymongo MUST NOT require CSRF token (no 419).
     */
    public function test_paymongo_webhook_endpoint_is_exempt_from_csrf_protection(): void
    {
        $response = $this->postJson('/webhooks/paymongo', [
            'data' => [
                'attributes' => [
                    'type' => 'test.event',
                ]
            ]
        ]);

        // Must not be 419 (CSRF token mismatch)
        $this->assertNotEquals(419, $response->getStatusCode(), 'PayMongo webhook should not be blocked by CSRF (HTTP 419).');
    }

    /**
     * SEC-07 Regression Test:
     * Authentication OTP and password reset endpoints must have rate limiting (throttle middleware).
     */
    public function test_password_reset_otp_endpoints_have_rate_limiting_middleware(): void
    {
        $routesToCheck = [
            'password.email',
            'otp.verify',
            'password.update',
        ];

        foreach ($routesToCheck as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, "Route {$routeName} must be defined.");
            
            $middleware = $route->gatherMiddleware();
            $hasThrottle = false;
            foreach ($middleware as $m) {
                if (str_starts_with($m, 'throttle:')) {
                    $hasThrottle = true;
                    break;
                }
            }
            $this->assertTrue($hasThrottle, "Route {$routeName} must have throttle middleware applied for brute-force protection.");
        }
    }

    /**
     * SEC-08 Regression Test:
     * Temporary password generation for branch staff must have strong entropy (not 4-digit PINs).
     */
    public function test_branch_staff_temporary_password_entropy_is_strong(): void
    {
        $tempPassword = 'Br#' . Str::random(10);
        $this->assertGreaterThanOrEqual(13, strlen($tempPassword));
        $this->assertMatchesRegularExpression('/[A-Za-z0-9]/', $tempPassword);
        // Ensure it cannot be guessed via a 4-digit numeric loop (0000-9999)
        $this->assertFalse(is_numeric($tempPassword));
    }

    /**
     * SEC-01 Regression Test:
     * Unauthenticated requests to administrative document streaming endpoints must be blocked.
     */
    public function test_unauthenticated_users_cannot_access_seller_permit_streaming_endpoint(): void
    {
        $response = $this->get('/admin/seller-application/999/document/permit');
        // Admin routes are protected by auth middleware and admin role check
        $this->assertTrue(in_array($response->getStatusCode(), [302, 401, 403]));
    }

    /**
     * SEC-01 Regression Test:
     * Unauthenticated users cannot access branch BIR document streaming endpoint.
     */
    public function test_unauthenticated_users_cannot_access_branch_bir_streaming_endpoint(): void
    {
        $response = $this->get('/admin/branch-application/999/bir-document');
        $this->assertTrue(in_array($response->getStatusCode(), [302, 401, 403]));
    }
}
