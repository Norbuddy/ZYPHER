<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\SearchHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    /**
     * Main product search.
     */
    public function index(Request $request): Response
    {
        $query = trim((string) $request->string('q', ''));
        $categoryParam = $request->input('category_id');
        $categoryId = is_numeric($categoryParam) ? (int) $categoryParam : null;
        $categorySlug = (!is_numeric($categoryParam) && !empty($categoryParam)) ? (string) $categoryParam : null;

        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $minRating = $request->input('min_rating');

        $sort = (string) $request->string('sort', 'relevance');

        /*
        |--------------------------------------------------------------------------
        | Save search history
        |--------------------------------------------------------------------------
        */

        if (
            $query !== '' &&
            $request->user()
        ) {
            $this->recordSearchHistory(
                $request->user()->id,
                $query
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Product Search
        |--------------------------------------------------------------------------
        */

        $products = Product::query()
            ->where('status', 'active')

            ->with([
                'images' => fn ($q) => $q->where('is_primary', true),
                'team:id,name,slug',
            ])

            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($inner) use ($query) {
                    $inner
                        ->where('name', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%");
                });
            })

            ->when(!empty($categoryParam), function ($q) use ($categoryId, $categorySlug) {
                $ids = Category::query()
                    ->when($categoryId, fn ($query) => $query->where('id', $categoryId)->orWhere('parent_id', $categoryId))
                    ->when($categorySlug, fn ($query) => $query->where('slug', $categorySlug)->orWhere('name', 'like', "%{$categorySlug}%"))
                    ->pluck('id');

                if ($ids->isNotEmpty()) {
                    $q->whereIn('category_id', $ids);
                } elseif ($categorySlug) {
                    $q->where('name', 'like', "%{$categorySlug}%");
                }
            })

            ->when(
                $minPrice !== null && $minPrice !== '',
                fn ($q) => $q->where(
                    'min_price',
                    '>=',
                    (float) $minPrice
                )
            )

            ->when(
                $maxPrice !== null && $maxPrice !== '',
                fn ($q) => $q->where(
                    'max_price',
                    '<=',
                    (float) $maxPrice
                )
            )

            ->when(
                $minRating !== null && $minRating !== '',
                fn ($q) => $q->where(
                    'avg_rating',
                    '>=',
                    (float) $minRating
                )
            );

        match ($sort) {
            'price_asc' => $products->orderBy('min_price', 'asc'),

            'price_desc' => $products->orderBy('min_price', 'desc'),

            'newest' => $products->orderByDesc('created_at'),

            'rating' => $products->orderByDesc('avg_rating'),

            default => $products->orderByDesc('sold_count'),
        };

        $results = $products
            ->paginate(24)
            ->withQueryString();

        try {
            Category::updateOrCreate(
                ['slug' => 'shoes'],
                ['name' => 'Shoes', 'parent_id' => null, 'is_active' => true, 'sort_order' => 4]
            );
            Category::where('name', 'Shoes')->orWhere('slug', 'shoes')->update([
                'is_active' => true,
                'parent_id' => null,
                'sort_order' => 4,
            ]);
        } catch (\Throwable $e) {}

        $categories = Category::query()
            ->where('parent_id', null)
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get(['id', 'name', 'slug']);

        return Inertia::render('Search/Index', [
            'results' => $results,
            'categories' => $categories,

            'filters' => $request->only([
                'q',
                'category_id',
                'min_price',
                'max_price',
                'min_rating',
                'sort',
            ]),
        ]);
    }

    /**
     * Store customer search history.
     */
    private function recordSearchHistory(
        int $userId,
        string $query
    ): void {
        $normalized = mb_strtolower(trim($query));

        if ($normalized === '') {
            return;
        }

        $history = SearchHistory::where('user_id', $userId)
            ->where('query', $normalized)
            ->first();

        if ($history) {
            $history->increment('search_count');

            $history->update([
                'last_searched_at' => now(),
            ]);

            return;
        }

        SearchHistory::create([
            'user_id' => $userId,
            'query' => $normalized,
            'search_count' => 1,
            'last_searched_at' => now(),
        ]);
    }

    /**
     * DSS endpoint used by the storefront search dropdown.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('q', ''));

        /*
        |--------------------------------------------------------------------------
        | 1. DSS SIMILARITY SUGGESTIONS
        |--------------------------------------------------------------------------
        |
        | Suggestions are NOT individual products.
        | They represent categories / attributes / shopping intents.
        |
        */

        $baseSuggestions = collect([
            'T-shirts',
            'Long sleeves',
            'Pants',
            'Jacket',
            'Shoes',
            'Black clothing',
            'White clothing',
            'Oversized clothing',
            'Casual clothing',
            'Streetwear',
        ]);

        if ($query !== '') {
            $normalizedQuery = mb_strtolower($query);

            $keywordMap = [
                'shirt' => [
                    'T-shirts',
                    'Long sleeves',
                    'Oversized clothing',
                ],

                'tshirt' => [
                    'T-shirts',
                    'Oversized clothing',
                    'Casual clothing',
                ],

                't-shirt' => [
                    'T-shirts',
                    'Oversized clothing',
                    'Casual clothing',
                ],

                'tee' => [
                    'T-shirts',
                    'Oversized clothing',
                ],

                'long' => [
                    'Long sleeves',
                    'T-shirts',
                    'Casual clothing',
                ],

                'pants' => [
                    'Pants',
                    'Casual clothing',
                    'Streetwear',
                ],

                'pant' => [
                    'Pants',
                    'Casual clothing',
                    'Streetwear',
                ],

                'cargo' => [
                    'Pants',
                    'Streetwear',
                    'Casual clothing',
                ],

                'jacket' => [
                    'Jacket',
                    'Casual clothing',
                    'Streetwear',
                ],

                'coat' => [
                    'Jacket',
                    'Casual clothing',
                ],

                'shoe' => [
                    'Shoes',
                    'Casual clothing',
                ],

                'shoes' => [
                    'Shoes',
                    'Casual clothing',
                ],

                'sneaker' => [
                    'Shoes',
                    'Streetwear',
                ],

                'black' => [
                    'Black clothing',
                    'T-shirts',
                    'Pants',
                    'Jacket',
                    'Shoes',
                ],

                'white' => [
                    'White clothing',
                    'T-shirts',
                    'Long sleeves',
                    'Shoes',
                ],

                'oversized' => [
                    'Oversized clothing',
                    'T-shirts',
                    'Long sleeves',
                    'Streetwear',
                ],

                'casual' => [
                    'Casual clothing',
                    'T-shirts',
                    'Pants',
                    'Jacket',
                ],

                'street' => [
                    'Streetwear',
                    'Oversized clothing',
                    'Shoes',
                    'Pants',
                ],
            ];

            $matched = collect();

            foreach ($keywordMap as $keyword => $items) {
                if (str_contains($normalizedQuery, $keyword)) {
                    $matched = $matched->merge($items);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | If no known attribute was detected, use category similarity
            |--------------------------------------------------------------------------
            */

            if ($matched->isEmpty()) {
                $matched = $baseSuggestions->filter(
                    fn ($suggestion) =>
                        str_contains(
                            mb_strtolower($suggestion),
                            $normalizedQuery
                        )
                        ||
                        str_contains(
                            $normalizedQuery,
                            mb_strtolower($suggestion)
                        )
                );
            }

            $suggestions = $matched
                ->unique()
                ->take(6)
                ->values();
        } else {
            /*
            |--------------------------------------------------------------------------
            | Initial suggestions before typing
            |--------------------------------------------------------------------------
            */

            $suggestions = $baseSuggestions
                ->take(6)
                ->values();
        }

        /*
        |--------------------------------------------------------------------------
        | 2. SEARCH HISTORY
        |--------------------------------------------------------------------------
        */

        $history = collect();

        if ($request->user()) {
            $history = $request->user()
                ->searchHistories()
                ->orderByDesc('last_searched_at')
                ->limit(5)
                ->get([
                    'id',
                    'query',
                    'search_count',
                    'last_searched_at',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 3. DSS PRODUCT RECOMMENDATIONS
        |--------------------------------------------------------------------------
        */

        $recommendations = $this->getRecommendations(
            $request,
            $query
        );

        return response()->json([
            'suggestions' => $suggestions,

            'history' => $history,

            'recommendations' => $recommendations,
        ]);
    }


/**
 * Rule-based DSS recommendation engine.
 */
    private function getRecommendations(
        Request $request,
        string $query = ''
    ) {
        /*
        |--------------------------------------------------------------------------
        | Customer's previous search behavior
        |--------------------------------------------------------------------------
        */

        $historyQueries = collect();

        if ($request->user()) {
            $historyQueries = $request->user()
                ->searchHistories()
                ->orderByDesc('search_count')
                ->limit(5)
                ->pluck('query');
        }

        /*
        |--------------------------------------------------------------------------
        | Product recommendation base query
        |--------------------------------------------------------------------------
        */

        $products = Product::query()
            ->where('status', 'active')
            ->where('name', '!=', 'chromehearts')
            ->with([
                'images' => fn ($q) => $q->where('is_primary', true),
                'team:id,name,slug',
            ]);

        /*
        |--------------------------------------------------------------------------
        | DSS SCORING
        |--------------------------------------------------------------------------
        |
        | Current query match       = 50
        | Search-history match      = 30
        | Sales popularity          = 10%
        | Rating                    = x5
        |
        */

        $products->select('products.*');

        $scoreParts = [];
        $bindings = [];

        if ($query !== '') {
            $scoreParts[] = "
                CASE
                    WHEN products.name LIKE ? THEN 50
                    WHEN products.name LIKE ? THEN 30
                    WHEN products.description LIKE ? THEN 20
                    ELSE 0
                END
            ";

            $bindings[] = "{$query}%";
            $bindings[] = "%{$query}%";
            $bindings[] = "%{$query}%";
        }

        foreach ($historyQueries as $historyQuery) {
            $scoreParts[] = "
                CASE
                    WHEN products.name LIKE ? THEN 30
                    WHEN products.description LIKE ? THEN 15
                    ELSE 0
                END
            ";

            $bindings[] = "%{$historyQuery}%";
            $bindings[] = "%{$historyQuery}%";
        }

        $scoreParts[] = '(COALESCE(products.sold_count, 0) * 0.10)';
        $scoreParts[] = '(COALESCE(products.avg_rating, 0) * 5)';

        $products->selectRaw(
            '(' . implode(' + ', $scoreParts) . ') AS recommendation_score',
            $bindings
        );

        /*
        |--------------------------------------------------------------------------
        | Personalized matching
        |--------------------------------------------------------------------------
        */

        if ($query !== '' || $historyQueries->isNotEmpty()) {
            $products->where(function ($q) use ($query, $historyQueries) {

                if ($query !== '') {
                    $q->where('products.name', 'like', "%{$query}%")
                        ->orWhere('products.description', 'like', "%{$query}%");
                }

                foreach ($historyQueries as $historyQuery) {
                    $q->orWhere(
                        'products.name',
                        'like',
                        "%{$historyQuery}%"
                    );

                    $q->orWhere(
                        'products.description',
                        'like',
                        "%{$historyQuery}%"
                    );
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Get recommendations
        |--------------------------------------------------------------------------
        */

        $products = $products
            ->orderByDesc('recommendation_score')
            ->orderByDesc('sold_count')
            ->limit(6)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Popular fallback
        |--------------------------------------------------------------------------
        */

        if ($products->count() < 6) {

            $existingIds = $products->pluck('id');

            $fallback = Product::query()
                ->where('status', 'active')
                ->where('name', '!=', 'chromehearts')
                ->whereNotIn('id', $existingIds)
                ->with([
                    'images' => fn ($q) => $q->where('is_primary', true),
                    'team:id,name,slug',
                ])
                ->orderByDesc('sold_count')
                ->orderByDesc('avg_rating')
                ->limit(6 - $products->count())
                ->get();

            $products = $products
                ->concat($fallback)
                ->values();
        }

        return $products;
    }
}