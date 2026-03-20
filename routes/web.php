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
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/catalog', CatalogController::class)->name('catalog.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('/documents/tech-sheet/{productDocument}', TechSheetDownloadController::class)
    ->name('documents.tech-sheet.download');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
Route::match(['put', 'patch'], '/cart', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{lineKey}', [CartController::class, 'destroy'])->name('cart.destroy');

Route::get('/checkout', CheckoutController::class)->name('checkout.show');
Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
Route::get('/orders/{order}/submitted', [OrderController::class, 'submitted'])->name('orders.submitted');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::get('/orders/{order}/pdf', [OrderController::class, 'downloadPdf'])->name('orders.pdf');

require __DIR__.'/admin.php';
require __DIR__.'/company.php';
require __DIR__.'/auth.php';
