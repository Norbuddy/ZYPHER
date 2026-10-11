<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\Seller\InventoryController;
use App\Http\Controllers\Seller\DashboardController;
use App\Http\Controllers\Seller\MessageController;
use App\Http\Controllers\Seller\OrderShopController;
use App\Http\Controllers\Seller\ProductController;
use App\Http\Controllers\Seller\ReportController;
use App\Http\Controllers\Seller\SupplierController;
use App\Http\Controllers\Seller\VoucherController;
use App\Http\Controllers\Supplier\InventoryController as SupplierInventoryController;
use App\Http\Controllers\Supplier\MessageController as SupplierMessageController;
use App\Http\Controllers\Supplier\TeamController as SupplierTeamController;
use App\Models\Supplier;
use App\Models\SupplierOrder;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'is.supplier'])
    ->group(function () {
        Route::get('/supplier/dashboard', function () {
            $user = request()->user();
            $supplier = Supplier::query()->where('team_id', $user?->currentTeam?->id)->first();

            $orders = $supplier
                ? SupplierOrder::query()->where('supplier_id', $supplier->id)->latest()->get()
                : collect();

            /** @var \Illuminate\Support\Collection<int, \App\Models\ProductVariant> $variants */
            $variants = $supplier
                ? ProductVariant::query()->with('product')->whereHas('product', fn ($query) => $query->where('supplier_id', $supplier->id))->get()
                : collect();

            $lowStockCount = $variants->filter(fn ($variant) => $variant->availableStock() > 0 && $variant->availableStock() <= 5)->count();
            $outOfStockCount = $variants->filter(fn ($variant) => $variant->availableStock() <= 0)->count();

            $acceptedOrders = $orders->whereIn('status', ['accepted', 'processing', 'completed']);

            $stats = [
                'total_orders' => $acceptedOrders->count(),
                'total_revenue' => (float) $acceptedOrders->sum('total_amount'),
                'inventory_products' => $variants->pluck('product_id')->unique()->count(),
                'low_stock_count' => $lowStockCount,
                'out_of_stock_count' => $outOfStockCount,
                'inventory_value' => number_format($variants->sum(fn ($variant) => $variant->availableStock() * (float) $variant->price), 2),
            ];

            $logs = $orders->take(8)->map(function (SupplierOrder $order) use ($supplier) {
                $items = is_array($order->items) ? $order->items : [];

                return [
                    'shop_name' => $order->supplier?->name ?? $supplier?->name ?? 'Unknown shop',
                    'product_name' => !empty($items) ? collect($items)->pluck('name')->implode(', ') : $order->order_number,
                    'order_status' => $order->status,
                    'quantity_ordered' => !empty($items) ? collect($items)->sum('quantity') : 0,
                    'date_ordered' => $order->created_at?->format('M d, Y'),
                    'total_amount' => number_format((float) $order->total_amount, 2),
                ];
            });

            $latestAcceptedOrder = $orders->firstWhere('status', 'accepted');
            $latestAcceptedItems = is_array($latestAcceptedOrder?->items ? $latestAcceptedOrder->items : null) ? $latestAcceptedOrder->items : [];
            $stats['latest_order_shop'] = $latestAcceptedOrder ? ($latestAcceptedOrder->shop_name ?? $latestAcceptedOrder->team?->name ?? 'Unknown shop') : null;
            $stats['latest_order_products'] = $latestAcceptedOrder && !empty($latestAcceptedItems)
                ? collect($latestAcceptedItems)->pluck('name')->implode(', ')
                : null;

            // Analytics series for Overview / Reports tabs
            $last7 = collect(range(6, 0))->map(function ($i) use ($orders) {
                $day = now()->subDays($i)->startOfDay();
                $dayOrders = $orders->filter(fn ($o) => $o->created_at && $o->created_at->isSameDay($day));
                return [
                    'label' => $day->format('D'),
                    'date' => $day->toDateString(),
                    'orders' => $dayOrders->count(),
                    'revenue' => round((float) $dayOrders->sum('total_amount'), 2),
                ];
            })->values();

            $statusGroups = $orders->groupBy('status')->map->count();
            $statusDistribution = [
                ['label' => 'Completed', 'value' => (int) ($statusGroups['completed'] ?? 0), 'color' => '#16A34A'],
                ['label' => 'Processing', 'value' => (int) ($statusGroups['processing'] ?? 0), 'color' => '#3B82F6'],
                ['label' => 'Accepted', 'value' => (int) ($statusGroups['accepted'] ?? 0), 'color' => '#6D5EF8'],
                ['label' => 'Pending', 'value' => (int) ($statusGroups['pending'] ?? 0), 'color' => '#F59E0B'],
                ['label' => 'Rejected', 'value' => (int) ($statusGroups['rejected'] ?? 0), 'color' => '#DC2626'],
            ];

            $inventoryMix = [
                ['label' => 'Healthy', 'value' => max(0, $variants->count() - $lowStockCount - $outOfStockCount), 'color' => '#16A34A'],
                ['label' => 'Low stock', 'value' => $lowStockCount, 'color' => '#F59E0B'],
                ['label' => 'Out of stock', 'value' => $outOfStockCount, 'color' => '#DC2626'],
            ];

            return Inertia::render('Supplier/Dashboard', [
                'stats' => $stats,
                'logs' => $logs,
                'charts' => [
                    'ordersByDay' => $last7,
                    'statusDistribution' => $statusDistribution,
                    'inventoryMix' => $inventoryMix,
                ],
            ]);
        })->name('supplier.dashboard');

        Route::get('/supplier/orders', function () {
            $user = request()->user();
            $supplier = Supplier::query()->where('team_id', $user?->currentTeam?->id)->first();

            $orders = $supplier
                ? SupplierOrder::query()->where('supplier_id', $supplier->id)->latest()->get()
                : collect();

            $payload = $orders->map(function (SupplierOrder $order) use ($supplier) {
                $items = is_array($order->items) ? $order->items : [];

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'shop_name' => $order->shop_name ?? $order->shop?->name ?? $order->team?->name ?? $supplier?->name ?? 'Unknown',
                    'team_id' => $order->team_id,
                    'products' => !empty($items) ? collect($items)->pluck('name')->implode(', ') : '',
                    'quantity' => !empty($items) ? collect($items)->sum('quantity') : 0,
                    'total_amount' => number_format((float) $order->total_amount, 2),
                    'date_ordered' => $order->created_at?->format('M d, Y'),
                    'status' => $order->status,
                    'shipment_status' => $order->shipment_status ?? 'pending',
                    'items' => $items,
                ];
            })->values();

            return Inertia::render('Supplier/Orders', [
                'orders' => $payload,
            ]);
        })->name('supplier.orders.index');

        Route::post('/supplier/orders/{order}/accept', function (SupplierOrder $order) {
            $supplier = Supplier::query()->where('team_id', request()->user()?->currentTeam?->id)->first();
            abort_unless($supplier && $order->supplier_id === $supplier->id, 403);

            $order->status = 'accepted';
            $order->shipment_status = 'pending';
            $order->save();

            return back();
        })->name('supplier.orders.accept');
        Route::post('/supplier/orders/{order}/reject', function (SupplierOrder $order) {
            $supplier = Supplier::query()->where('team_id', request()->user()?->currentTeam?->id)->first();
            abort_unless($supplier && $order->supplier_id === $supplier->id, 403);

            $items = is_array($order->items) ? $order->items : [];
            $inventory = new \App\Services\InventoryService();

            DB::transaction(function () use ($order, $items, $inventory) {
                foreach ($items as $item) {
                    $productId = $item['id'] ?? null;
                    $quantity = isset($item['quantity']) ? (int) $item['quantity'] : 0;

                    if (!$productId || $quantity <= 0) {
                        continue;
                    }

                    $variant = ProductVariant::where('product_id', $productId)->lockForUpdate()->first();
                    if ($variant) {
                        $inventory->release($variant->id, $quantity);
                    }
                }

                $order->status = 'rejected';
                $order->save();
            });

            return back();
        })->name('supplier.orders.reject');

        Route::post('/supplier/orders/{order}/processing', function (SupplierOrder $order) {
            $order->status = 'processing';
            $order->save();

            return back();
        })->name('supplier.orders.processing');

        Route::get('/supplier/products', [\App\Http\Controllers\Supplier\ProductController::class, 'index'])->name('supplier.products.index');
        Route::get('/supplier/products/create', [\App\Http\Controllers\Supplier\ProductController::class, 'create'])->name('supplier.products.create');
        Route::post('/supplier/products', [\App\Http\Controllers\Supplier\ProductController::class, 'store'])->name('supplier.products.store');
        Route::get('/supplier/products/{product}/edit', [\App\Http\Controllers\Supplier\ProductController::class, 'edit'])->name('supplier.products.edit');
        Route::patch('/supplier/products/{product}', [\App\Http\Controllers\Supplier\ProductController::class, 'update'])->name('supplier.products.update');
        Route::delete('/supplier/products/{product}', [\App\Http\Controllers\Supplier\ProductController::class, 'destroy'])->name('supplier.products.destroy');

        Route::get('/supplier/shipment', [\App\Http\Controllers\Supplier\ShipmentController::class, 'index'])->name('supplier.shipment.index');
        Route::post('/supplier/shipment/{order}/status', [\App\Http\Controllers\Supplier\ShipmentController::class, 'updateStatus'])->name('supplier.shipment.update_status');
        Route::post('/supplier/shipment/{order}/tracking', [\App\Http\Controllers\Supplier\ShipmentController::class, 'setTracking'])->name('supplier.shipment.set_tracking');

        Route::get('/supplier/inventory', [SupplierInventoryController::class, 'index'])->name('supplier.inventory.index');
        Route::get('/supplier/inventory/{variant}/edit', [SupplierInventoryController::class, 'edit'])->name('supplier.inventory.edit');
        Route::patch('/supplier/inventory/{variant}', [SupplierInventoryController::class, 'updateStock'])->name('supplier.inventory.update');

        Route::get('/supplier/messages', [SupplierMessageController::class, 'index'])->name('supplier.messages.index');
        Route::post('/supplier/messages/start', [SupplierMessageController::class, 'start'])->name('supplier.messages.start');
        Route::get('/supplier/messages/{conversation}', [SupplierMessageController::class, 'show'])->name('supplier.messages.show');
        Route::post('/supplier/messages/{conversation}', [SupplierMessageController::class, 'store'])->name('supplier.messages.store');
        Route::get('/supplier/teams', [SupplierTeamController::class, 'index'])->name('supplier.teams.index');
        Route::post('/supplier/teams', [SupplierTeamController::class, 'bulkSupply'])->name('supplier.teams.bulk_supply');
        Route::post('/supplier/teams/{team}/supply', [SupplierTeamController::class, 'supply'])->name('supplier.teams.supply');
        Route::delete('/supplier/teams/{team}/supply', [SupplierTeamController::class, 'remove'])->name('supplier.teams.remove');
    });

