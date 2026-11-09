<?php

use Illuminate\Support\Facades\Route;
use Modules\Membership\Controllers\MemberController;

Route::middleware(['web', 'auth'])->prefix('library')->name('library.')->group(function () {
    Route::get('members', [MemberController::class, 'index'])->name('members.index');

    Route::middleware('role:admin')->group(function () {
        Route::get('members-trashed', [MemberController::class, 'trashed'])->name('members.trashed');
        Route::post('members', [MemberController::class, 'store'])->name('members.store');
        Route::delete('members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');
        Route::patch('members/{id}/restore', [MemberController::class, 'restore'])->name('members.restore');
    });

    Route::get('members/{member}', [MemberController::class, 'show'])->name('members.show');
});
