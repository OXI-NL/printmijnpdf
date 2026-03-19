<?php

use App\Http\Controllers\OrderController;
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
Route::post('/admin/orders/{orderNumber}/ship', [OrderController::class, 'ship'])->name('admin.order.ship');
