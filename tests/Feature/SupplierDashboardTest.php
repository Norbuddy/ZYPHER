<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\SupplierOrder;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_dashboard_uses_supplier_order_data(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->forceFill(['is_supplier' => true, 'current_team_id' => $user->ownedTeams()->first()->id])->save();

        $supplier = Supplier::create([
            'team_id' => $user->currentTeam->id,
            'name' => 'Acme Supplies',
            'contact_name' => 'Jane',
            'email' => 'supplier@example.com',
        ]);

        SupplierOrder::create([
            'supplier_id' => $supplier->id,
            'team_id' => $user->currentTeam->id,
            'order_number' => 'SUP-1001',
            'total_amount' => 125.5,
            'status' => 'accepted',
            'items' => [['name' => 'Widget', 'quantity' => 2]],
        ]);

        $this->actingAs($user);

        $response = $this->get('/supplier/dashboard');

        $response->assertOk();
        $this->assertEquals('Supplier/Dashboard', $response->inertiaPage()['component']);
        $this->assertSame(1, $response->inertiaProps('stats')['total_orders']);
        $this->assertSame(125.5, $response->inertiaProps('stats')['total_revenue']);
        $this->assertSame('Acme Supplies', $response->inertiaProps('logs')[0]['shop_name']);
    }

    public function test_supplier_orders_index_keeps_all_statuses_visible_in_history(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->forceFill(['is_supplier' => true, 'current_team_id' => $user->ownedTeams()->first()->id])->save();

        $supplier = Supplier::create([
            'team_id' => $user->currentTeam->id,
            'name' => 'Acme Supplies',
            'contact_name' => 'Jane',
            'email' => 'supplier@example.com',
        ]);

        SupplierOrder::create([
            'supplier_id' => $supplier->id,
            'team_id' => $user->currentTeam->id,
            'order_number' => 'SUP-1003',
            'total_amount' => 10.0,
            'status' => 'pending',
            'shipment_status' => 'pending',
            'items' => [['name' => 'Widget', 'quantity' => 1]],
        ]);

        SupplierOrder::create([
            'supplier_id' => $supplier->id,
            'team_id' => $user->currentTeam->id,
            'order_number' => 'SUP-1004',
            'total_amount' => 25.0,
            'status' => 'accepted',
            'shipment_status' => 'delivered',
            'items' => [['name' => 'Widget', 'quantity' => 2]],
        ]);

        $this->actingAs($user);

        $response = $this->get('/supplier/orders');

        $response->assertOk();
        $this->assertCount(2, $response->inertiaProps('orders'));
    }

    public function test_accepting_supplier_order_deducts_inventory(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->forceFill(['is_supplier' => true, 'current_team_id' => $user->ownedTeams()->first()->id])->save();

        $supplier = Supplier::create([
            'team_id' => $user->currentTeam->id,
            'name' => 'Acme Supplies',
            'contact_name' => 'Jane',
            'email' => 'supplier@example.com',
        ]);

        $category = Category::create([
            'parent_id' => null,
            'name' => 'General',
            'slug' => 'general',
            'image_path' => null,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $product = Product::create([
            'team_id' => $user->currentTeam->id,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Widget',
            'slug' => 'widget',
            'description' => 'Test product',
            'status' => 'draft',
            'min_price' => 10,
            'max_price' => 10,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'WIDGET-001',
            'label' => 'Default',
            'price' => 10,
            'stock' => 10,
        ]);

        $order = SupplierOrder::create([
            'supplier_id' => $supplier->id,
            'team_id' => $user->currentTeam->id,
            'order_number' => 'SUP-1002',
            'total_amount' => 30.0,
            'status' => 'pending',
            'items' => [['id' => $product->id, 'name' => 'Widget', 'quantity' => 3, 'unitPrice' => 10]],
        ]);

        $this->actingAs($user);

        $response = $this->post("/supplier/orders/{$order->id}/accept");

        $response->assertOk();
        $order->refresh();
        $variant->refresh();

        $this->assertSame('accepted', $order->status);
        $this->assertSame(7, $variant->stock);
    }
}
