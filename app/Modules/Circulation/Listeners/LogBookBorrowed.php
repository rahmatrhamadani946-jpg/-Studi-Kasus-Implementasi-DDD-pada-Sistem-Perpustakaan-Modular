<?php

namespace Modules\Circulation\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Circulation\Events\BookBorrowed;

/**
 * Listener BookBorrowed: mencatat log peminjaman.
 * (Dapat diganti/ditambah dengan pengiriman notifikasi.)
 */
class LogBookBorrowed
{
    public function handle(BookBorrowed $event): void
    {
        $loan = $event->loan->loadMissing('details');

        Log::info('Buku dipinjam', [
            'loan_number' => $loan->loan_number,
            'member_id' => $loan->member_id,
            'books' => $loan->details->map(fn ($d) => [
                'title' => $d->book_title,
                'isbn' => $d->book_isbn,
                'quantity' => $d->quantity,
            ])->all(),
            'due_at' => $loan->due_at->toDateTimeString(),
        ]);
    }
}
