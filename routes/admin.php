<?php

use App\Modules\Admin\Http\Controllers\CatalogBannerAdminController;
use App\Modules\Admin\Http\Controllers\CategoryAdminController;
use App\Modules\Admin\Http\Controllers\CommerceSettingsAdminController;
use App\Modules\Admin\Http\Controllers\DashboardController;
use App\Modules\Admin\Http\Controllers\DistributorAdminController;
use App\Modules\Admin\Http\Controllers\OrderAdminController;
use App\Modules\Admin\Http\Controllers\ProductAdminController;
use App\Modules\Admin\Http\Controllers\ProductImportController;
use App\Modules\Admin\Http\Controllers\ProductMediaController;
use App\Modules\Admin\Http\Controllers\UserAdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::post('stock/sync', [DashboardController::class, 'syncStock'])->name('contapyme.sync');

        Route::patch('categories/{category}/status', [CategoryAdminController::class, 'setStatus'])->name('categories.status');
        Route::resource('categories', CategoryAdminController::class)->except('show');

        Route::get('catalog-banners', [CatalogBannerAdminController::class, 'index'])->name('catalog-banners.index');
        Route::post('catalog-banners', [CatalogBannerAdminController::class, 'store'])->name('catalog-banners.store');
        Route::delete('catalog-banners/{catalogBanner}', [CatalogBannerAdminController::class, 'destroy'])->name('catalog-banners.destroy');

        Route::get('products/check-sku', [ProductAdminController::class, 'checkSku'])
            ->middleware(['throttle:api-endpoints', 'suspicious_automation'])
            ->name('products.check-sku');
        Route::get('products/import/template', [ProductImportController::class, 'downloadTemplate'])->name('products.import.template');
        Route::post('products/import', [ProductImportController::class, 'import'])->name('products.import');
        Route::post('products/bulk-action', [ProductAdminController::class, 'bulkAction'])->name('products.bulk-action');
        Route::get('products/inventory/pdf', [ProductAdminController::class, 'downloadInventoryPdf'])->name('products.inventory.pdf');
        Route::patch('products/{product}/status', [ProductAdminController::class, 'setStatus'])->name('products.status');
        Route::patch('products/{product}/stock', [ProductAdminController::class, 'setStock'])->name('products.stock');
        Route::patch('products/{product}/variants/stock', [ProductAdminController::class, 'setVariantStocks'])->name('products.variants.stock');
        Route::post('products/{product}/duplicate', [ProductAdminController::class, 'duplicate'])->name('products.duplicate');
        Route::get('products/{product}/documents/{document}/download', [ProductMediaController::class, 'downloadDocument'])->name('products.documents.download');
        Route::delete('products/{product}/photos/{photo}', [ProductMediaController::class, 'destroyPhoto'])->name('products.photos.destroy');
        Route::delete('products/{product}/documents/{document}', [ProductMediaController::class, 'destroyDocument'])->name('products.documents.destroy');
        Route::delete('products/{product}/videos/{video}', [ProductMediaController::class, 'destroyVideo'])->name('products.videos.destroy');
        Route::resource('products', ProductAdminController::class)->except('show');

        Route::patch('distributors/{distributor}/status', [DistributorAdminController::class, 'setStatus'])->name('distributors.status');
        Route::patch('distributors/{distributor}/tier', [DistributorAdminController::class, 'updateTier'])->name('distributors.tier.update');
        Route::resource('distributors', DistributorAdminController::class)->except('show');
        Route::resource('users', UserAdminController::class)->except('show');

        Route::get('settings/commerce', [CommerceSettingsAdminController::class, 'edit'])->name('settings.commerce.edit');
        Route::patch('settings/commerce', [CommerceSettingsAdminController::class, 'update'])->name('settings.commerce.update');

        Route::get('orders', [OrderAdminController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}/edit', [OrderAdminController::class, 'edit'])->name('orders.edit');
        Route::put('orders/{order}', [OrderAdminController::class, 'update'])->name('orders.update');
        Route::get('orders/{order}', [OrderAdminController::class, 'show'])->name('orders.show');
        Route::patch('orders/{order}/status', [OrderAdminController::class, 'updateStatus'])->name('orders.status');
        Route::get('orders/{order}/pdf', [OrderAdminController::class, 'downloadPdf'])->name('orders.pdf');
        Route::delete('orders/{order}', [OrderAdminController::class, 'destroy'])->name('orders.destroy');
    });
