<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\ProfileController;
use App\Modules\Catalog\Http\Controllers\CatalogController;
use App\Modules\Catalog\Http\Controllers\ProductController;
use App\Modules\Documents\Http\Controllers\TechSheetDownloadController;
use App\Modules\Orders\Http\Controllers\CartController;
use App\Modules\Orders\Http\Controllers\CheckoutController;
use App\Modules\Orders\Http\Controllers\OrderController;
use App\Modules\Orders\Http\Controllers\PaymentReceiptController;
use Illuminate\Support\Facades\Route;

Route::get('/up', HealthController::class)->name('health');

Route::get('/aviso-de-privacidad', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/aviso-de-tratamiento', [LegalController::class, 'treatment'])->name('legal.treatment');
Route::get('/terminos-y-condiciones', [LegalController::class, 'terms'])->name('legal.terms');

Route::get('/', CatalogController::class)
    ->middleware(['throttle:catalog-scraping', 'suspicious_automation'])
    ->name('catalog.index');

Route::redirect('/catalog', '/', 301);

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
Route::get('/orders/{order}/payment-status', [OrderController::class, 'paymentStatus'])
    ->middleware(['throttle:api-endpoints'])
    ->name('orders.payment-status');
Route::post('/orders/{order}/payment-receipt', [OrderController::class, 'uploadPaymentReceipt'])
    ->middleware(['throttle:api-endpoints', 'suspicious_automation'])
    ->name('orders.payment-receipt.upload');
Route::post('/orders/{order}/payment-upload-link', [OrderController::class, 'regeneratePaymentUploadLink'])
    ->middleware(['throttle:api-endpoints', 'suspicious_automation'])
    ->name('orders.payment-upload-link');

Route::get('/pedidos/{order}/comprobante', [PaymentReceiptController::class, 'show'])
    ->middleware(['throttle:api-endpoints', 'suspicious_automation'])
    ->name('orders.payment-receipt.show');
Route::post('/pedidos/{order}/comprobante', [PaymentReceiptController::class, 'store'])
    ->middleware(['throttle:api-endpoints', 'suspicious_automation'])
    ->name('orders.payment-receipt.store');
Route::post('/pedidos/{order}/comprobante/regenerar', [PaymentReceiptController::class, 'regenerate'])
    ->middleware(['throttle:api-endpoints', 'suspicious_automation'])
    ->name('orders.payment-receipt.regenerate');

require __DIR__.'/admin.php';
require __DIR__.'/company.php';
require __DIR__.'/auth.php';
