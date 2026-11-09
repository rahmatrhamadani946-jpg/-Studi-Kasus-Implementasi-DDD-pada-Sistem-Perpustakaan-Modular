<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Book;
use Modules\Circulation\Models\Loan;

class LibraryQueryBenchmark extends Command
{
    protected $signature = 'library:query-benchmark';

    protected $description = 'Membandingkan jumlah query sebelum (N+1) dan sesudah eager loading';

    public function handle(): int
    {
        if (Book::count() === 0) {
            $this->error('Data kosong. Jalankan: php artisan db:seed --class=LibrarySeeder');

            return self::FAILURE;
        }

        $rows = [];

        // 1. Daftar buku beserta penulis, penerbit, dan kategori
        $lazy = $this->countQueries(function () {
            Book::all()->each(function (Book $book) {
                $book->authors->count();
                $book->publisher?->name;
                $book->categories->count();
            });
        });
        $eager = $this->countQueries(function () {
            Book::with(['authors', 'publisher', 'categories'])->get()->each(function (Book $book) {
                $book->authors->count();
                $book->publisher?->name;
                $book->categories->count();
            });
        });
        $rows[] = ['Daftar buku + penulis + penerbit + kategori', Book::count() . ' buku', $lazy, $eager, $lazy - $eager];

        // 2. Daftar peminjaman beserta detail dan denda
        $lazy = $this->countQueries(function () {
            Loan::all()->each(function (Loan $loan) {
                $loan->details->count();
                $loan->fines->count();
            });
        });
        $eager = $this->countQueries(function () {
            Loan::with(['details', 'fines'])->get()->each(function (Loan $loan) {
                $loan->details->count();
                $loan->fines->count();
            });
        });
        $rows[] = ['Daftar peminjaman + detail + denda', Loan::count() . ' peminjaman', $lazy, $eager, $lazy - $eager];

        $this->table(['Skenario', 'Data', 'Tanpa eager loading', 'Dengan eager loading', 'Selisih'], $rows);

        return self::SUCCESS;
    }

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $callback();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
