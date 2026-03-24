<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
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

// Contactformulier
Route::post('/api/contact', [ContactController::class, 'send'])->name('api.contact');

// Mollie webhook (CSRF uitgezonderd in bootstrap/app.php)
Route::post('/webhook/mollie', [OrderController::class, 'webhook'])->name('webhook.mollie');

// Order pagina's
Route::get('/bestelling/{orderNumber}', [OrderController::class, 'complete'])->name('order.complete');
Route::get('/status/{orderNumber}', [OrderController::class, 'status'])->name('order.status');

// Admin login routes
Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

// Admin routes (beveiligd met AdminAuth middleware)
Route::prefix('admin')->middleware(\App\Http\Middleware\AdminAuth::class)->group(function () {
    Route::get('/orders', [AdminController::class, 'orders'])->name('admin.orders');
    Route::get('/orders/{orderNumber}/pdf', [AdminController::class, 'downloadPdf'])->name('admin.order.pdf');
    Route::get('/orders/{orderNumber}/imposed', [AdminController::class, 'downloadImposedPdf'])->name('admin.order.imposed');
    Route::post('/orders/{orderNumber}/impose', [AdminController::class, 'generateImposition'])->name('admin.order.impose');
    Route::get('/orders/{orderNumber}/pakbon', [AdminController::class, 'pakbon'])->name('admin.order.pakbon');
    Route::post('/orders/{orderNumber}/ship', [AdminController::class, 'updateStatus'])->name('admin.order.ship');
    Route::delete('/orders/{orderNumber}', [AdminController::class, 'destroy'])->name('admin.order.delete');
    Route::post('/orders/bulk-delete', [AdminController::class, 'bulkDestroy'])->name('admin.orders.bulk-delete');

    // Facturatie
    Route::post('/orders/{orderNumber}/invoice', [AdminController::class, 'createInvoice'])->name('admin.order.invoice.create');
    Route::get('/orders/{orderNumber}/invoice', [AdminController::class, 'downloadInvoice'])->name('admin.order.invoice.download');
    Route::post('/orders/{orderNumber}/invoice/resend', [AdminController::class, 'resendInvoice'])->name('admin.order.invoice.resend');
    Route::get('/invoices/monthly', [AdminController::class, 'monthlySummary'])->name('admin.invoices.monthly');
});
