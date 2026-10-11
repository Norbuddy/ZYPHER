<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\OrderShopStatus;
use App\Enums\ShopStatus;
use App\Models\OrderShop;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $completed = OrderShopStatus::Completed;

        $stats = [
            'total_revenue' => (float) OrderShop::where('status', $completed)->sum('subtotal'),
            'total_commission' => (float) OrderShop::where('status', $completed)->sum('platform_fee'),
            'active_shops' => Team::where('status', ShopStatus::Approved)->where('personal_team', false)->count(),
            'pending_shops' => Team::where('status', ShopStatus::Pending)->count(),
            'total_users' => User::count(),
            'total_orders' => OrderShop::count(),
        ];

        // Last 7 days revenue (area / bar chart)
        $days = collect(range(6, 0))->map(fn ($i) => Carbon::today()->subDays($i));
        $revenueByDay = $days->map(function (Carbon $day) use ($completed) {
            $sum = (float) OrderShop::where('status', $completed)
                ->whereDate('created_at', $day)
                ->sum('subtotal');

            return [
                'label' => $day->format('D'),
                'date' => $day->toDateString(),
                'value' => round($sum, 2),
            ];
        })->values();

        // Order status distribution (donut)
        $statusCounts = OrderShop::query()
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

        // Top shops by GMV (bar)
        $topShops = OrderShop::query()
            ->select('team_id', DB::raw('SUM(subtotal) as gmv'), DB::raw('COUNT(*) as orders'))
            ->where('status', $completed)
            ->groupBy('team_id')
            ->orderByDesc('gmv')
            ->limit(6)
            ->with('team:id,name')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->team?->name ?? 'Shop #'.$row->team_id,
                'value' => round((float) $row->gmv, 2),
                'orders' => (int) $row->orders,
            ]);

        // User growth last 6 months (area)
        $months = collect(range(5, 0))->map(fn ($i) => Carbon::today()->startOfMonth()->subMonths($i));
        $userGrowth = $months->map(function (Carbon $month) {
            $count = User::where('created_at', '<=', $month->copy()->endOfMonth())->count();

            return [
                'label' => $month->format('M'),
                'value' => $count,
            ];
        })->values();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'charts' => [
                'revenueByDay' => $revenueByDay,
                'statusDistribution' => $statusDistribution,
                'topShops' => $topShops,
                'userGrowth' => $userGrowth,
            ],
        ]);
    }
}
