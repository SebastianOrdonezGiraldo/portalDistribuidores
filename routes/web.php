<?php

use App\Http\Controllers\ProfileController;
use App\Modules\Catalog\Http\Controllers\CatalogController;
use App\Modules\Catalog\Http\Controllers\ProductController;
use App\Modules\Documents\Http\Controllers\TechSheetDownloadController;
use App\Modules\Orders\Http\Controllers\CartController;
use App\Modules\Orders\Http\Controllers\CheckoutController;
use App\Modules\Orders\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('catalog.index');
});

Route::get('/dashboard', function () {
    if (auth()->user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('empresa.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::get('/catalog', CatalogController::class)
    ->middleware(['throttle:catalog-scraping', 'suspicious_automation'])
    ->name('catalog.index');
Route::get('/products/{product}', [ProductController::class, 'show'])
    ->middleware(['throttle:catalog-scraping', 'suspicious_automation'])
    ->name('products.show');
Route::get('/documents/tech-sheet/{productDocument}', TechSheetDownloadController::class)
    ->middleware(['throttle:api-endpoints', 'suspicious_automation'])
    ->name('documents.tech-sheet.download');
Route::get('/documents/manual/{productDocument}', TechSheetDownloadController::class)
    ->middleware(['throttle:api-endpoints', 'suspicious_automation'])
    ->name('documents.manual.download');
Route::get('/documents/invima/{productDocument}', TechSheetDownloadController::class)
    ->middleware(['throttle:api-endpoints', 'suspicious_automation'])
    ->name('documents.invima.download');
Route::get('/documents/quick-guide/{productDocument}', TechSheetDownloadController::class)
    ->middleware(['throttle:api-endpoints', 'suspicious_automation'])
    ->name('documents.quick-guide.download');
Route::get('/documents/calibration-document/{productDocument}', TechSheetDownloadController::class)
    ->middleware(['throttle:api-endpoints', 'suspicious_automation'])
    ->name('documents.calibration-document.download');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'store'])
    ->middleware(['throttle:api-endpoints', 'suspicious_automation'])
    ->name('cart.store');
Route::match(['put', 'patch'], '/cart', [CartController::class, 'update'])
    ->middleware(['throttle:api-endpoints', 'suspicious_automation'])
    ->name('cart.update');
Route::delete('/cart/{lineKey}', [CartController::class, 'destroy'])
    ->middleware(['throttle:api-endpoints', 'suspicious_automation'])
    ->name('cart.destroy');

Route::get('/checkout', CheckoutController::class)->name('checkout.show');
Route::post('/orders', [OrderController::class, 'store'])
    ->middleware(['throttle:api-endpoints', 'suspicious_automation'])
    ->name('orders.store');
Route::get('/orders/{order}/submitted', [OrderController::class, 'submitted'])->name('orders.submitted');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::get('/orders/{order}/pdf', [OrderController::class, 'downloadPdf'])->name('orders.pdf');

require __DIR__.'/admin.php';
require __DIR__.'/company.php';
require __DIR__.'/auth.php';
