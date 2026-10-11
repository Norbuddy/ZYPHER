<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Enums\OrderShopStatus;
use App\Enums\PayoutStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /**
     * Orders/reviews with a rating at or below this are counted as a "complaint"
     * for the Damage Rate metric (there's no dedicated complaint flag on reviews,
     * so a low star rating is used as the proxy signal).
     */
    private const COMPLAINT_RATING_THRESHOLD = 2;

    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;
        $teamId = $team?->id;

        $completed = $team->orderShops()->where('status', OrderShopStatus::Completed);

        return Inertia::render('Seller/Reports/Index', [
            'totalRevenue' => (clone $completed)->sum('seller_payout'),
            'totalOrders' => (clone $completed)->count(),
            'unpaidPayout' => (clone $completed)->where('payout_status', PayoutStatus::Unpaid)->sum('seller_payout'),
            'topProducts' => $team->products()
                ->orderByDesc('sold_count')
                ->limit(10)
                ->get(['id', 'name', 'sold_count']),
            'productInsights' => $this->buildProductInsights($teamId),
        ]);
    }

    /**
     * For every product in this shop, work out:
     *  - Channel Split: how many units sold online (marketplace orders) vs. in-store (POS)
     *  - Damage Rate: % of reviewable (completed) orders for that item that came back
     *    with a low rating (used as a proxy for a "complaint")
     */
    private function buildProductInsights(?int $teamId): array
    {
        if (! $teamId) {
            return [];
        }

        // Units sold online per product: order_items on Completed order_shops for this team,
        // joined through product_variants to reach the product.
        $onlineUnits = DB::table('order_items')
            ->join('order_shops', 'order_shops.id', '=', 'order_items.order_shop_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->where('order_shops.team_id', $teamId)
            ->where('order_shops.status', OrderShopStatus::Completed->value)
            ->groupBy('product_variants.product_id')
            ->select('product_variants.product_id as product_id', DB::raw('SUM(order_items.quantity) as units'), DB::raw('COUNT(order_items.id) as orders'))
            ->get()
            ->keyBy('product_id');

        // Units sold in-store per product: pos_sale_items joined through product_variants.
        $posUnits = DB::table('pos_sale_items')
            ->join('pos_sales', 'pos_sales.id', '=', 'pos_sale_items.pos_sale_id')
            ->join('product_variants', 'product_variants.id', '=', 'pos_sale_items.product_variant_id')
            ->where('pos_sales.team_id', $teamId)
            ->groupBy('product_variants.product_id')
            ->select('product_variants.product_id as product_id', DB::raw('SUM(pos_sale_items.quantity) as units'), DB::raw('COUNT(pos_sale_items.id) as orders'))
            ->get()
            ->keyBy('product_id');

        // Complaint (low-rating review) counts per product.
        $complaints = DB::table('reviews')
            ->join('products', 'products.id', '=', 'reviews.product_id')
            ->where('products.team_id', $teamId)
            ->where('reviews.rating', '<=', self::COMPLAINT_RATING_THRESHOLD)
            ->groupBy('reviews.product_id')
            ->select('reviews.product_id as product_id', DB::raw('COUNT(*) as complaint_count'))
            ->get()
            ->keyBy('product_id');

        $products = DB::table('products')
            ->where('team_id', $teamId)
            ->whereNull('deleted_at')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $insights = [];

        foreach ($products as $product) {
            $online = $onlineUnits->get($product->id);
            $pos = $posUnits->get($product->id);

            $onlineQty = (int) ($online->units ?? 0);
            $posQty = (int) ($pos->units ?? 0);
            $totalQty = $onlineQty + $posQty;

            $onlinePct = $totalQty > 0 ? round(($onlineQty / $totalQty) * 100, 1) : 0;
            $posPct = $totalQty > 0 ? round(($posQty / $totalQty) * 100, 1) : 0;

            $dominantChannel = match (true) {
                $totalQty === 0 => 'No sales yet',
                $onlineQty === $posQty => 'Even split',
                $onlineQty > $posQty => 'Online',
                default => 'POS',
            };

            // Damage rate is measured against completed *online* orders, since only
            // online purchases can be reviewed (POS sales have no review flow).
            $reviewableOrders = (int) ($online->orders ?? 0);
            $complaintCount = (int) ($complaints->get($product->id)->complaint_count ?? 0);
            $damageRate = $reviewableOrders > 0 ? round(($complaintCount / $reviewableOrders) * 100, 1) : 0;

            // Skip products that have never sold anywhere, to keep the report focused.
            if ($totalQty === 0 && $complaintCount === 0) {
                continue;
            }

            $insights[] = [
                'id' => $product->id,
                'name' => $product->name,
                'onlineUnits' => $onlineQty,
                'posUnits' => $posQty,
                'totalUnits' => $totalQty,
                'onlinePct' => $onlinePct,
                'posPct' => $posPct,
                'dominantChannel' => $dominantChannel,
                'reviewableOrders' => $reviewableOrders,
                'complaintCount' => $complaintCount,
                'damageRate' => $damageRate,
            ];
        }

        // Highest-volume products first.
        usort($insights, fn ($a, $b) => $b['totalUnits'] <=> $a['totalUnits']);

        return $insights;
    }
}
