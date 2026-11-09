<?php

use Illuminate\Support\Facades\Route;
use Modules\Circulation\Controllers\LoanController;

Route::middleware(['web', 'auth'])->prefix('library')->name('library.')->group(function () {
    Route::get('loans', [LoanController::class, 'index'])->name('loans.index');
    Route::post('loans', [LoanController::class, 'store'])->name('loans.store');
    Route::post('loans/{loan}/return', [LoanController::class, 'returnLoan'])->name('loans.return');

    Route::middleware('role:admin')->group(function () {
        Route::get('loans-trashed', [LoanController::class, 'trashed'])->name('loans.trashed');
        Route::delete('loans/{loan}', [LoanController::class, 'destroy'])->name('loans.destroy');
        Route::patch('loans/{id}/restore', [LoanController::class, 'restore'])->name('loans.restore');
    });

    Route::get('loans/{loan}', [LoanController::class, 'show'])->name('loans.show');
});
