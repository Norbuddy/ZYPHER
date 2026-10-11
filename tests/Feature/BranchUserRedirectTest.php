<?php

namespace Tests\Feature;

use App\Http\Responses\LoginResponse;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class BranchUserRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_user_login_response_redirects_to_pos(): void
    {
        $user = User::factory()->create(['role' => 'branch_user']);
        $request = Request::create('/login', 'POST');
        $request->setUserResolver(fn () => $user);

        $response = (new LoginResponse())->toResponse($request);

        $this->assertTrue($response->isRedirect(route('seller.dashboard')));
    }

    public function test_branch_user_visiting_storefront_is_redirected_to_pos(): void
    {
        $user = User::factory()->create(['role' => 'branch_user']);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('seller.dashboard'));
    }

    public function test_approved_branch_user_can_access_the_seller_dashboard(): void
    {
        $owner = User::factory()->create(['role' => 'seller_admin']);
        Team::factory()->create([
            'user_id' => $owner->id,
            'personal_team' => false,
            'status' => 'approved',
        ]);
        $branch = Branch::create([
            'user_id' => $owner->id,
            'name' => 'Approved Branch',
            'phone' => '09123456789',
            'address' => '123 Test Street',
            'bir_document_path' => 'bir_documents/test.pdf',
            'status' => 'approved',
        ]);
        $staff = User::factory()->create([
            'role' => 'branch_user',
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($staff)
            ->get(route('seller.dashboard'))
            ->assertOk();
    }

    public function test_branch_pos_includes_seller_owned_variants(): void
    {
        $owner = User::factory()->create(['role' => 'seller_admin']);
        $team = Team::factory()->create([
            'user_id' => $owner->id,
            'personal_team' => false,
            'status' => 'approved',
        ]);
        $branch = Branch::create([
            'user_id' => $owner->id,
            'name' => 'Approved Branch',
            'phone' => '09123456789',
            'address' => '123 Test Street',
            'bir_document_path' => 'bir_documents/test.pdf',
            'status' => 'approved',
        ]);
        $staff = User::factory()->create([
            'role' => 'branch_user',
            'branch_id' => $branch->id,
        ]);
        $category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $product = Product::create([
            'team_id' => $team->id,
            'category_id' => $category->id,
            'name' => 'Seller POS Item',
            'slug' => 'seller-pos-item',
            'status' => 'active',
            'pos_available' => false,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SELLER-POS-001',
            'price' => 100,
            'stock' => 5,
        ]);

        $this->actingAs($staff)
            ->get(route('seller.pos.index'))
            ->assertInertia(fn ($page) => $page->where('products', []));

        $this->actingAs($staff)
            ->post(route('seller.inventory.manual.store'), [
                'product_variant_id' => $variant->id,
                'quantity' => 7,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'stock' => 7,
        ]);
        $this->assertDatabaseHas('seller_inventories', [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 7,
        ]);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'branch_id' => null,
            'stock' => 5,
        ]);

        $this->actingAs($staff)
            ->post(route('seller.inventory.shop-products.import'), [
                'source_product_id' => $product->id,
            ])
            ->assertRedirect();

        $branchCopy = Product::where('branch_id', $branch->id)
            ->where('name', $product->name)
            ->first();
        $this->assertNotNull($branchCopy);
        $this->assertNotSame($product->id, $branchCopy->id);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $branchCopy->id,
            'branch_id' => $branch->id,
            'stock' => 0,
        ]);

        $this->actingAs($staff)
            ->get(route('seller.pos.index'))
            ->assertInertia(fn ($page) => $page->where('products.0.id', $product->id));

        $this->actingAs($staff)
            ->post(route('seller.inventory.items.store'), [
                'name' => 'Branch-only Item',
                'sku' => 'BRANCH-ITEM-001',
                'category_id' => $category->id,
                'price' => 75,
                'stock' => 4,
            ])
            ->assertRedirect();

        $branchItem = Product::where('name', 'Branch-only Item')->first();
        $this->assertNotNull($branchItem);
        $this->assertSame($branch->id, $branchItem->branch_id);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $branchItem->id,
            'branch_id' => $branch->id,
            'sku' => 'BRANCH-ITEM-001',
            'stock' => 4,
        ]);
    }
}