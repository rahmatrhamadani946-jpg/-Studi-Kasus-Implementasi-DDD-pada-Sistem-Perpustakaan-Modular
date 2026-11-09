<?php

use App\Models\User;
use Modules\Catalog\Models\Book;
use Modules\Circulation\Models\Loan;
use Modules\Circulation\Services\LoanService;
use Modules\Membership\Models\Member;

it('Buku: soft delete, withTrashed, dan restore', function () {
    $books = Book::factory()->count(3)->create();

    $books->first()->delete();

    expect(Book::count())->toBe(2)
        ->and(Book::withTrashed()->count())->toBe(3)
        ->and(Book::onlyTrashed()->count())->toBe(1);

    Book::onlyTrashed()->first()->restore();

    expect(Book::count())->toBe(3);
});

it('Anggota: soft delete dan restore', function () {
    $member = libraryMember();

    $member->delete();
    expect(Member::count())->toBe(0)->and(Member::withTrashed()->count())->toBe(1);

    $member->restore();
    expect(Member::count())->toBe(1);
});

it('Peminjaman: soft delete dan restore', function () {
    $member = libraryMember();
    $loan = app(LoanService::class)->createLoan($member->id, [['book_id' => libraryBook()->id, 'quantity' => 1]]);

    $loan->delete();
    expect(Loan::count())->toBe(0)->and(Loan::withTrashed()->count())->toBe(1);

    $loan->restore();
    expect(Loan::count())->toBe(1);
});

it('admin dapat melihat data terhapus dan me-restore buku lewat API', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $book = libraryBook();

    $this->actingAs($admin)->deleteJson("/library/books/{$book->id}")->assertOk();
    expect(Book::count())->toBe(0);

    $this->actingAs($admin)->getJson('/library/books-trashed')->assertOk()->assertJsonCount(1);
    $this->actingAs($admin)->patchJson("/library/books/{$book->id}/restore")->assertOk();

    expect(Book::count())->toBe(1);
});

it('admin dapat me-restore anggota dan peminjaman lewat API', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $member = libraryMember();
    $loan = app(LoanService::class)->createLoan($member->id, [['book_id' => libraryBook()->id, 'quantity' => 1]]);

    $this->actingAs($admin)->deleteJson("/library/loans/{$loan->id}")->assertOk();
    $this->actingAs($admin)->patchJson("/library/loans/{$loan->id}/restore")->assertOk();
    $this->actingAs($admin)->deleteJson("/library/members/{$member->id}")->assertOk();
    $this->actingAs($admin)->patchJson("/library/members/{$member->id}/restore")->assertOk();

    expect(Loan::count())->toBe(1)->and(Member::count())->toBe(1);
});

it('non-admin tidak boleh menghapus atau me-restore (403)', function () {
    $user = User::factory()->create(['role' => 'user']);
    $book = libraryBook();
    $book->delete();

    $this->actingAs($user)->patchJson("/library/books/{$book->id}/restore")->assertForbidden();
    $this->actingAs($user)->getJson('/library/books-trashed')->assertForbidden();
    expect(Book::count())->toBe(0);
});

it('buku yang di-soft delete tidak muncul di daftar maupun detail', function () {
    $user = User::factory()->create();
    $book = libraryBook();
    $book->delete();

    $this->actingAs($user)->getJson('/library/books')->assertOk()->assertJsonCount(0, 'data');
    $this->actingAs($user)->getJson("/library/books/{$book->id}")->assertNotFound();
});
