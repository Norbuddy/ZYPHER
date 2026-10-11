<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Enums\OrderShopStatus;
use App\Models\PosSale;
use App\Models\SupplierOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;
        $branchId = $request->user()->branch_id;

        /*
        |--------------------------------------------------------------------------
        | Seller Financial Statistics
        |--------------------------------------------------------------------------
        */

        $completedShops = $branchId ? collect() : $team->orderShops()
            ->where('status', OrderShopStatus::Completed)
            ->get(['seller_payout', 'platform_fee']);

        $netPayout = (float) $completedShops->sum('seller_payout');
        $grossRevenue = (float) $completedShops->sum(
            fn ($shop) => (float) $shop->seller_payout + (float) $shop->platform_fee
        );

        $supplierOrders = $branchId ? collect() : SupplierOrder::where('team_id', $team->id)
            ->whereIn('status', ['accepted', 'processing', 'completed'])
            ->get(['total_amount', 'paid_at']);

        $cogs = (float) $supplierOrders->sum('total_amount');
        $accountsPayable = (float) $supplierOrders->whereNull('paid_at')->sum('total_amount');
        $supplierPaid = (float) $supplierOrders->whereNotNull('paid_at')->sum('total_amount');
        $branchPosRevenue = $branchId
            ? (float) PosSale::where('team_id', $team->id)->where('branch_id', $branchId)->sum('total_amount')
            : 0;
        $dashboardRevenue = $branchId ? $branchPosRevenue : $grossRevenue;
        $cashOnHand = $branchId ? $branchPosRevenue : (float) $team->starting_cash_balance
            + $netPayout
            - $supplierPaid
            + (float) \App\Models\CashAdjustment::where('team_id', $team->id)->where('type', 'deposit')->sum('amount')
            - (float) \App\Models\CashAdjustment::where('team_id', $team->id)->where('type', 'withdrawal')->sum('amount');

        $stats = [
            'pending_orders' => $branchId ? 0 : $team->orderShops()->where('status', OrderShopStatus::PendingPayment)->count(),
            'processing_orders' => $branchId ? 0 : $team->orderShops()->where('status', OrderShopStatus::Processing)->count(),
            'gross_revenue' => $dashboardRevenue,
            'net_payout' => $branchId ? $branchPosRevenue : $netPayout,
            'cogs' => $cogs,
            'gross_profit' => $dashboardRevenue - $cogs,
            'accounts_payable' => $accountsPayable,
            'cash_on_hand' => $cashOnHand,
            'active_products' => $branchId
                ? \App\Models\ProductVariant::where('branch_id', $branchId)->count()
                : $team->products()->where('status', 'active')->count(),
            'low_stock_variants' => \App\Models\ProductVariant::whereHas(
                'product', fn ($q) => $q->where('team_id', $team->id)
            )->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('stock', '<=', 5)->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Supplier Label
        |--------------------------------------------------------------------------
        */

        $supplierNames = \App\Models\Supplier::whereHas('teams', function ($q) use ($team) {
            $q->where('teams.id', $team->id)
                ->where('supplier_team.active', true);
        })->pluck('name');


        $supplierLabel = match (true) {
            $supplierNames->count() === 1 => $supplierNames->first(),
            $supplierNames->count() > 1 => 'Your Suppliers',
            default => 'Supplier',
        };

        /*
        |--------------------------------------------------------------------------
        | Recent Online Orders
        |--------------------------------------------------------------------------
        |
        | We get the latest online orders for this seller.
        |
        */

        $onlineOrders = $team->orderShops()
            ->with('items', 'order.user:id,name')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($orderShop) {
                return [
                    'id' => 'online-' . $orderShop->id,

                    'source' => 'online',

                    'source_label' => 'ONLINE',

                    'order_id' => $orderShop->id,

                    'reference' => $orderShop->order?->order_number,

                    'customer_name' => $orderShop->order?->user?->name ?? 'Customer',

                    'product_names' => $orderShop->items
                        ->pluck('product_name')
                        ->filter()
                        ->values()
                        ->all(),

                    'items_count' => $orderShop->items->sum('quantity'),

                    'ordered_at' => $orderShop->created_at?->toIso8601String(),

                    'status' => $orderShop->status?->value ?? (string) $orderShop->status,

                    'total' => (float) $orderShop->subtotal,

                    'can_process' => $orderShop->status === OrderShopStatus::PendingPayment,
                    'can_ship' => $orderShop->status === OrderShopStatus::Processing,
                ];
            });

        if ($branchId) {
            $onlineOrders = collect();
        }

        /*
        |--------------------------------------------------------------------------
        | Recent POS Sales
        |--------------------------------------------------------------------------
        */

        $posSales = PosSale::where('team_id', $team->id)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->with('items')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => 'pos-' . $sale->id,

                    'source' => 'pos',

                    'source_label' => 'POS',

                    'order_id' => null,

                    'reference' => $sale->sale_number,

                    'customer_name' => 'Walk-in Customer',

                    'product_names' => $sale->items
                        ->pluck('product_name')
                        ->filter()
                        ->values()
                        ->all(),

                    'items_count' => $sale->items->sum('quantity'),

                    'ordered_at' => $sale->created_at?->toIso8601String(),

                    'status' => 'paid',

                    'total' => (float) $sale->total_amount,

                    'payment_method' => $sale->payment_method,

                    'can_process' => false,

                    'can_ship' => false,
                ];
            });

        /*
        |--------------------------------------------------------------------------
        | Unified Recent Orders + POS Sales
        |--------------------------------------------------------------------------
        |
        | Both sources are combined and sorted by the actual creation date.
        |
        */

        $source = $request->query('source', 'all');

        $recentOrdersAndSales = collect();

        if (in_array($source, ['all', 'online'])) {
            $recentOrdersAndSales = $recentOrdersAndSales->concat($onlineOrders);
        }

        if (in_array($source, ['all', 'pos'])) {
            $recentOrdersAndSales = $recentOrdersAndSales->concat($posSales);
        }

        $recentOrdersAndSales = $recentOrdersAndSales
            ->sortByDesc('ordered_at')
            ->values()
            ->take(15)
            ->all();

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | Analytics charts (Overview / Reports)
        |--------------------------------------------------------------------------
        */

        $days = collect(range(6, 0))->map(fn ($i) => Carbon::today()->subDays($i));

        $orderShopsQuery = $team->orderShops();
        if ($branchId) {
            // Branch users primarily see POS; still compute online if any scoped later
        }

        $revenueByDay = $days->map(function (Carbon $day) use ($team, $branchId) {
            if ($branchId) {
                $value = (float) \App\Models\PosSale::where('team_id', $team->id)
                    ->where('branch_id', $branchId)
                    ->whereDate('created_at', $day)
                    ->sum('total_amount');
            } else {
                $value = (float) $team->orderShops()
                    ->where('status', OrderShopStatus::Completed)
                    ->whereDate('created_at', $day)
                    ->sum('subtotal');
            }

            return [
                'label' => $day->format('D'),
                'date' => $day->toDateString(),
                'value' => round($value, 2),
            ];
        })->values();

        $ordersByDay = $days->map(function (Carbon $day) use ($team, $branchId) {
            if ($branchId) {
                $count = \App\Models\PosSale::where('team_id', $team->id)
                    ->where('branch_id', $branchId)
                    ->whereDate('created_at', $day)
                    ->count();
            } else {
                $count = $team->orderShops()->whereDate('created_at', $day)->count();
            }

            return [
                'label' => $day->format('D'),
                'date' => $day->toDateString(),
                'value' => $count,
            ];
        })->values();

        if ($branchId) {
            $statusDistribution = [
                ['label' => 'POS Sales', 'value' => (int) \App\Models\PosSale::where('team_id', $team->id)->where('branch_id', $branchId)->count(), 'color' => '#6D5EF8'],
            ];
            $channelSplit = [
                ['label' => 'POS', 'value' => 100, 'color' => '#F97316'],
            ];
        } else {
            $statusCounts = $team->orderShops()
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            $statusDistribution = [
                ['label' => 'Completed', 'value' => (int) ($statusCounts[OrderShopStatus::Completed->value] ?? 0), 'color' => '#16A34A'],
                ['label' => 'Processing', 'value' => (int) ($statusCounts[OrderShopStatus::Processing->value] ?? 0), 'color' => '#3B82F6'],
                ['label' => 'Shipped', 'value' => (int) ($statusCounts[OrderShopStatus::Shipped->value] ?? 0), 'color' => '#6D5EF8'],
                ['label' => 'Pending Payment', 'value' => (int) ($statusCounts[OrderShopStatus::PendingPayment->value] ?? 0), 'color' => '#F59E0B'],
                ['label' => 'Cancelled', 'value' => (int) ($statusCounts[OrderShopStatus::Cancelled->value] ?? 0), 'color' => '#DC2626'],
            ];

            $onlineCount = $team->orderShops()->count();
            $posCount = \App\Models\PosSale::where('team_id', $team->id)->count();
            $channelTotal = max(1, $onlineCount + $posCount);
            $channelSplit = [
                ['label' => 'Online', 'value' => round(($onlineCount / $channelTotal) * 100), 'color' => '#3B82F6', 'count' => $onlineCount],
                ['label' => 'POS', 'value' => round(($posCount / $channelTotal) * 100), 'color' => '#F97316', 'count' => $posCount],
            ];
        }

        return Inertia::render('Seller/Dashboard', [
            'stats' => $stats,
            'supplierLabel' => $supplierLabel,
            'recentOrdersAndSales' => $recentOrdersAndSales,
            'activeSource' => $source,
            'charts' => [
                'revenueByDay' => $revenueByDay,
                'ordersByDay' => $ordersByDay,
                'statusDistribution' => $statusDistribution,
                'channelSplit' => $channelSplit,
            ],
        ]);
    }
}