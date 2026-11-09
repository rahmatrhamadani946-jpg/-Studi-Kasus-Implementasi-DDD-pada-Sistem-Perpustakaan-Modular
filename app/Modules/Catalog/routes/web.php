<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Controllers\BookCategoryController;
use Modules\Catalog\Controllers\BookController;

Route::middleware(['web', 'auth'])->prefix('library')->name('library.')->group(function () {
    Route::get('books', [BookController::class, 'index'])->name('books.index');
    Route::get('categories', [BookCategoryController::class, 'index'])->name('categories.index');

    Route::middleware('role:admin')->group(function () {
        // 'books-trashed' didefinisikan sebelum 'books/{book}' agar tidak bentrok
        Route::get('books-trashed', [BookController::class, 'trashed'])->name('books.trashed');
        Route::post('books', [BookController::class, 'store'])->name('books.store');
        Route::delete('books/{book}', [BookController::class, 'destroy'])->name('books.destroy');
        Route::patch('books/{id}/restore', [BookController::class, 'restore'])->name('books.restore');
        Route::post('categories', [BookCategoryController::class, 'store'])->name('categories.store');
    });

    Route::get('books/{book}', [BookController::class, 'show'])->name('books.show');
});
