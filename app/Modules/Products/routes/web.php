<?php

use Illuminate\Support\Facades\Route;
use Modules\Products\Controllers\ProductController;

// Wajib menggunakan middleware 'web' agar session & auth terbaca
Route::middleware(['web', 'auth'])->group(function () {
    Route::resource('products', ProductController::class);
});
