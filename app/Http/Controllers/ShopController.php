<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    /**
     * Display the public shop storefront profile.
     */
    public function show(Request $request, string $shopSlug): Response
    {
        $shop = Team::query()
            ->where('slug', $shopSlug)
            ->orWhere('id', is_numeric($shopSlug) ? (int) $shopSlug : 0)
            ->firstOrFail();

        $search = trim((string) $request->string('q', ''));
        $sort = (string) $request->string('sort', 'featured');

        $productsQuery = Product::query()
            ->where('team_id', $shop->id)
            ->where('status', 'active')
            ->where('name', '!=', 'chromehearts')
            ->where('name', 'not like', '%Louis Vuitton%')
            ->where('name', 'not like', '%Nike Dunk%')
            ->where('name', 'not like', '%Stay Awake%')
            ->with([
                'images' => fn ($q) => $q->where('is_primary', true),
            ]);

        if ($search !== '') {
            $productsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        match ($sort) {
            'price_asc' => $productsQuery->orderBy('min_price', 'asc'),
            'price_desc' => $productsQuery->orderBy('min_price', 'desc'),
            'best_selling' => $productsQuery->orderBy('sold_count', 'desc'),
            'newest' => $productsQuery->latest(),
            default => $productsQuery->orderBy('sold_count', 'desc')->latest(),
        };

        $products = $productsQuery->paginate(12)->withQueryString();

        $reviews = Review::query()
            ->whereHas('product', fn ($q) => $q->where('team_id', $shop->id))
            ->with(['user:id,name', 'product:id,name,slug'])
            ->latest()
            ->take(15)
            ->get();

        $totalProducts = Product::where('team_id', $shop->id)
            ->where('status', 'active')
            ->where('name', 'not like', '%Louis Vuitton%')
            ->where('name', 'not like', '%Nike Dunk%')
            ->where('name', 'not like', '%Stay Awake%')
            ->count();

        $totalReviews = Review::whereHas('product', fn ($q) => $q->where('team_id', $shop->id))->count();
        $avgRating = Review::whereHas('product', fn ($q) => $q->where('team_id', $shop->id))->avg('rating') ?: ($shop->rating_avg ?: 5.0);

        // Fetch location address from shop owner's "My Address" or assigned branch
        $owner = $shop->owner ?? \App\Models\User::find($shop->user_id);
        $sellerAddress = null;
        if ($owner) {
            $sellerAddress = $owner->addresses()->where('is_default', true)->first()
                ?? $owner->addresses()->latest()->first();
        }

        $locationAddress = null;
        if ($sellerAddress) {
            $locationAddress = collect([
                $sellerAddress->line1,
                $sellerAddress->line2,
                $sellerAddress->city,
                $sellerAddress->province,
                $sellerAddress->postal_code,
            ])->filter()->join(', ');
        } elseif ($owner?->branch) {
            $branch = $owner->branch;
            $locationAddress = collect([
                $branch->address,
                $branch->barangay,
                $branch->city,
                $branch->province,
            ])->filter()->join(', ');
        }

        return Inertia::render('Shop/Show', [
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'slug' => $shop->slug,
                'description' => $shop->description ?: 'Independent apparel shop on Zypher',
                'location_address' => $locationAddress,
                'logo_path' => $shop->logo_path,
                'rating_avg' => round((float) $avgRating, 1),
                'rating_count' => $totalReviews,
                'products_count' => $totalProducts,
            ],
            'products' => $products,
            'reviews' => $reviews,
            'filters' => [
                'q' => $search,
                'sort' => $sort,
            ],
        ]);
    }
}
