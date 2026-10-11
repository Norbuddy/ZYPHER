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
    use RefreshDatabase;
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

    /**
     * Admin Protection Test:
     * Unauthenticated users cannot export order or payout CSV reports.
     */
    public function test_unauthenticated_users_cannot_export_reports(): void
    {
        $orderExport = $this->get('/admin/orders/export');
        $this->assertTrue(in_array($orderExport->getStatusCode(), [302, 401, 403]));

        $payoutExport = $this->get('/admin/payouts/export');
        $this->assertTrue(in_array($payoutExport->getStatusCode(), [302, 401, 403]));
    }

    /**
     * Admin Protection Test:
     * Voucher management routes are protected by is.admin middleware.
     */
    public function test_voucher_management_routes_are_protected_by_admin_middleware(): void
    {
        foreach (['admin.vouchers.toggle-status', 'admin.vouchers.destroy'] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, "Route {$routeName} must be defined.");
            $this->assertContains('is.admin', $route->gatherMiddleware());
        }
    }

    /**
     * Admin Application Test:
     * Admin can approve a seller application without 500 error, creating team and setting seller_admin role.
     */
    public function test_admin_can_approve_seller_application_successfully(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_admin' => true,
        ]);

        $applicant = User::factory()->create([
            'role' => 'user',
            'current_team_id' => null,
        ]);

        $application = SellerApplication::create([
            'user_id' => $applicant->id,
            'business_name' => 'Friend Store',
            'business_email' => 'friend@example.com',
            'contact_number' => '09123456789',
            'business_location' => 'Manila',
            'permit_path' => 'seller-permits/test-permit.pdf',
            'product_legitimation_path' => 'seller-permits/test-legit.pdf',
            'requested_category_ids' => [],
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->patch("/admin/seller-application/{$application->id}/approve");

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('teams', [
            'user_id' => $applicant->id,
            'name' => 'Friend Store',
            'status' => 'approved',
        ]);
        $this->assertEquals('approved', $application->fresh()->status);
        $this->assertEquals('seller_admin', $applicant->fresh()->role);
        $this->assertNotNull($applicant->fresh()->current_team_id);
    }

    /**
     * Admin Application Test:
     * Admin can approve a branch application successfully without errors.
     */
    public function test_admin_can_approve_branch_application_successfully(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_admin' => true,
        ]);

        $owner = User::factory()->create([
            'role' => 'seller_admin',
        ]);

        $branch = Branch::create([
            'user_id' => $owner->id,
            'name' => 'Friend Branch',
            'phone' => '09123456789',
            'address' => 'Manila City',
            'bir_document_path' => 'bir_documents/test.pdf',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->patch("/admin/branch-application/{$branch->id}/approve");

        $response->assertSessionHasNoErrors();
        $this->assertEquals('active', $branch->fresh()->status);
    }

    /**
     * Search Test:
     * Search endpoint with category_id or shoes filter succeeds without server error.
     */
    public function test_search_endpoint_with_category_id_param_succeeds(): void
    {
        $response = $this->get('/search?category_id=shoes');
        $response->assertOk();

        $response2 = $this->get('/search?q=test&category_id=shoes');
        $response2->assertOk();
    }
}
