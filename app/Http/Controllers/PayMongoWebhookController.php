<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PosSale;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use App\Services\OrderService;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PayMongoWebhookController extends Controller
{
    public function __invoke(Request $request, PayMongoService $paymongo, OrderService $orders): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('Paymongo-Signature') ?? $request->header('PayMongo-Signature');

        if (! $paymongo->verifyWebhookSignature($payload, $signature)) {
            Log::warning('PayMongo webhook: invalid signature');

            return response('Invalid signature', 400);
        }

        $data = $request->json()->all();
        $eventType = data_get($data, 'data.attributes.type')
            ?? data_get($data, 'data.attributes.event')
            ?? data_get($data, 'type');

        $resource = data_get($data, 'data.attributes.data')
            ?? data_get($data, 'data.attributes')
            ?? data_get($data, 'data');

        Log::info('PayMongo webhook received', [
            'type' => $eventType,
            'livemode' => data_get($data, 'data.attributes.livemode'),
        ]);

        // Normalize common event names
        $type = strtolower((string) $eventType);

        if (in_array($type, [
            'checkout_session.payment.paid',
            'payment.paid',
            'payment_intent.succeeded',
            'source.chargeable',
        ], true) || str_contains($type, 'payment.paid') || str_contains($type, 'paid')) {
            $this->handlePaid($resource, $data, $orders);
        }

        if (in_array($type, [
            'payment.failed',
            'payment_intent.payment_failed',
            'checkout_session.payment.failed',
        ], true) || str_contains($type, 'failed')) {
            $this->handleFailed($resource, $data, $orders);
        }

        return response('OK', 200);
    }

    protected function handlePaid(mixed $resource, array $fullPayload, OrderService $orders): void
    {
        $metadata = data_get($resource, 'attributes.metadata')
            ?? data_get($resource, 'metadata')
            ?? data_get($fullPayload, 'data.attributes.metadata')
            ?? [];

        if (! empty($metadata['pos_sale_id'])) {
            $pos = PosSale::find($metadata['pos_sale_id']);
            if ($pos) {
                $this->handlePosPaid($pos);

                return;
            }
        }

        $order = $this->resolveOrder($resource, $fullPayload);

        if (! $order) {
            Log::warning('PayMongo paid event: order not found', ['resource' => $resource]);

            return;
        }

        if ($order->payment_status === \App\Enums\PaymentStatus::Paid) {
            return; // idempotent
        }

        $reference = data_get($resource, 'id')
            ?? data_get($resource, 'attributes.payment_intent_id')
            ?? data_get($fullPayload, 'data.id')
            ?? $order->payment_reference;

        $order->update([
            'payment_reference' => is_string($reference) ? $reference : $order->payment_reference,
        ]);

        $orders->confirmPayment($order);

        Log::info('PayMongo: order marked paid', ['order_id' => $order->id, 'order_number' => $order->order_number]);
    }

    protected function handlePosPaid(PosSale $sale): void
    {
        if ($sale->payment_status === 'paid') {
            return;
        }

        DB::transaction(function () use ($sale) {
            $sale->load('items');
            foreach ($sale->items as $item) {
                if ($item->product_variant_id) {
                    $variant = ProductVariant::where('id', $item->product_variant_id)->lockForUpdate()->first();
                    if ($variant) {
                        // Move from reserved to sold
                        if (($variant->reserved_stock ?? 0) >= $item->quantity) {
                            $variant->decrement('reserved_stock', $item->quantity);
                        }
                        $variant->decrement('stock', $item->quantity);
                    }
                }
            }
            $sale->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);
        });

        Log::info('PayMongo: POS sale marked paid', ['pos_sale_id' => $sale->id]);
    }

    protected function handleFailed(mixed $resource, array $fullPayload, OrderService $orders): void
    {
        $order = $this->resolveOrder($resource, $fullPayload);

        if (! $order) {
            return;
        }

        if ($order->payment_status === \App\Enums\PaymentStatus::Paid) {
            return;
        }

        $orders->releaseUnpaidOrder($order);

        Log::info('PayMongo: order payment failed', ['order_id' => $order->id]);
    }

    protected function resolveOrder(mixed $resource, array $fullPayload): ?Order
    {
        $metadata = data_get($resource, 'attributes.metadata')
            ?? data_get($resource, 'metadata')
            ?? data_get($fullPayload, 'data.attributes.metadata')
            ?? [];

        $orderId = $metadata['order_id'] ?? null;
        $orderNumber = $metadata['order_number']
            ?? data_get($resource, 'attributes.reference_number')
            ?? data_get($resource, 'reference_number');

        if ($orderId) {
            $order = Order::find($orderId);
            if ($order) {
                return $order;
            }
        }

        if ($orderNumber) {
            return Order::where('order_number', $orderNumber)->first();
        }

        return null;
    }
}
