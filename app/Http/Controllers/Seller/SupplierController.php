<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\SupplierOrder;
use App\Services\InventoryService;
use App\Services\PayMongoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        $this->ensureSupplierSchema();

        $team = $request->user()->currentTeam;

        $suppliers = $team
            ? Supplier::query()
                ->whereHas('teams', function ($query) use ($team) {
                    $query->where('teams.id', $team->id)
                        ->where('supplier_team.active', true);
                })
                ->orderBy('name')
                ->get()
            : collect();

        $selectedSupplier = $suppliers->first();

        $catalog = Product::query()
            ->whereIn('supplier_id', $suppliers->pluck('id'))
            ->with('images', 'variants')
            ->latest()
            ->get()
            ->map(function (Product $product) {
                $product->stock = $product->variants->sum('stock');
                $product->reserved_stock = $product->variants->sum('reserved_stock');
                $product->available_stock = max(0, $product->stock - $product->reserved_stock);
                return $product;
            });

        $acceptedOrders = SupplierOrder::query()
            ->where('team_id', $team->id)
            ->whereIn('status', ['accepted', 'processing', 'completed']);

        $analytics = [
            'total_purchases' => $acceptedOrders->count(),
            'top_supplier' => SupplierOrder::query()
                ->where('team_id', $team->id)
                ->whereIn('status', ['accepted', 'processing', 'completed'])
                ->selectRaw('supplier_id, count(*) as order_count')
                ->groupBy('supplier_id')
                ->orderByDesc('order_count')
                ->first(),
            'total_spend' => $acceptedOrders->sum('total_amount'),
        ];

        if ($analytics['top_supplier']) {
            $analytics['top_supplier_name'] = Supplier::find($analytics['top_supplier']->supplier_id)?->name ?? 'N/A';
        } else {
            $analytics['top_supplier_name'] = 'N/A';
        }

        $catalog = $catalog->filter(function (Product $product) use ($selectedSupplier) {
            return !$selectedSupplier || $product->supplier_id === $selectedSupplier->id;
        })->values();

        $recentOrders = SupplierOrder::query()->where('team_id', $team->id)->latest()->take(10)->get();

        return Inertia::render('Seller/Supplier/Index', [
            'suppliers' => $suppliers,
            'selectedSupplier' => $selectedSupplier,
            'catalog' => $catalog,
            'analytics' => $analytics,
            'recentOrders' => $recentOrders,
        ]);
    }

    public function store(Request $request, PayMongoService $paymongo): RedirectResponse|SymfonyResponse
    {
        $this->ensureSupplierSchema();

        $team = $request->user()->currentTeam;
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:products,id'],
            'items.*.name' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unitPrice' => ['required', 'numeric', 'min:0'],
            'items.*.variantId' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.variant_label' => ['nullable', 'string'],
            'items.*.image_url' => ['nullable', 'string'],
            'items.*.reserved_stock' => ['sometimes', 'integer', 'min:0'],
            'payment_method' => ['nullable', 'string', 'in:card,gcash,paymaya,grab_pay,qrph'],
        ]);

        $supplier = Supplier::findOrFail($data['supplier_id']);

        if (! $team || ! Supplier::query()->forTeam($team)->where('id', $supplier->id)->exists()) {
            return redirect()->route('seller.supplier.index')
                ->withErrors(['supplier' => 'Supplier is not available for your shop.']);
        }

        $inventory = new InventoryService();
        $supplierOrder = null;

        try {
            $supplierOrder = DB::transaction(function () use ($data, $supplier, $team, $inventory) {
                $normalizedItems = [];

                foreach ($data['items'] as $item) {
                    $product = Product::with('variants')->find($item['id']);
                    $quantity = (int) $item['quantity'];

                    if (! $product) {
                        abort(422, "Unable to locate product #{$item['id']}.");
                    }

                    if ($product->variants->isEmpty()) {
                        abort(422, "Unable to locate inventory variant for product {$product->name}.");
                    }

                    $variantId = $item['variantId'] ?? null;
                    $variant = $product->variants->firstWhere('id', $variantId)
                        ?? $product->variants->first(fn ($variant) => $variant->availableStock() > 0)
                        ?? $product->variants->first();

                    if (! $variant) {
                        abort(422, "Unable to locate inventory variant for product {$product->name}.");
                    }

                    $alreadyReserved = (int) ($item['reserved_stock'] ?? 0);
                    $availableStock = (int) $variant->availableStock();
                    $requiredReservation = max(0, min($availableStock, $quantity - $alreadyReserved));

                    if ($requiredReservation > 0) {
                        $inventory->reserve($variant->id, $requiredReservation);
                    }

                    $normalizedItems[] = array_merge($item, [
                        'variantId' => $variant->id,
                        'variant_label' => $item['variant_label'] ?? $variant->label,
                    ]);
                }

                $totalAmount = collect($normalizedItems)->sum(
                    fn (array $item) => (float) $item['quantity'] * (float) $item['unitPrice']
                );

                return SupplierOrder::create([
                    'supplier_id' => $supplier->id,
                    'team_id' => $team->id,
                    'order_number' => 'SUP-'.now()->format('YmdHis').'-'.rand(100, 999),
                    'total_amount' => $totalAmount,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'payment_method' => $data['payment_method'] ?? 'card',
                    'items' => $normalizedItems,
                ]);
            });
        } catch (\RuntimeException $exception) {
            return redirect()->back()->withInput()->withErrors([
                'items' => $exception->getMessage(),
            ]);
        }

        // Pay with PayMongo (live or test depending on configured keys)
        if (! $paymongo->isConfigured()) {
            return redirect()->route('seller.supplier.index')
                ->with('error', $paymongo->configurationError() ?? 'PayMongo is not configured. Order saved as pending.');
        }

        try {
            $lineItems = [];
            foreach ($supplierOrder->items as $item) {
                $lineItems[] = [
                    'name' => mb_substr($item['name'] ?? 'Item', 0, 100),
                    'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                    'amount' => max(100, (int) round(((float) ($item['unitPrice'] ?? 0)) * 100)),
                    'currency' => 'PHP',
                ];
            }

            if (empty($lineItems)) {
                $lineItems[] = [
                    'name' => 'Supplier Order '.$supplierOrder->order_number,
                    'quantity' => 1,
                    'amount' => max(100, (int) round(((float) $supplierOrder->total_amount) * 100)),
                    'currency' => 'PHP',
                ];
            }

            $preferred = null;
            if (! empty($data['payment_method'])) {
                $preferred = [$data['payment_method']];
                if ($data['payment_method'] === 'gcash') {
                    $preferred = ['gcash', 'paymaya', 'grab_pay'];
                }
            }

            $session = $paymongo->createCheckoutSessionForSupplier(
                $supplierOrder,
                $lineItems,
                route('seller.supplier.paymongo.success', ['supplierOrder' => $supplierOrder->id]),
                route('seller.supplier.paymongo.cancel', ['supplierOrder' => $supplierOrder->id]),
                $preferred
            );

            $supplierOrder->update([
                'payment_reference' => $session['id'],
            ]);

            return Inertia::location($session['checkout_url']);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('seller.supplier.index')
                ->with('error', 'PayMongo error: '.$e->getMessage().' Order #'.$supplierOrder->order_number.' was created and is pending payment.');
        }
    }

    public function paymongoSuccess(Request $request, SupplierOrder $supplierOrder, PayMongoService $paymongo): RedirectResponse
    {
        abort_unless($supplierOrder->team_id === $request->user()->currentTeam?->id, 403);

        if ($supplierOrder->payment_status !== 'paid' && $supplierOrder->payment_reference && $paymongo->isConfigured()) {
            try {
                $session = $paymongo->retrieveCheckoutSession($supplierOrder->payment_reference);
                $status = data_get($session, 'data.attributes.status')
                    ?? data_get($session, 'data.attributes.payment_intent.status');
                $payments = data_get($session, 'data.attributes.payments', []);
                $paid = collect($payments)->contains(fn ($p) => data_get($p, 'attributes.status') === 'paid')
                    || in_array($status, ['paid', 'active', 'succeeded'], true);

                if ($paid) {
                    $supplierOrder->update([
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                        'status' => $supplierOrder->status === 'pending' ? 'processing' : $supplierOrder->status,
                    ]);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('seller.supplier.index')
            ->with('success', $supplierOrder->fresh()->payment_status === 'paid'
                ? 'Payment successful! Order #'.$supplierOrder->order_number
                : 'Payment received — confirmation pending. Order #'.$supplierOrder->order_number);
    }

    public function paymongoCancel(Request $request, SupplierOrder $supplierOrder): RedirectResponse
    {
        abort_unless($supplierOrder->team_id === $request->user()->currentTeam?->id, 403);

        return redirect()->route('seller.supplier.index')
            ->with('error', 'Payment was cancelled. Order #'.$supplierOrder->order_number.' remains pending.');
    }

    public function reserveProduct(Request $request, Product $product)
    {
        $this->ensureSupplierSchema();

        $team = $request->user()->currentTeam;

        if (!$team) {
            throw ValidationException::withMessages([
                'items' => 'Please select a supplier first.',
            ]);
        }

        $supplier = Supplier::query()
            ->forTeam($team)
            ->where('id', $product->supplier_id)
            ->first();

        if (!$supplier) {
            throw ValidationException::withMessages([
                'items' => 'Product could not be found.',
            ]);
        }

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $variants = ProductVariant::where('product_id', $product->id)
            ->lockForUpdate()
            ->get();

        if ($variants->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Product could not be found.',
            ]);
        }

        $availableStock = $variants->sum(fn (ProductVariant $variant) => $variant->availableStock());

        if ($data['quantity'] <= 0) {
            throw ValidationException::withMessages([
                'items' => 'Please enter a valid reservation quantity.',
            ]);
        }

        if ($availableStock > 0 && $data['quantity'] <= $availableStock) {
            throw ValidationException::withMessages([
                'items' => 'Requested quantity is available in stock. Use the normal purchase order process instead.',
            ]);
        }

        $inventory = new InventoryService();

        try {
            $inventory->reserveByProduct($product->id, $data['quantity']);
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages([
                'items' => $exception->getMessage(),
            ]);
        }

        return redirect()->back()->with('success', 'Product successfully reserved.');
    }

    private function ensureSupplierSchema(): void
    {
        // Schema is managed via migrations. Runtime schema creation has been removed
        // to keep migrations as the single source of truth.
        return;
    }
}
