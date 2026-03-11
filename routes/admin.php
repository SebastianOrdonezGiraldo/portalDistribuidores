<?php

use App\Modules\Admin\Http\Controllers\CategoryAdminController;
use App\Modules\Admin\Http\Controllers\DashboardController;
use App\Modules\Admin\Http\Controllers\DistributorAdminController;
use App\Modules\Admin\Http\Controllers\OrderAdminController;
use App\Modules\Admin\Http\Controllers\ProductAdminController;
use App\Modules\Admin\Http\Controllers\UserAdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::patch('categories/{category}/status', [CategoryAdminController::class, 'setStatus'])->name('categories.status');
        Route::resource('categories', CategoryAdminController::class)->except('show');
        Route::get('products/check-sku', [ProductAdminController::class, 'checkSku'])->name('products.check-sku');
        Route::get('products/import/template', [ProductAdminController::class, 'downloadImportTemplate'])->name('products.import.template');
        Route::post('products/import', [ProductAdminController::class, 'import'])->name('products.import');
        Route::patch('products/{product}/status', [ProductAdminController::class, 'setStatus'])->name('products.status');
        Route::delete('products/{product}/photos/{photo}', [ProductAdminController::class, 'destroyPhoto'])->name('products.photos.destroy');
        Route::delete('products/{product}/documents/{document}', [ProductAdminController::class, 'destroyDocument'])->name('products.documents.destroy');
        Route::delete('products/{product}/videos/{video}', [ProductAdminController::class, 'destroyVideo'])->name('products.videos.destroy');
        Route::resource('products', ProductAdminController::class)->except('show');
        Route::patch('distributors/{distributor}/status', [DistributorAdminController::class, 'setStatus'])->name('distributors.status');
        Route::resource('distributors', DistributorAdminController::class)->except('show');
        Route::resource('users', UserAdminController::class)->except('show');

        Route::get('orders', [OrderAdminController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderAdminController::class, 'show'])->name('orders.show');
        Route::get('orders/{order}/pdf', [OrderAdminController::class, 'downloadPdf'])->name('orders.pdf');
        Route::delete('orders/{order}', [OrderAdminController::class, 'destroy'])->name('orders.destroy');
    });
