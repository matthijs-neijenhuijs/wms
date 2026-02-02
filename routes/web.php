<?php

use App\Http\Controllers\SubdomainLookupController;
use Illuminate\Support\Facades\Route;

// Central domain routes - handled by domain constraint in bootstrap/app.php
Route::get('/', [SubdomainLookupController::class, 'show'])->name('subdomain.show');
Route::post('/lookup', [SubdomainLookupController::class, 'lookup'])->name('subdomain.lookup');
