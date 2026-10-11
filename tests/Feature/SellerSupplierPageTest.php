<?php

namespace Tests\Feature;

use App\Enums\ShopStatus;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SellerSupplierPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_view_supplier_page(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;
        $team->forceFill([
            'status' => ShopStatus::Approved,
            'personal_team' => false,
            'user_id' => $user->id,
        ])->save();
        $user->forceFill(['current_team_id' => $team->id])->save();

        $this->actingAs($user);

        $response = $this->get('/seller/supplier');

        $response->assertOk();
    }

    public function test_seller_can_create_supplier_order(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;
        $team->forceFill([
            'status' => ShopStatus::Approved,
            'personal_team' => false,
            'user_id' => $user->id,
        ])->save();
        $user->forceFill(['current_team_id' => $team->id])->save();

        if (!Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function ($table) {
                $table->id();
                $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('supplier_orders')) {
            Schema::create('supplier_orders', function ($table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
                $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
                $table->string('order_number')->unique();
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->string('status')->default('pending');
                $table->json('items')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'supplier_id')) {
            Schema::table('products', function ($table) {
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            });
        }

        $this->actingAs($user);

        $supplier = Supplier::create([
            'team_id' => $team->id,
            'name' => 'Acme Supplies',
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
            'team_id' => $team->id,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Widget',
            'slug' => 'widget',
            'description' => 'Test item',
            'status' => 'draft',
            'min_price' => 15.5,
            'max_price' => 15.5,
        ]);

        $product->variants()->create([
            'sku' => 'WIDGET-001',
            'label' => 'Default',
            'price' => 15.5,
            'stock' => 10,
        ]);

        $response = $this->post('/seller/supplier/orders', [
            'supplier_id' => $supplier->id,
            'items' => [
                ['id' => $product->id, 'name' => 'Widget', 'quantity' => 2, 'unitPrice' => 15.5],
            ],
        ]);

        $response->assertRedirect('/seller/supplier');
        $this->assertDatabaseHas('supplier_orders', [
            'supplier_id' => $supplier->id,
            'team_id' => $team->id,
        ]);
    }

    public function test_seller_supplier_order_preserves_variant_size_label_in_storage(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;
        $team->forceFill([
            'status' => ShopStatus::Approved,
            'personal_team' => false,
            'user_id' => $user->id,
        ])->save();
        $user->forceFill(['current_team_id' => $team->id])->save();

        $supplier = Supplier::create([
            'team_id' => $team->id,
            'name' => 'Acme Supplies',
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
            'team_id' => $team->id,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Widget',
            'slug' => 'widget',
            'description' => 'Test item',
            'status' => 'draft',
            'min_price' => 15.5,
            'max_price' => 15.5,
        ]);

        $variant = $product->variants()->create([
            'sku' => 'WIDGET-M',
            'label' => 'M',
            'price' => 15.5,
            'stock' => 10,
        ]);

        $this->actingAs($user);

        $response = $this->post('/seller/supplier/orders', [
            'supplier_id' => $supplier->id,
            'items' => [
                [
                    'id' => $product->id,
                    'name' => 'Widget',
                    'quantity' => 2,
                    'unitPrice' => 15.5,
                    'variantId' => $variant->id,
                    'variant_label' => 'M',
                ],
            ],
        ]);

        $response->assertRedirect('/seller/supplier');

        $order = \App\Models\SupplierOrder::query()->where('supplier_id', $supplier->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertSame('M', $order->items[0]['variant_label'] ?? null);
        $this->assertSame($variant->id, $order->items[0]['variantId'] ?? null);
    }
}
