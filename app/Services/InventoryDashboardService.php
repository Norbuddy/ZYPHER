<?php

namespace App\Services;

use App\Models\SellerInventory;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Team;

class InventoryDashboardService
{
    public function dashboard(Team $team, ?int $branchId = null): array
    {
        $totalProducts = Product::where('team_id', $team->id)
            ->when($branchId, fn ($query) => $query->whereHas('variants', fn ($variants) => $variants->where('branch_id', $branchId)))
            ->count();

        $variants = ProductVariant::whereHas('product', function ($query) use ($team) {
            $query->where('team_id', $team->id);
        })->with(['product.images' => fn ($q) => $q->where('is_primary', true)])->get();

        if ($branchId) {
            $variants = $variants->where('branch_id', $branchId)->values();
        }

        $lowStock = $variants->filter(function ($variant) {
            return $variant->availableStock() <= 5 && $variant->availableStock() > 0;
        })->count();

        $inStock = $variants->filter(function ($variant) {
            return $variant->availableStock() > 0;
        })->count();

        $outOfStock = $variants->filter(function ($variant) {
            return $variant->availableStock() <= 0;
        })->count();

        return [
            'stats' => [
                'total_products' => $totalProducts,
                'low_stock' => $lowStock,
                'in_stock' => $inStock,
                'out_of_stock' => $outOfStock,
            ],

            // Placeholder muna
            'lowStocks' => $variants
    ->filter(function ($variant) {
        return $variant->availableStock() <= 5
            && $variant->availableStock() > 0;
    })
    ->sortBy(function ($variant) {
        return $variant->availableStock();
    })
    ->values()
    ->map(function ($variant) {
        $image = $variant->product->images->first();
        return [
            'product' => $variant->product->name,
            'image_path' => $image?->path,
            'variant' => $variant->label ?: '-',
            'stock' => $variant->availableStock(),
            'sku' => $variant->sku,
        ];
    }),

    'topSelling' => (function () use ($team, $branchId) {
                $rows = OrderItem::query()
                    ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
                    ->whereHas('variant.product', function ($query) use ($team) {
                        $query->where('team_id', $team->id);
                    })
                    ->when($branchId, fn ($query) => $query->where('product_variants.branch_id', $branchId))
                    ->selectRaw('
                        product_variants.product_id as product_id,
                        MAX(order_items.product_name) as product_name,
                        SUM(order_items.quantity) as sold,
                        SUM((order_items.price_at_purchase * order_items.quantity) - order_items.item_discount) as revenue
                    ')
                    ->groupBy('product_variants.product_id')
                    ->orderByDesc('sold')
                    ->limit(5)
                    ->get();

                $images = Product::whereIn('id', $rows->pluck('product_id'))
                    ->with(['images' => fn ($q) => $q->where('is_primary', true)])
                    ->get()
                    ->keyBy('id');

                return $rows->map(function ($row) use ($images) {
                    $image = $images->get($row->product_id)?->images->first();
                    return [
                        'product_name' => $row->product_name,
                        'sold' => $row->sold,
                        'revenue' => $row->revenue,
                        'image_path' => $image?->path,
                    ];
                });
            })(),

            'supplierInventory' => SellerInventory::where('seller_id', $team->id)
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->with(['product.images' => fn ($q) => $q->where('is_primary', true)])
                ->orderByDesc('updated_at')
                ->get()
                ->map(function ($row) {
                    return [
                        'id' => $row->id,
                        'product_id' => $row->product_id,
                        'product_name' => $row->product?->name ?? "Product #{$row->product_id}",
                        'image_path' => $row->product?->images?->first()?->path,
                        'quantity' => $row->quantity,
                        'source_type' => $row->source_type,
                        'source_id' => $row->source_id,
                        'updated_at' => $row->updated_at?->format('M d, Y h:i A'),
                    ];
                }),

            'branchStocks' => $variants->map(fn ($variant) => [
                'id' => $variant->id,
                'product_id' => $variant->product_id,
                'product_name' => $variant->product->name,
                'variant' => $variant->label ?: 'Default',
                'sku' => $variant->sku,
                'stock' => (int) $variant->stock,
                'available_stock' => max(0, (int) $variant->stock - (int) $variant->reserved_stock),
                'price' => (float) $variant->price,
                'source' => $variant->branch_id ? 'Branch item' : 'Shop item',
            ])->values(),

            'manualEligibleProducts' => $variants
                ->map(fn ($variant) => [
                    'id' => $variant->id,
                    'product_id' => $variant->product_id,
                    'name' => $variant->product->name . ($variant->label ? " - {$variant->label}" : ''),
                ])
                ->sortBy('name')
                ->values(),

            'shopProducts' => Product::where('team_id', $team->id)
                ->whereNull('branch_id')
                ->when($branchId, fn ($query) => $query->whereNotExists(function ($subquery) use ($branchId) {
                    $subquery->selectRaw('1')
                        ->from('products as branch_products')
                        ->whereColumn('branch_products.team_id', 'products.team_id')
                        ->whereColumn('branch_products.name', 'products.name')
                        ->where('branch_products.branch_id', $branchId);
                }))
                ->with(['variants' => fn ($query) => $query->whereNull('branch_id')])
                ->orderBy('name')
                ->get(['id', 'name']),

            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ];
    }
}