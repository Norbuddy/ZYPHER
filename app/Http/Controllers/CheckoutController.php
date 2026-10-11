<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Voucher;
use App\Services\OrderService;
use App\Services\PayMongoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CheckoutController extends Controller
{
    public function index(Request $request): Response
    {
        $cart = $request->user()->cart()->firstOrCreate([]);

        $pm = app(PayMongoService::class);

        return Inertia::render('Checkout/Index', [
            'itemsByShop' => $cart->itemsGroupedByShop(),
            'addresses' => $request->user()->addresses,
            'paymongoTestMode' => $pm->isTestMode(),
            'paymongoConfigured' => $pm->isConfigured(),
            'paymongoError' => $pm->configurationError(),
        ]);
    }

    public function store(Request $request, OrderService $orderService, PayMongoService $paymongo): RedirectResponse|SymfonyResponse
    {
        $data = $request->validate([
            'address_id' => ['required', 'exists:addresses,id'],
            'platform_voucher_code' => ['nullable', 'string', 'max:40'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'payment_method' => ['nullable', 'string', 'in:card,gcash,paymaya,grab_pay,qrph'],
        ]);

        $cart = $request->user()->cart;

        if (isset($data['latitude'], $data['longitude'])) {
            \App\Models\Address::where('id', $data['address_id'])
                ->where('user_id', $request->user()->id)
                ->update([
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                ]);
        }

        $platformVoucher = null;

        if (! empty($data['platform_voucher_code'])) {
            $platformVoucher = Voucher::where('code', $data['platform_voucher_code'])
                ->where('owner_type', 'platform')
                ->first();
        }

        try {
            $order = $orderService->placeOrder(
                $request->user(),
                $cart,
                $data['address_id'],
                $platformVoucher
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['checkout' => $e->getMessage()]);
        }

        if (! $paymongo->isConfigured()) {
            $hint = $paymongo->configurationError() ?? 'PayMongo keys are not set.';

            // Keep order as pending_payment so user can fix keys and retry
            return redirect()->route('account.orders.show', $order->id)
                ->withErrors(['checkout' => $hint]);
        }

        try {
            $lineItems = $this->buildLineItems($order);

            $preferred = null;
            if (! empty($data['payment_method'])) {
                $preferred = [$data['payment_method']];
                // Still offer related channels for e-wallets
                if ($data['payment_method'] === 'gcash') {
                    $preferred = ['gcash', 'paymaya', 'grab_pay'];
                }
            }

            $session = $paymongo->createCheckoutSession(
                $order,
                $lineItems,
                route('checkout.paymongo.success', ['order' => $order->id]),
                route('checkout.paymongo.cancel', ['order' => $order->id]),
                $preferred,
            );

            $order->update([
                'payment_reference' => $session['id'],
            ]);

            // Inertia XHR posts need Inertia::location for external URLs
            return Inertia::location($session['checkout_url']);
        } catch (\Throwable $e) {
            report($e);

            // Surface the real error so the user can see why redirect failed
            return redirect()->route('account.orders.show', $order->id)
                ->with('error', 'PayMongo error: '.$e->getMessage())
                ->withErrors(['checkout' => 'PayMongo error: '.$e->getMessage()]);
        }
    }

    public function success(Request $request, Order $order, OrderService $orderService, PayMongoService $paymongo): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        // Best-effort: if webhook already marked paid, just show success.
        // Otherwise try to retrieve session and confirm.
        if ($order->payment_status !== \App\Enums\PaymentStatus::Paid && $order->payment_reference && $paymongo->isConfigured()) {
            try {
                $session = $paymongo->retrieveCheckoutSession($order->payment_reference);
                $status = data_get($session, 'data.attributes.status')
                    ?? data_get($session, 'data.attributes.payment_intent.status');

                $payments = data_get($session, 'data.attributes.payments', []);
                $paid = collect($payments)->contains(fn ($p) => data_get($p, 'attributes.status') === 'paid')
                    || in_array($status, ['paid', 'active', 'succeeded'], true);

                if ($paid) {
                    $orderService->confirmPayment($order);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('account.orders.show', $order->id)
            ->with('success', $order->fresh()->payment_status === \App\Enums\PaymentStatus::Paid
                ? 'Payment successful! Order #'.$order->order_number
                : 'Payment received — confirmation pending. Order #'.$order->order_number);
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return redirect()->route('account.orders.show', $order->id)
            ->with('error', 'Payment was cancelled. You can try again from your order page. Order #'.$order->order_number);
    }

    protected function buildLineItems(Order $order): array
    {
        $order->loadMissing('orderShops.items');

        $items = [];
        foreach ($order->orderShops as $shop) {
            foreach ($shop->items as $item) {
                $items[] = [
                    'name' => mb_substr($item->product_name ?? $item->name ?? 'Item', 0, 100),
                    'quantity' => max(1, (int) $item->quantity),
                    // OrderItem stores the price in price_at_purchase (not unit_price)
                    'amount' => (int) round(((float) ($item->price_at_purchase ?? $item->unit_price ?? 0)) * 100),
                    'currency' => 'PHP',
                ];
            }
        }

        if (empty($items)) {
            $items[] = [
                'name' => 'Order '.$order->order_number,
                'quantity' => 1,
                'amount' => max(100, (int) round(((float) $order->total_amount) * 100)),
                'currency' => 'PHP',
            ];
        }

        // PayMongo rejects zero / sub-100-centavo line items
        foreach ($items as &$line) {
            $line['amount'] = max(100, (int) $line['amount']);
        }
        unset($line);

        return $items;
    }
}