Route::middleware(['auth', 'verified', 'is.approved.seller', 'branch.access'])
    ->prefix('seller')
    ->name('seller.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
        Route::get('/pos', [\App\Http\Controllers\Seller\PosController::class, 'index'])->name('pos.index');
        Route::post('/pos/sale', [\App\Http\Controllers\Seller\PosController::class, 'store'])->name('pos.store');
        Route::get('/pos/paymongo/success/{sale}', [\App\Http\Controllers\Seller\PosController::class, 'paymongoSuccess'])->name('pos.paymongo.success');
        Route::get('/pos/paymongo/cancel/{sale}', [\App\Http\Controllers\Seller\PosController::class, 'paymongoCancel'])->name('pos.paymongo.cancel');
        Route::post('/products/pos', [ProductController::class, 'storePos'])->name('products.store.pos');
        
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::patch('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        Route::get('/orders', [OrderShopController::class, 'index'])->name('orders.index');
        Route::post('/orders/{orderShop}/processing', [OrderShopController::class, 'markProcessing'])->name('orders.processing');
        Route::post('/orders/{orderShop}/shipped', [OrderShopController::class, 'markShipped'])->name('orders.shipped');

        Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
        Route::post('/vouchers', [VoucherController::class, 'store'])->name('vouchers.store');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

        Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('messages.show');
        Route::post('/messages/{conversation}', [MessageController::class, 'store'])->name('messages.store');
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/items', [InventoryController::class, 'storeItem'])->name('inventory.items.store');
        Route::post('/inventory/import-shop-product', [InventoryController::class, 'importShopProduct'])->name('inventory.shop-products.import');
        Route::get('/supplier', [SupplierController::class, 'index'])->name('supplier.index');
        Route::post('/supplier/orders', [SupplierController::class, 'store'])->name('supplier.orders.store');
        Route::get('/supplier/paymongo/success/{supplierOrder}', [SupplierController::class, 'paymongoSuccess'])->name('supplier.paymongo.success');
        Route::get('/supplier/paymongo/cancel/{supplierOrder}', [SupplierController::class, 'paymongoCancel'])->name('supplier.paymongo.cancel');
        Route::post('/supplier/products/{product}/reserve', [SupplierController::class, 'reserveProduct'])->name('supplier.products.reserve');

        Route::post('/cash/add', function (\Illuminate\Http\Request $request) {
            $team = $request->user()->currentTeam;

            $data = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'note' => 'nullable|string|max:255',
            ]);

            \App\Models\CashAdjustment::create([
                'team_id' => $team->id,
                'user_id' => $request->user()->id,
                'type' => 'deposit',
                'amount' => $data['amount'],
                'note' => $data['note'] ?? null,
            ]);

            return back()->with('success', 'Cash added.');
        })->name('cash.add');

        Route::post('/supplier/orders/{order}/confirm-received', function (\Illuminate\Http\Request $request, SupplierOrder $order) {
            $team = $request->user()->currentTeam;
            abort_unless($team && $order->team_id === $team->id, 403);

            $data = $request->validate([
                'received' => ['sometimes', 'array'],
                'received.*' => ['integer', 'min:0'],
                'note' => ['sometimes', 'nullable', 'string', 'max:500'],
            ]);

            if ($order->status === 'completed' || $order->received_at !== null) {
                abort(409, 'This order has already been confirmed as received.');
            }

            DB::transaction(function () use ($order, $team, $data) {
                $locked = SupplierOrder::where('id', $order->id)->lockForUpdate()->first();

                if (!$locked || $locked->status === 'completed' || $locked->received_at !== null) {
                    abort(409, 'This order has already been confirmed as received.');
                }

                $items = is_array($locked->items) ? $locked->items : [];
                $receivedInput = $data['received'] ?? [];

                foreach ($items as $item) {
                    $productId = $item['id'] ?? null;
                    $orderedQty = isset($item['quantity']) ? (int) $item['quantity'] : 0;

                    if (!$productId || $orderedQty <= 0) {
                        continue;
                    }

                    $receivedQty = array_key_exists($productId, $receivedInput)
                        ? (int) $receivedInput[$productId]
                        : $orderedQty;
                    $receivedQty = max(0, min($receivedQty, $orderedQty));

                    if ($receivedQty <= 0) {
                        continue;
                    }

                    $inventory = \App\Models\SellerInventory::firstOrNew([
                        'seller_id' => $team->id,
                        'product_id' => $productId,
                    ]);
                    $inventory->quantity = ($inventory->exists ? $inventory->quantity : 0) + $receivedQty;
                    $inventory->source_type = 'supplier';
                    $inventory->source_id = $locked->id;
                    $inventory->save();

                    \App\Models\InventoryLog::create([
                        'seller_id' => $team->id,
                        'product_id' => $productId,
                        'type' => 'in',
                        'quantity' => $receivedQty,
                        'source_type' => 'supplier',
                        'source_id' => $locked->id,
                        'note' => $receivedQty < $orderedQty
                            ? "Partial delivery from order {$locked->order_number} ({$receivedQty}/{$orderedQty} received)"
                            : "Received in full from order {$locked->order_number}",
                    ]);
                }

                $locked->status = 'completed';
                $locked->received_at = now();
                if (!empty($data['note'])) {
                    $locked->receiving_note = $data['note'];
                }
                $locked->save();
            });

            return back()->with('success', 'Order marked as received. Inventory updated.');
        })->name('supplier.orders.confirm-received');
        Route::post('/supplier/orders/{order}/pay', function (\Illuminate\Http\Request $request, SupplierOrder $order) {
            $team = $request->user()->currentTeam;
            abort_unless($team && $order->team_id === $team->id, 403);

            if ($order->paid_at !== null) {
                abort(409, 'This order has already been paid.');
            }

            if (!in_array($order->status, ['accepted', 'processing', 'completed'])) {
                abort(422, 'This order cannot be paid yet.');
            }

            $order->paid_at = now();
            $order->save();

            return back()->with('success', 'Supplier payment recorded.');
        })->name('seller.supplier.orders.pay');

        Route::post('/supplier/pay-outstanding', function (\Illuminate\Http\Request $request) {
            $team = $request->user()->currentTeam;

            $request->validate(['method' => ['required', 'in:cash']]);

            $outstanding = SupplierOrder::where('team_id', $team->id)
                ->whereIn('status', ['accepted', 'processing', 'completed'])
                ->whereNull('paid_at')
                ->get();

            if ($outstanding->isEmpty()) {
                return back()->with('success', 'No outstanding balance to pay.');
            }

            \Illuminate\Support\Facades\DB::transaction(function () use ($outstanding) {
                foreach ($outstanding as $order) {
                    $locked = SupplierOrder::where('id', $order->id)->lockForUpdate()->first();
                    if ($locked && $locked->paid_at === null) {
                        $locked->paid_at = now();
                        $locked->save();
                    }
                }
            });

            return back()->with('success', 'Supplier payment recorded.');
        })->name('supplier.pay-outstanding');

        Route::post('/inventory/manual', function (\Illuminate\Http\Request $request) {
            $team = $request->user()->currentTeam;
            $branchId = $request->user()->branch_id;
            $data = $request->validate([
                'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
                'quantity' => ['required', 'integer', 'min:1'],
            ]);

            $sourceVariant = \App\Models\ProductVariant::with('product')
                ->whereKey($data['product_variant_id'])
                ->firstOrFail();
            abort_unless(
                (int) $sourceVariant->product->team_id === (int) $team->id
                    && ($sourceVariant->branch_id === null || (int) $sourceVariant->branch_id === (int) $branchId),
                403
            );

            $existing = \App\Models\SellerInventory::where('seller_id', $team->id)
                ->where('product_id', $sourceVariant->product_id)
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->first();

            if ($existing && $existing->source_type === 'supplier') {
                return back()->withErrors([
                    'product_id' => 'This product already has inventory sourced from a supplier order and cannot be added manually.',
                ]);
            }

            DB::transaction(function () use ($existing, $sourceVariant, $team, $branchId, $data) {
                $variant = $sourceVariant;

                if ($branchId && $sourceVariant->branch_id === null) {
                    $variant = \App\Models\ProductVariant::where('product_id', $sourceVariant->product_id)
                        ->where('branch_id', $branchId)
                        ->where('label', $sourceVariant->label)
                        ->first();

                    if (! $variant) {
                        $variant = \App\Models\ProductVariant::create([
                            'product_id' => $sourceVariant->product_id,
                            'branch_id' => $branchId,
                            'sku' => $sourceVariant->sku . '-B' . $branchId,
                            'label' => $sourceVariant->label,
                            'price' => $sourceVariant->price,
                            'stock' => 0,
                            'reserved_stock' => 0,
                            'is_active' => $sourceVariant->is_active,
                        ]);
                    }
                }

                $variant->increment('stock', $data['quantity']);

                $inventory = $existing ?? new \App\Models\SellerInventory([
                    'seller_id' => $team->id,
                    'product_id' => $sourceVariant->product_id,
                    'branch_id' => $branchId,
                ]);
                $inventory->quantity = ($inventory->exists ? $inventory->quantity : 0) + $data['quantity'];
                $inventory->source_type = 'manual';
                $inventory->source_id = null;
                $inventory->save();

                \App\Models\InventoryLog::create([
                    'seller_id' => $team->id,
                    'product_id' => $sourceVariant->product_id,
                    'branch_id' => $branchId,
                    'type' => 'in',
                    'quantity' => $data['quantity'],
                    'source_type' => 'manual',
                    'source_id' => null,
                    'note' => 'Manually added by seller',
                ]);
            });

            return back()->with('success', 'Product added to inventory.');
        })->name('inventory.manual.store');

        Route::post('/shop/profile', [\App\Http\Controllers\Seller\ShopProfileController::class, 'update'])
            ->name('seller.shop.update');

    });

// Note: "Start Selling" (team creation) uses Jetstream's default team creation route,
// which is why it isn't redefined here. After a team is created its status defaults
// to 'pending' via the migration, so it won't pass the is.approved.seller check
// until an admin approves it in /admin/shops.
