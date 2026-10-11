<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function show(string $shopSlug, string $productSlug): Response
    {
        $product = Product::query()
            ->active()
            ->whereHas('team', fn ($q) => $q->where('slug', $shopSlug))
            ->where('slug', $productSlug)
            ->with([
                'images',
                'team',
                'category',
                'options.values',
                'variants.optionValues',
                'reviews.user:id,name',
            ])
            ->firstOrFail();

        return Inertia::render('Product/Show', [
            'product' => $product,
            'available_sizes' => $product->variants->filter(fn($v) => ($v->stock - ($v->reserved_stock ?? 0)) > 0)->map(fn($v) => $v->label)->values(),
        ]);
    }
}
