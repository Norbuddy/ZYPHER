<?php

use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\PopupAdController;
use App\Http\Controllers\Admin\BranchApplicationController;
use App\Http\Controllers\Admin\SupplierApplicationController;
use App\Http\Controllers\Admin\SellerApplicationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderShopController;
use App\Http\Controllers\Admin\PayoutController;
use App\Http\Controllers\Admin\ProductModerationController;
use App\Http\Controllers\Admin\ShopApprovalController;
use App\Http\Controllers\Admin\SupportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VoucherController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'is.admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/shops', [ShopApprovalController::class, 'index'])->name('shops.index');
        Route::post('/shops/{team}/approve', [ShopApprovalController::class, 'approve'])->name('shops.approve');
        Route::post('/shops/{team}/reject', [ShopApprovalController::class, 'reject'])->name('shops.reject');
        Route::post('/shops/{team}/suspend', [ShopApprovalController::class, 'suspend'])->name('shops.suspend');

        Route::get('/products', [ProductModerationController::class, 'index'])->name('products.index');
        Route::post('/products/{product}/flag', [ProductModerationController::class, 'flag'])->name('products.flag');
        Route::post('/products/{product}/unflag', [ProductModerationController::class, 'unflag'])->name('products.unflag');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/suspend', [UserController::class, 'suspend'])->name('users.suspend');
        Route::post('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');

        Route::get('/orders', [OrderShopController::class, 'index'])->name('orders.index');
        Route::post('/orders/{orderShop}/refund', [OrderShopController::class, 'refund'])->name('orders.refund');

        Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
        Route::post('/vouchers', [VoucherController::class, 'store'])->name('vouchers.store');

        Route::get('/payouts', [PayoutController::class, 'index'])->name('payouts.index');
        Route::post('/payouts/{orderShop}/mark-paid', [PayoutController::class, 'markPaid'])->name('payouts.mark-paid');
        Route::post('/payouts/team/{team}/mark-paid', [PayoutController::class, 'markPaidTeam'])->name('payouts.team.mark-paid');

        Route::get('/support', [SupportController::class, 'index'])->name('support.index');
        Route::get('/support/{conversation}', [SupportController::class, 'show'])->name('support.show');
        Route::post('/support/{conversation}', [SupportController::class, 'store'])->name('support.store');
        Route::post('/support/{conversation}/close', [SupportController::class, 'close'])->name('support.close');

        Route::get('/supplier-application', [SupplierApplicationController::class, 'index'])->name('supplier-application.index');
        Route::patch('/supplier-application/{supplierApplication}/approve', [SupplierApplicationController::class, 'approve'])->name('supplier-application.approve');

        Route::patch('/supplier-application/{supplierApplication}/reject', [SupplierApplicationController::class, 'reject'])->name('supplier-application.reject');

        Route::get('/seller-application', [SellerApplicationController::class, 'index'])->name('seller-application.index');
        Route::get('/seller-application/{sellerApplication}/document/{type}', [DocumentController::class, 'viewSellerDocument'])->name('seller-application.document');
        Route::patch('/seller-application/{sellerApplication}/approve', [SellerApplicationController::class, 'approve'])->name('seller-application.approve');
        Route::patch('/seller-application/{sellerApplication}/reject', [SellerApplicationController::class, 'reject'])->name('seller-application.reject');

        Route::get('/branch-application', [BranchApplicationController::class, 'index'])->name('branch-application.index');
        Route::get('/branch-application/{branch}/bir-document', [DocumentController::class, 'viewBranchBir'])->name('branch-application.bir-document');
        Route::patch('/branch-application/{branch}/approve', [BranchApplicationController::class, 'approve'])->name('branch-application.approve');
        Route::patch('/branch-application/{branch}/reject', [BranchApplicationController::class, 'reject'])->name('branch-application.reject');
        Route::patch('/branch-application/{branch}/suspend', [BranchApplicationController::class, 'suspend'])->name('branch-application.suspend');
        Route::patch('/branch-application/{branch}/activate', [BranchApplicationController::class, 'activate'])->name('branch-application.activate');
        Route::delete('/branch-application/{branch}', [BranchApplicationController::class, 'destroy'])->name('branch-application.destroy');

        Route::get('/banners', [BannerController::class, 'index'])->name('banners.index');
        Route::post('/banners', [BannerController::class, 'store'])->name('banners.store');
        Route::post('/banners/{banner}', [BannerController::class, 'update'])->name('banners.update');
        Route::delete('/banners/{banner}', [BannerController::class, 'destroy'])->name('banners.destroy');
        Route::post('/banners/reorder', [BannerController::class, 'reorder'])->name('banners.reorder');

        Route::get('/popup-ads', [PopupAdController::class, 'index'])->name('popup-ads.index');
        Route::post('/popup-ads', [PopupAdController::class, 'store'])->name('popup-ads.store');
        Route::post('/popup-ads/{popupAd}', [PopupAdController::class, 'update'])->name('popup-ads.update');
        Route::delete('/popup-ads/{popupAd}', [PopupAdController::class, 'destroy'])->name('popup-ads.destroy');
    });