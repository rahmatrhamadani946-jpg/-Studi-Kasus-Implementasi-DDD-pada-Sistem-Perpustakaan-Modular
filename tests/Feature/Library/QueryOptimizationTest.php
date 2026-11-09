<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Author;
use Modules\Catalog\Models\Book;
use Modules\Catalog\Models\BookCategory;
use Modules\Catalog\Models\Publisher;

function seedBooksWithRelations(int $count): void
{
    $publisher = Publisher::factory()->create();
    $author = Author::factory()->create();
    $category = BookCategory::create(['name' => 'Umum ' . uniqid(), 'slug' => 'umum-' . uniqid()]);

    Book::factory()->count($count)->create(['publisher_id' => $publisher->id])->each(function (Book $book) use ($author, $category) {
        $book->authors()->attach($author);
        $book->categories()->attach($category->id, ['added_at' => now()]);
    });
}

function countQueries(callable $callback): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $callback();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

it('eager loading menghilangkan N+1 (sebelum vs sesudah)', function () {
    seedBooksWithRelations(10);

    $touch = function ($book) {
        $book->authors->count();
        $book->publisher?->name;
        $book->categories->count();
    };

    $before = countQueries(fn () => Book::all()->each($touch));
    $after = countQueries(fn () => Book::with(['authors', 'publisher', 'categories'])->get()->each($touch));

    expect($before)->toBe(1 + 10 * 3)   // 1 query buku + 3 query per buku
        ->and($after)->toBe(4);         // buku + authors + publisher + categories
});

it('endpoint daftar buku memakai jumlah query konstan (tidak tergantung jumlah data)', function () {
    $user = User::factory()->create();

    seedBooksWithRelations(3);
    $small = countQueries(fn () => $this->actingAs($user)->getJson('/library/books')->assertOk());

    seedBooksWithRelations(12);
    $large = countQueries(fn () => $this->actingAs($user)->getJson('/library/books')->assertOk());

    expect($large)->toBe($small);
});

it('endpoint daftar peminjaman tidak N+1 (anggota diambil 1 query lewat contract)', function () {
    $user = User::factory()->create();
    $service = app(\Modules\Circulation\Services\LoanService::class);

    $makeLoans = function (int $n) use ($service) {
        for ($i = 0; $i < $n; $i++) {
            $service->createLoan(libraryMember()->id, [['book_id' => libraryBook()->id, 'quantity' => 1]]);
        }
    };

    $makeLoans(2);
    $small = countQueries(fn () => $this->actingAs($user)->getJson('/library/loans')->assertOk());

    $makeLoans(8);
    $large = countQueries(fn () => $this->actingAs($user)->getJson('/library/loans')->assertOk());

    expect($large)->toBe($small);
});
