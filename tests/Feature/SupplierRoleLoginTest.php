<?php

namespace Tests\Feature;

use App\Enums\ShopStatus;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierRoleLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_user_is_redirected_to_supplier_center_after_login(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->forceFill(['is_supplier' => true])->save();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/supplier/dashboard');
    }

    public function test_seller_user_is_redirected_to_seller_center_after_login(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        /** @var Team|null $team */
        $team = $user->ownedTeams()->first();

        if (!$team instanceof Team) {
            $this->fail('Seller test user should have an owned team to promote.');
        }

        $team->forceFill([
            'status' => ShopStatus::Approved,
            'personal_team' => false,
            'user_id' => $user->id,
        ])->save();
        $user->forceFill(['current_team_id' => $team->id])->save();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/seller/dashboard');
    }
}
