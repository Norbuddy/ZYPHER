<?php

    use App\Http\Controllers\SellerApplicationController;
    use App\Http\Controllers\SupplierApplicationController;
    use App\Http\Controllers\Auth\OtpPasswordResetController;
    use App\Http\Controllers\AddressController;
    use App\Http\Controllers\CartController;
    use App\Http\Controllers\CheckoutController;
    use App\Http\Controllers\PayMongoWebhookController;
    use App\Http\Controllers\HomeController;
    use App\Http\Controllers\MessageController;
    use App\Http\Controllers\OrderController;
    use App\Http\Controllers\ProductController;
    use App\Http\Controllers\ReviewController;
    use App\Http\Controllers\SearchController;
    use App\Http\Controllers\SupportController;
    use App\Http\Controllers\VoucherController;
    use Illuminate\Support\Facades\Route;
    use Inertia\Inertia;

    // ---------- Zypher: Public storefront ----------
    Route::get('/', [HomeController::class, 'index'])->middleware('branch.seller')->name('home');
    Route::get('/about', function () {
        return Inertia::render('About');
    })->name('about');
    Route::get('/search', [SearchController::class, 'index'])->middleware('branch.seller')->name('search');
    Route::get('/search/suggestions', [SearchController::class, 'suggestions'])->middleware(['web', 'branch.seller'])->name('search.suggestions');
    Route::get('/shop/{shopSlug}/{productSlug}', [ProductController::class, 'show'])->middleware('branch.seller')->name('products.show');

    // ---------- Become a Seller (Guest or Customer) ----------
    Route::get('/become-a-seller', [SellerApplicationController::class, 'create'])->name('seller-application.create');
    Route::post('/become-a-seller', [SellerApplicationController::class, 'store'])->name('seller-application.store');

    // ---------- Zypher: OTP-based forgot password (guest only) ----------
    Route::middleware('guest')->group(function () {
        Route::get('/forgot-password', function () {
            return Inertia::render('Auth/ForgotPassword', ['status' => session('status')]);
        })->name('password.request');

        Route::post('/forgot-password', [OtpPasswordResetController::class, 'sendOtp'])->name('password.email');

        Route::get('/verify-otp', function () {
            return Inertia::render('Auth/VerifyOtp', request()->only('email'));
        })->name('otp.form');
        Route::post('/verify-otp', [OtpPasswordResetController::class, 'verifyOtp'])->name('otp.verify');

        Route::get('/reset-password', function () {
            return Inertia::render('Auth/ResetPassword', request()->only('email', 'token'));
        })->name('password.reset.form');
        Route::post('/reset-password', [OtpPasswordResetController::class, 'resetPassword'])->name('password.update');
    });
    // PayMongo webhooks (no auth — verified via signature)
    Route::post('/webhooks/paymongo', PayMongoWebhookController::class)->name('paymongo.webhook');

    Route::middleware([
        'auth:sanctum',
        config('jetstream.auth_session'),
        'verified',
    ])->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Dashboard');
        })->name('dashboard');

        Route::get('/branch/pending', function () {
            return Inertia::render('Seller/BranchPending', [
                'reason' => request()->user()?->branch_id ? 'suspended' : 'unlinked',
            ]);
        })->name('branch.pending');

        Route::post('/seller/branches', [\App\Http\Controllers\BranchController::class, 'store'])
            ->middleware('branch.access')
            ->name('seller.branches.store');

        Route::get('/branch/profile', [\App\Http\Controllers\BranchProfileController::class, 'show'])
            ->middleware('branch.access')
            ->name('branch.profile');

        Route::get('/branch/staff', [\App\Http\Controllers\BranchStaffController::class, 'index'])
            ->middleware('branch.access')
            ->name('branch.staff.index');
        Route::post('/branch/staff', [\App\Http\Controllers\BranchStaffController::class, 'store'])
            ->middleware('branch.access')
            ->name('branch.staff.store');
        Route::patch('/branch/staff/{staff}/suspension', [\App\Http\Controllers\BranchStaffController::class, 'updateSuspension'])
            ->middleware('branch.access')
            ->name('branch.staff.suspension');
        Route::delete('/branch/staff/{staff}', [\App\Http\Controllers\BranchStaffController::class, 'destroy'])
            ->middleware('branch.access')
            ->name('branch.staff.destroy');

        Route::get('/seller/branches/{branch}/bir-document', [\App\Http\Controllers\BranchController::class, 'viewBirDocument'])
            ->middleware('auth')
            ->name('seller.branches.bir.view');

        Route::get('/seller/branches/{branch}/sales', [\App\Http\Controllers\BranchController::class, 'sales'])
            ->middleware('auth')
            ->name('seller.branches.sales');

        // Supplier Application
        Route::post('/supplier/apply', [SupplierApplicationController::class, 'store'])
            ->name('supplier.apply');


        // ---------- Zypher: customer-facing (cart, checkout, orders, reviews) ----------
        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
        Route::patch('/cart/{cartItem}', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');

        Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
        Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
        Route::get('/checkout/paymongo/success/{order}', [CheckoutController::class, 'success'])->name('checkout.paymongo.success');
        Route::get('/checkout/paymongo/cancel/{order}', [CheckoutController::class, 'cancel'])->name('checkout.paymongo.cancel');

        Route::post('/vouchers/preview', [VoucherController::class, 'preview'])->name('vouchers.preview');

        Route::get('/account/orders', [OrderController::class, 'index'])->name('account.orders.index');
        Route::get('/account/orders/{order}', [OrderController::class, 'show'])->name('account.orders.show');
        Route::post('/account/orders/order-shops/{orderShopId}/confirm-receipt', [OrderController::class, 'confirmReceipt'])
            ->name('account.orders.confirm-receipt');

        Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');

        Route::get('/account/settings', [AddressController::class, 'index'])->name('account.settings');
        Route::post('/account/addresses', [AddressController::class, 'store'])->name('account.addresses.store');
        Route::delete('/account/addresses/{address}', [AddressController::class, 'destroy'])->name('account.addresses.destroy');

        // ---------- Zypher: messaging (customer <-> shop) ----------
        Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::post('/messages/start', [MessageController::class, 'start'])->name('messages.start');
        Route::post('/messages/send', [MessageController::class, 'send'])->name('messages.send');
        Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('messages.show');
        Route::post('/messages/{conversation}', [MessageController::class, 'store'])->name('messages.store');

        // ---------- Seller pending approval ----------
        Route::get('/seller/pending-approval', function () {
        return Inertia::render('Seller/PendingApproval');
        })->name('seller.pending.approval');

        Route::get('/branch/pending', function () {
        return Inertia::render('Seller/BranchPending');
        })->name('branch.pending');

        Route::get('/seller/denied', function () {
        return Inertia::render('Seller/Denied');
        })->name('seller.denied');
        // ---------- Zypher: customer service / support chat (quick-reply tickets) ----------
        Route::get('/support', [SupportController::class, 'index'])->name('support.index');
        Route::post('/support', [SupportController::class, 'store'])->name('support.store');
        Route::get('/support/{conversation}', [SupportController::class, 'show'])->name('support.show');
        Route::post('/support/{conversation}/message', [SupportController::class, 'storeMessage'])->name('support.message.store');

    });

// Temporary PayMongo diagnostic (local only) — remove after keys work
Route::get('/__paymongo-check', function () {
    abort_unless(app()->environment('local'), 404);
    $pm = app(\App\Services\PayMongoService::class);
    return response()->json([
        'configured' => $pm->isConfigured(),
        'test_mode' => $pm->isTestMode(),
        'masked_secret' => $pm->maskedSecret(),
        'config_paymongo_secret_len' => strlen((string) config('paymongo.secret_key')),
        'config_services_secret_len' => strlen((string) config('services.paymongo.secret_key')),
        'error' => $pm->configurationError(),
        'hint' => 'If configured=false, run: php artisan config:clear  then refresh this page',
    ]);
});

