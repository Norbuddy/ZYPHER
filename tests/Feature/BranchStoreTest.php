<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BranchStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_create_branch_with_bir_document(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $file = UploadedFile::fake()->create('bir-registration.pdf', 512, 'application/pdf');

        $response = $this->actingAs($user)->post(route('seller.branches.store'), [
            'name' => 'Main Branch',
            'phone' => '+63 9123456789',
            'region' => 'National Capital Region',
            'province' => 'Metro Manila',
            'city' => 'Makati City',
            'barangay' => 'Poblacion',
            'address' => '123 Test Street',
            'latitude' => 14.5995,
            'longitude' => 120.9842,
            'bir_document' => $file,
            'email' => 'main-staff@example.com',
            'password' => 'temporary-password',
            'password_confirmation' => 'temporary-password',
            'branch_role' => 'manager',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('branches', [
            'user_id' => $user->id,
            'name' => 'Main Branch',
            'phone' => '+63 9123456789',
            'region' => 'National Capital Region',
            'province' => 'Metro Manila',
            'city' => 'Makati City',
            'barangay' => 'Poblacion',
        ]);

        $branch = \App\Models\Branch::query()->where('user_id', $user->id)->first();

        $this->assertNotNull($branch);
        $this->assertTrue(Storage::disk('public')->exists($branch->bir_document_path));

        $this->assertDatabaseHas('users', [
            'email' => 'main-staff@example.com',
            'role' => 'branch_user',
            'branch_role' => 'manager',
            'branch_id' => $branch->id,
        ]);
        $this->assertTrue(Hash::check('temporary-password', User::where('email', 'main-staff@example.com')->value('password')));
    }

    public function test_authorized_users_can_view_bir_document_inline(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create(['role' => 'seller_admin']);
        $branch = Branch::create([
            'user_id' => $owner->id,
            'name' => 'Main Branch',
            'phone' => '09123456789',
            'address' => '123 Test Street',
            'bir_document_path' => 'bir_documents/test.pdf',
        ]);
        Storage::disk('public')->put($branch->bir_document_path, '%PDF-1.4 test');

        $response = $this->actingAs($owner)->get(route('seller.branches.bir.view', $branch));

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'inline');
    }

    public function test_unrelated_users_cannot_view_bir_document(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $branch = Branch::create([
            'user_id' => $owner->id,
            'name' => 'Main Branch',
            'phone' => '09123456789',
            'address' => '123 Test Street',
            'bir_document_path' => 'bir_documents/test.pdf',
        ]);

        $this->actingAs($otherUser)
            ->get(route('seller.branches.bir.view', $branch))
            ->assertForbidden();
    }
}
