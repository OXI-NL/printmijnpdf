<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Homepage / bestelformulier
Route::get('/', [OrderController::class, 'index'])->name('home');

// API endpoints
Route::post('/api/calculate-price', [OrderController::class, 'calculatePrice'])->name('api.calculate');
Route::post('/api/order', [OrderController::class, 'store'])->name('api.order');

// Mollie webhook (CSRF uitgezonderd in bootstrap/app.php)
Route::post('/webhook/mollie', [OrderController::class, 'webhook'])->name('webhook.mollie');

// Order pagina's
Route::get('/bestelling/{orderNumber}', [OrderController::class, 'complete'])->name('order.complete');
Route::get('/status/{orderNumber}', [OrderController::class, 'status'])->name('order.status');

// Admin routes (later beveiligen met auth middleware)
Route::prefix('admin')->group(function () {
    Route::get('/orders', [AdminController::class, 'orders'])->name('admin.orders');
    Route::get('/orders/{orderNumber}/pdf', [AdminController::class, 'downloadPdf'])->name('admin.order.pdf');
    Route::get('/orders/{orderNumber}/pakbon', [AdminController::class, 'pakbon'])->name('admin.order.pakbon');
    Route::post('/orders/{orderNumber}/ship', [AdminController::class, 'updateStatus'])->name('admin.order.ship');
});
