<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StoreProductRequest;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $supplier = $user->currentTeam?->supplier;

        $products = $supplier
            ? Product::where('supplier_id', $supplier->id)->with('images', 'variants', 'category')->latest()->paginate(20)
            : Product::paginate(0);

        return Inertia::render('Supplier/Products/Index', ['products' => $products]);
    }

    public function create(Request $request)
    {
        $allowed = [
            'Electronics', 'Toys', 'Shoes', 'Slippers',
            'Pants', 'T-Shirts', 'Long Sleeves', 'Jacket',
        ];

        $categories = Category::whereIn('name', $allowed)->where('is_active', true)->get()->sortBy(function ($c) use ($allowed) {
            return array_search($c->name, $allowed);
        })->values();

        return Inertia::render('Supplier/Products/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $user = $request->user();
        $team = $user->currentTeam;
        $supplier = $team?->supplier;

        abort_unless($supplier, 403);

        $data = $request->validated();

        $product = Product::create([
            'team_id' => $team->id,
            'supplier_id' => $supplier->id,
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) . '-' . Str::random(6),
            'description' => $data['description'] ?? null,
            'status' => ProductStatus::Draft,
        ]);

        // If size_variants provided, create one variant per size and stock.
        if (!empty($data['size_variants']) && is_array($data['size_variants'])) {
            foreach ($data['size_variants'] as $sv) {
                $product->variants()->create([
                    'sku' => Str::upper(Str::random(10)),
                    'label' => $sv['size'],
                    'price' => $data['price'],
                    'stock' => $sv['stock'] ?? 0,
                ]);
            }
        } else {
            // create a default variant to hold price/stock
            $variant = $product->variants()->create([
                'sku' => Str::upper(Str::random(10)),
                'label' => 'Default',
                'price' => $data['price'],
                'stock' => $data['stock'] ?? 0,
            ]);
        }

        // Update product total stock from variants
        $product->refreshStock();

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');

                $product->images()->create([
                    'path' => $path,
                    'sort_order' => $index,
                    'is_primary' => $index === 0,
                ]);
            }
        }

        $product->refreshPriceRange();

        return redirect()->route('supplier.products.index')->with('success', 'Product created.');
    }

    public function edit(Request $request, Product $product)
    {
        $user = $request->user();
        $supplier = $user->currentTeam?->supplier;

        abort_unless($supplier?->id && $product->supplier_id === $supplier->id, 403);

        $product->load('images', 'variants');

        $allowed = [
            'Electronics', 'Toys', 'Shoes', 'Slippers',
            'Pants', 'T-Shirts', 'Long Sleeves', 'Jacket',
        ];

        $categories = Category::whereIn('name', $allowed)->where('is_active', true)->get()->sortBy(function ($c) use ($allowed) {
            return array_search($c->name, $allowed);
        })->values();

        return Inertia::render('Supplier/Products/Edit', [
            'product' => $product,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $user = $request->user();
        $supplier = $user->currentTeam?->supplier;

        abort_unless($supplier?->id && $product->supplier_id === $supplier->id, 403);

        $allowed = [
            'Electronics', 'Toys', 'Shoes', 'Slippers',
            'Pants', 'T-Shirts', 'Long Sleeves', 'Jacket',
        ];
        $allowedCategoryIds = Category::whereIn('name', $allowed)->pluck('id')->toArray();
        $inRule = count($allowedCategoryIds) ? ('in:' . implode(',', $allowedCategoryIds)) : 'exists:categories,id';

        $data = $request->validate([
            'category_id' => ['sometimes', $inRule],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'size_variants' => ['nullable', 'array'],
            'size_variants.*.size' => ['required_with:size_variants', 'string'],
            'size_variants.*.stock' => ['required_with:size_variants', 'integer', 'min:0'],
        ]);

        $product->update($data);

        if (isset($data['price'])) {
            // update variant prices for all variants (including size variants)
            $product->variants()->update(['price' => $data['price']]);
            $product->refreshPriceRange();
        }

        // Sync size variants if provided
        if (!empty($data['size_variants']) && is_array($data['size_variants'])) {
            $incomingLabels = array_map(fn($sv) => (string) $sv['size'], $data['size_variants']);

            foreach ($data['size_variants'] as $sv) {
                $label = $sv['size'];
                $stock = $sv['stock'] ?? 0;

                $variant = $product->variants()->where('label', $label)->first();
                if ($variant instanceof ProductVariant) {
                    $variant->update(['stock' => $stock]);
                } else {
                    $product->variants()->create([
                        'sku' => Str::upper(Str::random(10)),
                        'label' => $label,
                        'price' => $data['price'] ?? 0,
                        'stock' => $stock,
                    ]);
                }
            }

            // Remove variants that are no longer present (except 'Default')
            $product->variants()->whereNotIn('label', $incomingLabels)->where('label', '<>', 'Default')->delete();
            // If there are incoming size variants, remove any 'Default' variant
            if (count($incomingLabels) > 0) {
                $product->variants()->where('label', 'Default')->delete();
            }

            // refresh total stock after variant sync
            $product->refreshStock();
        }

        // If no size_variants were provided but `stock` was sent, update the default variant stock
        if ((empty($data['size_variants']) || !is_array($data['size_variants'])) && isset($data['stock'])) {
            $default = $product->variants()->where('label', 'Default')->first();
            if ($default instanceof ProductVariant) {
                $default->update(['stock' => $data['stock']]);
                $product->refreshStock();
            }
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');

                $product->images()->create([
                    'path' => $path,
                    'sort_order' => $product->images()->count() + $index,
                    'is_primary' => false,
                ]);
            }
        }

        return back()->with('success', 'Product updated.');
    }

    public function destroy(Request $request, Product $product)
    {
        $user = $request->user();
        $supplier = $user->currentTeam?->supplier;

        abort_unless($supplier?->id && $product->supplier_id === $supplier->id, 403);

        $product->delete();

        return redirect()->route('supplier.products.index')->with('success', 'Product removed.');
    }
}
