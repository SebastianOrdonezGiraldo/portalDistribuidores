<?php

use App\Modules\Company\Http\Controllers\CompanyDashboardController;
use App\Modules\Company\Http\Controllers\CompanyOrderController;
use App\Modules\Company\Http\Controllers\CompanyProfileController;
use App\Modules\Company\Http\Controllers\CompanyUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:distributor'])
    ->prefix('empresa')
    ->name('empresa.')
    ->group(function () {

        // Dashboard de empresa
        Route::get('/', CompanyDashboardController::class)->name('dashboard');

        // Historial de pedidos/cotizaciones
        Route::get('/pedidos', [CompanyOrderController::class, 'index'])->name('orders.index');
        Route::get('/pedidos/{order}', [CompanyOrderController::class, 'show'])->name('orders.show');
        Route::get('/pedidos/{order}/pdf', [CompanyOrderController::class, 'downloadPdf'])->name('orders.pdf');

        // Datos de la empresa
        Route::get('/perfil', [CompanyProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/perfil', [CompanyProfileController::class, 'update'])->name('profile.update');

        // Usuarios de la empresa
        Route::get('/usuarios', [CompanyUserController::class, 'index'])->name('users.index');
        Route::get('/usuarios/nuevo', [CompanyUserController::class, 'create'])->name('users.create');
        Route::post('/usuarios', [CompanyUserController::class, 'store'])->name('users.store');
        Route::get('/usuarios/{user}/editar', [CompanyUserController::class, 'edit'])->name('users.edit');
        Route::put('/usuarios/{user}', [CompanyUserController::class, 'update'])->name('users.update');
        Route::patch('/usuarios/{user}/estado', [CompanyUserController::class, 'toggleActive'])->name('users.toggle-active');
    });
