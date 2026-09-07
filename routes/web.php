<?php

declare(strict_types=1);

use App\Http\Controllers\ProductBarcodeDownloadController;
use App\Http\Controllers\SubdomainLookupController;
use Illuminate\Support\Facades\Route;

// Central domain routes - handled by domain constraint in bootstrap/app.php
Route::get('/', [SubdomainLookupController::class, 'show'])->name('subdomain.show');
Route::post('/lookup', [SubdomainLookupController::class, 'lookup'])->name('subdomain.lookup');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('{tenant}/products/{product}/barcode.png', ProductBarcodeDownloadController::class)
        ->name('products.barcode.download');
});
