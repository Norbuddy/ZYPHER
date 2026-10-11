<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\ProductVariant;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;
        $branchId = $request->user()->branch_id;

        $products = \App\Models\Product::where('team_id', $team?->id)
            ->when($branchId, fn ($query) => $query->where(function ($products) use ($branchId) {
                $products->whereHas('variants', fn ($variants) => $variants->where('branch_id', $branchId));
            }))
            ->where(function ($query) {
                $query->where('pos_available', true)
                    ->orWhere('status', \App\Enums\ProductStatus::Active);
            })
            ->with(['variants' => fn ($q) => $q->when($branchId, fn ($variants) => $variants->where('branch_id', $branchId)), 'images' => fn ($q) => $q->where('is_primary', true)])
            ->get();

        $recentSales = PosSale::where('team_id', $team?->id)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->with('items')
            ->latest()
            ->take(10)
            ->get();

        $paymongo = app(PayMongoService::class);

        return Inertia::render('Seller/Pos/Index', [
            'products' => $products,
            'recentSales' => $recentSales,
            'branchName' => $request->user()->branch?->name ?? $team?->name,
            'paymongoConfigured' => $paymongo->isConfigured(),
            'paymongoTestMode' => $paymongo->isTestMode(),
            'paymongoError' => $paymongo->configurationError(),
        ]);
    }

    public function store(Request $request, PayMongoService $paymongo)
    {
        $team = $request->user()->currentTeam;
        $branchId = $request->user()->branch_id;

        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|integer|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,ewallet,atm,card,gcash,paymaya',
        ]);

        $method = $data['payment_method'];
        // Normalize UI labels
        if (in_array($method, ['atm', 'card'], true)) {
            $method = 'card';
        }
        if ($method === 'ewallet') {
            $method = 'gcash'; // default e-wallet channel; customer picks on PayMongo page
        }

        $needsOnlinePay = in_array($method, ['card', 'gcash', 'paymaya', 'ewallet', 'atm'], true)
            && $method !== 'cash'
            && $data['payment_method'] !== 'cash';

        // Cash = immediate settle (no PayMongo)
        if ($data['payment_method'] === 'cash') {
            $sale = $this->createSale($data, $team, $branchId, $request, 'cash', 'paid', null);

            return back()->with([
                'success' => 'Sale completed (cash).',
                'receipt' => $sale->load('items', 'branch'),
            ]);
        }

        // Online methods: create pending sale, then PayMongo session
        $sale = $this->createSale($data, $team, $branchId, $request, $data['payment_method'], 'pending', null);

        if (! $paymongo->isConfigured()) {
            $this->voidPendingSale($sale);

            return back()->withErrors([
                'payment' => $paymongo->configurationError()
                    ?? 'PayMongo is not configured. Set a real PAYMONGO_SECRET_KEY=sk_test_… in .env then run php artisan config:clear',
            ]);
        }

        try {
            $targetCentavos = (int) round(((float) $sale->total_amount) * 100);
            $sumCentavos = 0;
            $lineItems = $sale->items->map(function ($item) use (&$sumCentavos) {
                $amount = (int) round(((float) $item->unit_price) * 100);
                $qty = (int) $item->quantity;
                $sumCentavos += ($amount * $qty);

                return [
                    'name' => mb_substr($item->product_name.' '.($item->variant_label ?? ''), 0, 100),
                    'quantity' => $qty,
                    'amount' => max(100, $amount),
                    'currency' => 'PHP',
                ];
            })->values()->all();

            if ($sumCentavos !== $targetCentavos || empty($lineItems)) {
                $lineItems = [[
                    'name' => 'POS Sale '.$sale->sale_number,
                    'quantity' => 1,
                    'amount' => max(100, $targetCentavos),
                    'currency' => 'PHP',
                ]];
            }

            $methods = match ($data['payment_method']) {
                'card', 'atm' => ['card'],
                'gcash', 'ewallet' => ['gcash', 'paymaya', 'grab_pay'],
                'paymaya' => ['paymaya'],
                default => ['card', 'gcash', 'paymaya'],
            };

            // Temporarily override allowed methods for this session via config isn't ideal;
            // PayMongoService uses config list — pass via createCheckoutSession customization.
            $session = $paymongo->createCheckoutSessionForPos(
                $sale,
                $lineItems,
                $methods,
                route('seller.pos.paymongo.success', $sale),
                route('seller.pos.paymongo.cancel', $sale),
            );

            $sale->update(['payment_reference' => $session['id']]);

            // Hard redirect to PayMongo Hosted Checkout (same as online store)
            return Inertia::location($session['checkout_url']);
        } catch (\Throwable $e) {
            report($e);
            // Roll back pending sale stock? For simplicity release by deleting pending sale
            $this->voidPendingSale($sale);

            return back()->withErrors(['payment' => 'Could not start PayMongo: '.$e->getMessage()]);
        }
    }

    public function paymongoSuccess(Request $request, PosSale $sale, PayMongoService $paymongo)
    {
        abort_unless($sale->team_id === $request->user()->currentTeam?->id, 403);

        if ($sale->payment_status !== 'paid' && $paymongo->isConfigured() && $sale->payment_reference) {
            try {
                $session = $paymongo->retrieveCheckoutSession($sale->payment_reference);
                $payments = data_get($session, 'data.attributes.payments', []);
                $paid = collect($payments)->contains(fn ($p) => data_get($p, 'attributes.status') === 'paid');

                $sessionStatus = data_get($session, 'data.attributes.status');
                if ($paid || in_array($sessionStatus, ['paid', 'succeeded'], true)) {
                    $sale->update([
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('seller.pos.index')->with([
            'success' => $sale->fresh()->payment_status === 'paid'
                ? 'Payment successful.'
                : 'Payment submitted — waiting for confirmation.',
            'receipt' => $sale->fresh()->load('items', 'branch'),
        ]);
    }

    public function paymongoCancel(Request $request, PosSale $sale)
    {
        abort_unless($sale->team_id === $request->user()->currentTeam?->id, 403);

        if ($sale->payment_status === 'pending') {
            $this->voidPendingSale($sale);
        }

        return redirect()->route('seller.pos.index')
            ->with('error', 'Payment cancelled. Sale was not completed.');
    }

    protected function createSale(array $data, $team, $branchId, Request $request, string $method, string $status, ?string $reference): PosSale
    {
        return DB::transaction(function () use ($data, $team, $branchId, $request, $method, $status, $reference) {
            $total = 0;
            $lineItems = [];

            foreach ($data['items'] as $item) {
                $variant = ProductVariant::with('product')
                    ->where('id', $item['product_variant_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                abort_unless(
                    $variant->product->team_id === $team?->id
                        && (! $branchId || (int) $variant->branch_id === (int) $branchId)
                        && ($variant->product->pos_available || $variant->product->status === \App\Enums\ProductStatus::Active),
                    403
                );

                $available = $variant->stock - ($variant->reserved_stock ?? 0);
                if ($item['quantity'] > $available) {
                    abort(422, "Not enough stock for {$variant->product->name}.");
                }

                // Only deduct stock when paid (cash) or hold for pending online
                if ($status === 'paid') {
                    $variant->decrement('stock', $item['quantity']);
                } else {
                    // Reserve
                    $variant->increment('reserved_stock', $item['quantity']);
                }

                $lineTotal = $variant->price * $item['quantity'];
                $total += $lineTotal;

                $lineItems[] = [
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'variant_label' => $variant->label,
                    'quantity' => $item['quantity'],
                    'unit_price' => $variant->price,
                    'line_total' => $lineTotal,
                ];
            }

            $sale = PosSale::create([
                'team_id' => $team->id,
                'user_id' => $request->user()->id,
                'branch_id' => $branchId,
                'sale_number' => 'POS-'.now()->format('YmdHis').'-'.Str::upper(Str::random(3)),
                'total_amount' => $total,
                'payment_method' => $method,
                'payment_status' => $status,
                'payment_reference' => $reference,
                'paid_at' => $status === 'paid' ? now() : null,
            ]);

            foreach ($lineItems as $line) {
                PosSaleItem::create(array_merge($line, ['pos_sale_id' => $sale->id]));
            }

            return $sale->load('items');
        });
    }

    protected function voidPendingSale(PosSale $sale): void
    {
        DB::transaction(function () use ($sale) {
            $sale->load('items');
            foreach ($sale->items as $item) {
                if ($item->product_variant_id) {
                    ProductVariant::where('id', $item->product_variant_id)
                        ->where('reserved_stock', '>=', $item->quantity)
                        ->decrement('reserved_stock', $item->quantity);
                }
            }
            $sale->update(['payment_status' => 'cancelled']);
            $sale->items()->delete();
            $sale->delete();
        });
    }
}
