<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        Category::firstOrCreate(
            ['name' => 'Shoes'],
            ['slug' => 'shoes', 'is_active' => true, 'sort_order' => 4]
        );

        $categories = Category::query()
            ->where('parent_id', '=', null)
            ->where('is_active', '=', true)
            ->orderBy('sort_order', 'asc')
            ->get(['id', 'name', 'slug', 'image_path']);

        $productBaseQuery = fn () => Product::query()
            ->active()
            ->where('name', '!=', 'chromehearts')
            ->whereHas('team', fn ($query) => $query->where('name', '!=', 'Supplier Demo Supplier Team'))
            ->with(['images' => fn ($q) => $q->where('is_primary', true), 'team:id,name,slug']);

        // Best Sellers: ranked by how many units have actually sold.
        $bestSellers = $productBaseQuery()
            ->where('sold_count', '>', 0)
            ->orderByDesc('sold_count')
            ->limit(12)
            ->get();

        // Top Trending: ranked by highest average rating (needs at least one
        // real rating so a single 5-star review can't outrank a well-reviewed
        // product), tiebroken by rating_count so more-reviewed items rank higher.
        $topTrending = $productBaseQuery()
            ->where('rating_count', '>', 0)
            ->orderByDesc('avg_rating')
            ->orderByDesc('rating_count')
            ->limit(12)
            ->get();

        $banners = \App\Models\Banner::where('is_active', true)
            ->orderBy('sort_order')
            ->get([
                'id', 'title', 'subtitle', 'button_text', 'button_link',
                'title_font', 'subtitle_font', 'button_font',
                'title_color', 'subtitle_color', 'button_text_color',
                'button_background', 'button_gradient_color', 'button_style',
                'image_path',
            ]);

        return Inertia::render('Home', [
            'categories' => $categories,
            'bestSellers' => $bestSellers,
            'topTrending' => $topTrending,
            'banners' => $banners,
        ]);
    }
}
