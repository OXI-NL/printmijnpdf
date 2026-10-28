<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\LlmsController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/llms.txt', [LlmsController::class, 'index'])->name('llms');

// Homepage / bestelformulier
Route::get('/', [OrderController::class, 'index'])->name('home');

// API endpoints
Route::post('/api/calculate-price', [OrderController::class, 'calculatePrice'])->name('api.calculate');
Route::post('/api/order', [OrderController::class, 'store'])->name('api.order');

// Contactformulier
Route::post('/api/contact', [ContactController::class, 'send'])->name('api.contact');
Route::post('/api/contact/custom-format', [ContactController::class, 'sendCustomFormat'])->name('api.contact.custom-format');

// Over ons, privacy en leveringsvoorwaarden
Route::view('/over-ons', 'legal.over-ons')->name('over-ons');
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/leveringsvoorwaarden', 'legal.leveringsvoorwaarden')->name('leveringsvoorwaarden');

// Reviewlink voor de QR-code op het kaartje in elk pakket. Tijdelijke 302,
// zodat gedrukte kaartjes blijven werken als de Google-link ooit verandert.
Route::get('/review', fn () => redirect()->away(config('seo.google_review'), 302)
    ->header('X-Robots-Tag', 'noindex'))->name('review');

// SEO Landingspagina's
Route::prefix('/')->group(function () {
    Route::get('pdf-laten-printen', [LandingPageController::class, 'pdfLatenPrinten'])->name('landing.pdf');
    Route::get('pdf-naar-boekje', [LandingPageController::class, 'pdfNaarBoekje'])->name('landing.pdf-naar-boekje');
    Route::get('boekje-printen', [LandingPageController::class, 'boekjePrinten'])->name('landing.boekje-printen');
    Route::get('pdf-printen-met-spoed', [LandingPageController::class, 'spoed'])->name('landing.spoed');
    Route::get('prijzen', [LandingPageController::class, 'prijzen'])->name('landing.prijzen');
    Route::get('pdf-aanleveren', [LandingPageController::class, 'aanleveren'])->name('landing.aanleveren');
    Route::get('scriptie-printen', [LandingPageController::class, 'scriptie'])->name('landing.scriptie');
    Route::get('reader-printen', [LandingPageController::class, 'reader'])->name('landing.reader');
    Route::get('cursusmateriaal-printen', [LandingPageController::class, 'cursusmateriaal'])->name('landing.cursusmateriaal');
    Route::get('boekje-maken', [LandingPageController::class, 'boekje'])->name('landing.boekje');
    Route::get('handleiding-printen', [LandingPageController::class, 'handleiding'])->name('landing.handleiding');
    Route::get('zakelijk', [LandingPageController::class, 'zakelijk'])->name('landing.zakelijk');
    Route::get('liturgie-uitvaart-printen', [LandingPageController::class, 'uitvaart'])->name('landing.uitvaart');
    Route::get('boekje-bruiloft-printen', [LandingPageController::class, 'bruiloft'])->name('landing.bruiloft');
    Route::get('printen-zonder-printer', [LandingPageController::class, 'zonderPrinter'])->name('landing.zonder-printer');
    Route::get('printen-delft', [LandingPageController::class, 'delft'])->name('landing.delft');
});

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
    Route::post('/orders/bulk-archive', [AdminController::class, 'bulkArchive'])->name('admin.orders.bulk-archive');
    Route::post('/orders/bulk-unarchive', [AdminController::class, 'bulkUnarchive'])->name('admin.orders.bulk-unarchive');

    // Facturatie
    Route::post('/orders/{orderNumber}/invoice', [AdminController::class, 'createInvoice'])->name('admin.order.invoice.create');
    Route::get('/orders/{orderNumber}/invoice', [AdminController::class, 'downloadInvoice'])->name('admin.order.invoice.download');
    Route::post('/orders/{orderNumber}/invoice/resend', [AdminController::class, 'resendInvoice'])->name('admin.order.invoice.resend');
    Route::get('/invoices/monthly', [AdminController::class, 'monthlySummary'])->name('admin.invoices.monthly');
});
