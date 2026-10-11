<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
        public function index(Request $request): Response
    {
        $products = $request->user()->currentTeam->products()
            ->with('images')
            ->latest()
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image_path' => $product->images->first()?->path,
                    'min_price' => (float) $product->min_price,
                    'max_price' => (float) $product->max_price,
                    'sold_count' => $product->sold_count,
                    'status' => $product->status?->value ?? (string) $product->status,
                    'created_at' => $product->created_at?->toIso8601String(),
                ];
            });

        return Inertia::render('Seller/Products/Index', ['products' => $products]);
    }

    public function create(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        return Inertia::render('Seller/Products/Create', [
            'categories' => Category::where('is_active', true)->get(),
            'supplierStock' => \App\Models\SellerInventory::where('seller_id', $team->id)
                ->where('source_type', 'supplier')
                ->where('quantity', '>', 0)
                ->with('product:id,name,category_id')
                ->get()
                ->map(fn ($row) => [
                    'id' => $row->id,
                    'product_name' => $row->product?->name ?? "Product #{$row->product_id}",
                    'category_id' => $row->product?->category_id,
                    'quantity' => $row->quantity,
                ]),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        $data = $request->validated();

        $product = $team->products()->create([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) . '-' . Str::random(6),
            'description' => $data['description'] ?? null,
            // Publish immediately unless the seller explicitly unchecks "Publish now" on the form.
            'status' => ($data['publish'] ?? true) ? 'active' : 'draft',
        ]);

        // Store uploaded images (first one becomes the primary/thumbnail image)
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $filename = time().'_'.$file->getClientOriginalName();

$file->move(
    public_path('images/products'),
    $filename
);

$path = 'images/products/'.$filename;

                $product->images()->create([
    'path' => $path,
    'sort_order' => $index,
    'is_primary' => $index === 0,
]);
            }
        }

        // Build variation axes + option values
        $valueIdLookup = []; // "AxisName|Value" => option_value_id
        foreach ($data['options'] ?? [] as $axisIndex => $axis) {
            $option = $product->options()->create(['name' => $axis['name'], 'sort_order' => $axisIndex]);

            foreach ($axis['values'] as $valueIndex => $value) {
                $optionValue = $option->values()->create(['value' => $value, 'sort_order' => $valueIndex]);
                $valueIdLookup["{$axis['name']}|{$value}"] = $optionValue->id;
            }
        }

        // Create variant combinations. The frontend VariantBuilder sends
        // option_value_labels: [{axis: 'Color', value: 'Red'}, ...] per variant,
        // which we resolve against the lookup built above.
        foreach ($data['variants'] ?? [] as $variantData) {
            $variant = $product->variants()->create([
                'sku' => $variantData['sku'] ?? Str::upper(Str::random(10)),
                'label' => $variantData['label'] ?? null,
                'price' => $variantData['price'],
                'stock' => $variantData['stock'],
            ]);

            foreach ($variantData['option_value_labels'] ?? [] as $part) {
                $key = "{$part['axis']}|{$part['value']}";
                if (isset($valueIdLookup[$key])) {
                    $variant->optionValues()->attach($valueIdLookup[$key]);
                }
            }
        }

        $product->refreshPriceRange();

        if (!empty($data['seller_inventory_id'])) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($data, $team, $product) {
                $inventoryRow = \App\Models\SellerInventory::where('id', $data['seller_inventory_id'])
                    ->where('seller_id', $team->id)
                    ->where('source_type', 'supplier')
                    ->lockForUpdate()
                    ->first();

                if ($inventoryRow) {
                    $publishedQty = min((int) collect($data['variants'] ?? [])->sum('stock'), $inventoryRow->quantity);

                    if ($publishedQty > 0) {
                        $inventoryRow->decrement('quantity', $publishedQty);

                        \App\Models\InventoryLog::create([
                            'seller_id' => $team->id,
                            'product_id' => $inventoryRow->product_id,
                            'type' => 'out',
                            'quantity' => $publishedQty,
                            'source_type' => 'supplier',
                            'source_id' => $inventoryRow->id,
                            'note' => "Published as storefront listing: {$product->name}",
                        ]);
                    }
                }
            });
        }

        return redirect()->route('seller.products.index')->with('success', 'Product created.');
    }

    public function edit(Request $request, Product $product): Response
    {
        abort_unless($product->team_id === $request->user()->currentTeam->id, 403);

        $product->load('images', 'options.values', 'variants.optionValues');

        return Inertia::render('Seller/Products/Edit', [
            'product' => $product,
            'categories' => Category::where('is_active', true)->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        abort_unless($product->team_id === $request->user()->currentTeam->id, 403);

        $product->update($request->validated());
        $product->refreshPriceRange();

        return back()->with('success', 'Product updated.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->team_id === $request->user()->currentTeam->id, 403);

        $product->delete();

        return redirect()->route('seller.products.index')->with('success', 'Product removed.');
    }

    public function storePos(Request $request)
    {
        $team = $request->user()->currentTeam;

        $data = $request->validate([
            'name' => 'required|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'images' => 'nullable|array|max:8',
            'images.*' => 'file|mimes:jpg,jpeg,png,webp|max:5120',
            'seller_inventory_id' => 'nullable|integer|exists:seller_inventories,id',
        ]);

        $product = \App\Models\Product::create([
            'team_id' => $team->id,
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => \Illuminate\Support\Str::slug($data['name']) . '-' . \Illuminate\Support\Str::random(5),
            'status' => 'draft',
            'pos_available' => true,
        ]);

        $product->variants()->create([
            'label' => null,
            'sku' => 'POS-' . strtoupper(\Illuminate\Support\Str::random(6)),
            'price' => $data['price'],
            'stock' => $data['stock'],
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('images/products'), $filename);

                $product->images()->create([
                    'path' => 'images/products/' . $filename,
                    'sort_order' => $index,
                    'is_primary' => $index === 0,
                ]);
            }
        }

        if (!empty($data['seller_inventory_id'])) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($data, $team, $product) {
                $inventoryRow = \App\Models\SellerInventory::where('id', $data['seller_inventory_id'])
                    ->where('seller_id', $team->id)
                    ->where('source_type', 'supplier')
                    ->lockForUpdate()
                    ->first();

                if ($inventoryRow) {
                    $publishedQty = min((int) $data['stock'], $inventoryRow->quantity);

                    if ($publishedQty > 0) {
                        $inventoryRow->decrement('quantity', $publishedQty);

                        \App\Models\InventoryLog::create([
                            'seller_id' => $team->id,
                            'product_id' => $inventoryRow->product_id,
                            'type' => 'out',
                            'quantity' => $publishedQty,
                            'source_type' => 'supplier',
                            'source_id' => $inventoryRow->id,
                            'note' => "Published as POS item: {$product->name}",
                        ]);
                    }
                }
            });
        }

        return back()->with('success', 'Product added to POS.');
    }
}
