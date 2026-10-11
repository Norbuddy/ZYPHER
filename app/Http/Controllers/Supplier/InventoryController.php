<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $supplier = Supplier::query()->where('team_id', $user->currentTeam?->id)->first();

        $variants = $supplier
            ? ProductVariant::with(['product.category'])
                ->whereHas('product', fn ($query) => $query->where('supplier_id', $supplier->id))
                ->get()
            : collect();

        $products = $variants->pluck('product')->unique('id');
        $availableStock = $variants->sum(fn (ProductVariant $variant) => $variant->availableStock());
        $lowStockVariants = $variants->filter(fn (ProductVariant $variant) => $variant->availableStock() > 0 && $variant->availableStock() <= 5);
        $outOfStockVariants = $variants->filter(fn (ProductVariant $variant) => $variant->availableStock() <= 0);
        $inventoryValue = $variants->sum(fn (ProductVariant $variant) => $variant->availableStock() * (float) $variant->price);

        return Inertia::render('Supplier/Inventory', [
            'stats' => [
                'total_products' => $products->count(),
                'available_stock' => $availableStock,
                'low_stock_products' => $lowStockVariants->count(),
                'out_of_stock_products' => $outOfStockVariants->count(),
                'inventory_value' => number_format($inventoryValue, 2),
            ],
            'variants' => $variants->map(function (ProductVariant $variant) {
                return [
                    'id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'sku' => $variant->sku,
                    'size' => $variant->label ?? $variant->sku,
                    'category' => $variant->product->category?->name ?? 'Uncategorized',
                    'stock' => $variant->stock,
                    'reserved_stock' => $variant->reserved_stock,
                    'available_stock' => $variant->availableStock(),
                    'price' => number_format((float) $variant->price, 2),
                    'status' => $variant->availableStock() <= 0 ? 'out_of_stock' : ($variant->availableStock() <= 5 ? 'low_stock' : 'in_stock'),
                    'stock_update_status' => $variant->stock_update_status ?? 'approved',
                ];
            })->values(),
        ]);
    }

    public function edit(Request $request, ProductVariant $variant): Response
    {
        $user = $request->user();
        $supplier = Supplier::query()->where('team_id', $user->currentTeam?->id)->first();

        abort_unless($supplier && $variant->product->supplier_id === $supplier->id, 403);

        return Inertia::render('Supplier/Inventory/Edit', [
            'variant' => [
                'id' => $variant->id,
                'product_name' => $variant->product->name,
                'sku' => $variant->sku,
                'size' => $variant->label ?? $variant->sku,
                'stock' => $variant->stock,
                'reserved_stock' => $variant->reserved_stock,
                'available_stock' => $variant->availableStock(),
                'price' => number_format((float) $variant->price, 2),
                'stock_update_status' => Schema::hasColumn('product_variants', 'stock_update_status') ? ($variant->stock_update_status ?? 'approved') : 'approved',
            ],
        ]);
    }

    public function updateStock(Request $request, ProductVariant $variant): RedirectResponse
    {
        $user = $request->user();
        $supplier = Supplier::query()->where('team_id', $user->currentTeam?->id)->first();

        abort_unless($supplier && $variant->product->supplier_id === $supplier->id, 403);

        $data = $request->validate([
            'stock' => ['required', 'integer', 'min:0'],
        ]);

        $updateData = ['stock' => $data['stock']];
        if (Schema::hasColumn('product_variants', 'stock_update_status')) {
            $updateData['stock_update_status'] = 'pending';
        }

        $variant->update($updateData);

        return back()->with('success', 'Stock updated successfully and is pending approval.');
    }
}
