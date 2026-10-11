<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\InventoryDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryDashboardService $inventoryDashboard
    ) {}

    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        return Inertia::render(
            'Seller/Inventory/Index',
            $this->inventoryDashboard->dashboard($team, $request->user()->branch_id)
        );
    }

    public function storeItem(Request $request)
    {
        $branchId = $request->user()->branch_id;
        abort_unless($branchId, 403, 'Only branch accounts can create branch items.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        DB::transaction(function () use ($data, $request, $branchId) {
            do {
                $sku = 'BR-' . $branchId . '-' . Str::upper(Str::random(8));
            } while (ProductVariant::where('sku', $sku)->exists());

            $product = Product::create([
                'team_id' => $request->user()->currentTeam->id,
                'branch_id' => $branchId,
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'slug' => Str::slug($data['name']) . '-' . Str::lower(Str::random(6)),
                'status' => 'active',
                'pos_available' => true,
            ]);

            $product->variants()->create([
                'branch_id' => $branchId,
                'sku' => $sku,
                'price' => $data['price'],
                'stock' => $data['stock'],
            ]);

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('images/products'), $filename);

                $product->images()->create([
                    'path' => 'images/products/' . $filename,
                    'sort_order' => 0,
                    'is_primary' => true,
                ]);
            }
        });

        return back()->with('success', 'Branch item added with stock.');
    }

    public function importShopProduct(Request $request)
    {
        $branchId = $request->user()->branch_id;
        abort_unless($branchId, 403, 'Only branch accounts can import shop products.');

        $data = $request->validate([
            'source_product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        DB::transaction(function () use ($data, $request, $branchId) {
            $source = Product::whereKey($data['source_product_id'])
                ->where('team_id', $request->user()->currentTeam->id)
                ->whereNull('branch_id')
                ->with([
                    'variants' => fn ($query) => $query->whereNull('branch_id'),
                    'images',
                ])
                ->firstOrFail();

            abort_if(
                Product::where('team_id', $source->team_id)
                    ->where('branch_id', $branchId)
                    ->where('name', $source->name)
                    ->exists(),
                422,
                'This product has already been added to the branch.'
            );

            $product = Product::create([
                'team_id' => $source->team_id,
                'branch_id' => $branchId,
                'category_id' => $source->category_id,
                'name' => $source->name,
                'slug' => Str::slug($source->name) . '-b' . $branchId . '-' . Str::lower(Str::random(4)),
                'description' => $source->description,
                'status' => $source->getRawOriginal('status'),
                'pos_available' => true,
            ]);

            foreach ($source->variants as $variant) {
                $product->variants()->create([
                    'branch_id' => $branchId,
                    'sku' => $variant->sku . '-B' . $branchId . '-' . Str::upper(Str::random(3)),
                    'label' => $variant->label,
                    'price' => $variant->price,
                    'stock' => 0,
                    'reserved_stock' => 0,
                    'is_active' => $variant->is_active,
                ]);
            }

            foreach ($source->images as $image) {
                $product->images()->create([
                    'path' => $image->path,
                    'sort_order' => $image->sort_order,
                    'is_primary' => $image->is_primary,
                ]);
            }
        });

        return back()->with('success', 'Shop product added to this branch with separate stock.');
    }
}