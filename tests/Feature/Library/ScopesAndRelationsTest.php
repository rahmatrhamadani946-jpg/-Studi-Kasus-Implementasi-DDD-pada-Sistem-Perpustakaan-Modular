<?php

use App\Models\User;
use Modules\Catalog\Models\Book;
use Modules\Catalog\Models\BookCategory;
use Modules\Circulation\Services\LoanService;

it('scope available hanya mengembalikan buku yang stoknya ada', function () {
    libraryBook(0);
    libraryBook(3);

    expect(Book::available()->count())->toBe(1);
});

it('scope search mencari judul, ISBN, dan deskripsi', function () {
    libraryBook(1, ['title' => 'Belajar Laravel', 'description' => 'framework php']);
    libraryBook(1, ['title' => 'Memasak Nasi', 'description' => 'dapur']);

    expect(Book::search('Laravel')->count())->toBe(1)
        ->and(Book::search('dapur')->count())->toBe(1)
        ->and(Book::search('tidak-ada')->count())->toBe(0);
});

it('scope byCategory memfilter lewat relasi many-to-many', function () {
    $cat = BookCategory::create(['name' => 'Sains', 'slug' => 'sains']);
    $in = libraryBook();
    libraryBook();
    $in->categories()->attach($cat->id, ['added_at' => now()]);

    expect(Book::byCategory($cat->id)->pluck('id')->all())->toBe([$in->id]);
});

it('many-to-many buku-kategori menyimpan kolom pivot added_at', function () {
    $cat = BookCategory::create(['name' => 'Sains', 'slug' => 'sains']);
    $book = libraryBook();
    $book->categories()->attach($cat->id, ['added_at' => '2026-11-01 08:00:00']);

    $loaded = Book::with('categories')->find($book->id)->categories->first();

    expect($loaded->pivot->added_at)->toBe('2026-11-01 08:00:00')
        ->and($cat->books()->count())->toBe(1);
});

it('self-referencing: kategori memiliki sub-kategori dan induk', function () {
    $parent = BookCategory::create(['name' => 'Komputer', 'slug' => 'komputer']);
    $child = $parent->children()->create(['name' => 'Basis Data', 'slug' => 'basis-data']);

    expect($parent->children)->toHaveCount(1)
        ->and($child->parent->is($parent))->toBeTrue();
});

it('HasMany: peminjaman memiliki banyak detail peminjaman', function () {
    $member = libraryMember(role: ['max_loans' => 5]);
    $loan = app(LoanService::class)->createLoan($member->id, [
        ['book_id' => libraryBook()->id, 'quantity' => 1],
        ['book_id' => libraryBook()->id, 'quantity' => 2],
    ]);

    expect($loan->details)->toHaveCount(2)
        ->and($loan->details->sum('quantity'))->toBe(3)
        ->and($loan->details->first()->loan->is($loan))->toBeTrue();
});

it('ISBN-10 disimpan sebagai ISBN-13 kanonik dan ISBN tidak valid ditolak', function () {
    $book = libraryBook(1, ['isbn' => '0-306-40615-2']);

    expect($book->fresh()->isbn)->toBe('9780306406157');

    expect(fn () => libraryBook(1, ['isbn' => '123']))->toThrow(InvalidArgumentException::class);
});

it('API: ISBN tidak valid atau duplikat ditolak saat membuat buku', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $payload = ['title' => 'Buku', 'isbn' => '9780306406157', 'stock' => 2];

    $this->actingAs($admin)->postJson('/library/books', $payload)->assertCreated();
    $this->actingAs($admin)->postJson('/library/books', ['isbn' => '0306406152'] + $payload)
        ->assertStatus(422)->assertJsonValidationErrors('isbn');   // ISBN-10 setara buku yang sama
    $this->actingAs($admin)->postJson('/library/books', ['isbn' => '1234567890123'] + $payload)
        ->assertStatus(422)->assertJsonValidationErrors('isbn');   // check digit salah
});

it('API: filter pencarian, kategori, dan ketersediaan pada daftar buku', function () {
    $user = User::factory()->create();
    $cat = BookCategory::create(['name' => 'Sains', 'slug' => 'sains']);
    $a = libraryBook(2, ['title' => 'Fisika Dasar']);
    libraryBook(0, ['title' => 'Fisika Lanjut']);
    libraryBook(2, ['title' => 'Memasak']);
    $a->categories()->attach($cat->id, ['added_at' => now()]);

    $this->actingAs($user)->getJson('/library/books?q=Fisika&available=1')->assertOk()->assertJsonCount(1, 'data');
    $this->actingAs($user)->getJson("/library/books?category_id={$cat->id}")->assertOk()->assertJsonCount(1, 'data');
});
