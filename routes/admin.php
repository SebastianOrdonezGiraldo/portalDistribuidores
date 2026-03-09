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

        Route::resource('categories', CategoryAdminController::class)->except('show');
        Route::resource('products', ProductAdminController::class)->except('show');
        Route::resource('distributors', DistributorAdminController::class)->except('show');
        Route::resource('users', UserAdminController::class)->except('show');

        Route::get('orders', [OrderAdminController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderAdminController::class, 'show'])->name('orders.show');
        Route::get('orders/{order}/pdf', [OrderAdminController::class, 'downloadPdf'])->name('orders.pdf');
        Route::delete('orders/{order}', [OrderAdminController::class, 'destroy'])->name('orders.destroy');
    });
