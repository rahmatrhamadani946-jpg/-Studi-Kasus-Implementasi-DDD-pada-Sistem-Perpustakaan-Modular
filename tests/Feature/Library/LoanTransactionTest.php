<?php

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Modules\Catalog\Models\Book;
use Modules\Circulation\Events\BookBorrowed;
use Modules\Circulation\Events\BookReturned;
use Modules\Circulation\Listeners\LogBookBorrowed;
use Modules\Circulation\Models\Fine;
use Modules\Circulation\Models\Loan;
use Modules\Circulation\Models\LoanDetail;
use Modules\Circulation\Services\LoanService;
use Modules\Shared\Exceptions\InsufficientStockException;
use Modules\Shared\Exceptions\MemberNotEligibleException;

beforeEach(function () {
    $this->service = app(LoanService::class);
});

it('membuat peminjaman: kurangi stok, simpan snapshot, update status anggota', function () {
    Event::fake([BookBorrowed::class]);
    $member = libraryMember();
    $book = libraryBook(5);

    $loan = $this->service->createLoan($member->id, [['book_id' => $book->id, 'quantity' => 2]]);

    expect($loan->status)->toBe('borrowed')
        ->and($loan->details)->toHaveCount(1)
        ->and($loan->details->first()->book_title)->toBe($book->title)
        ->and($loan->details->first()->book_isbn)->toBe($book->isbn)
        ->and($loan->due_at->isSameDay(now()->addDays(14)))->toBeTrue()
        ->and($book->fresh()->stock)->toBe(3)
        ->and($member->fresh()->active_loans_count)->toBe(2)
        ->and($member->fresh()->status)->toBe('borrowing');

    Event::assertDispatched(BookBorrowed::class, fn ($e) => $e->loan->is($loan));
});

it('snapshot tetap utuh walau judul buku berubah kemudian', function () {
    $member = libraryMember();
    $book = libraryBook(5, ['title' => 'Judul Lama']);

    $loan = $this->service->createLoan($member->id, [['book_id' => $book->id, 'quantity' => 1]]);
    $book->update(['title' => 'Judul Baru']);

    expect($loan->fresh()->details->first()->book_title)->toBe('Judul Lama');
});

it('ROLLBACK: stok tidak cukup membatalkan seluruh peminjaman', function () {
    Event::fake([BookBorrowed::class]);
    $member = libraryMember(role: ['max_loans' => 10]);
    $bookA = libraryBook(5);
    $bookB = libraryBook(1);

    expect(fn () => $this->service->createLoan($member->id, [
        ['book_id' => $bookA->id, 'quantity' => 2],  // sebenarnya cukup
        ['book_id' => $bookB->id, 'quantity' => 5],  // tidak cukup -> gagal
    ]))->toThrow(InsufficientStockException::class);

    expect(Loan::count())->toBe(0)
        ->and(LoanDetail::count())->toBe(0)
        ->and($bookA->fresh()->stock)->toBe(5)   // stok buku A kembali utuh
        ->and($bookB->fresh()->stock)->toBe(1)
        ->and($member->fresh()->active_loans_count)->toBe(0)
        ->and($member->fresh()->status)->toBe('active');

    Event::assertNotDispatched(BookBorrowed::class); // event tidak ditembakkan saat gagal
});

it('menolak anggota yang melebihi batas pinjam', function () {
    $member = libraryMember();                 // batas 3 buku
    $book = libraryBook(10);

    expect(fn () => $this->service->createLoan($member->id, [['book_id' => $book->id, 'quantity' => 4]]))
        ->toThrow(MemberNotEligibleException::class, 'Batas peminjaman terlampaui');

    expect(Loan::count())->toBe(0)->and($book->fresh()->stock)->toBe(10);
});

it('menolak anggota ditangguhkan dan keanggotaan kedaluwarsa', function () {
    $book = libraryBook(10);
    $suspended = libraryMember(['status' => 'suspended']);
    $expired = libraryMember(['membership_expires_at' => now()->subDay()]);

    expect(fn () => $this->service->createLoan($suspended->id, [['book_id' => $book->id, 'quantity' => 1]]))
        ->toThrow(MemberNotEligibleException::class, 'ditangguhkan');
    expect(fn () => $this->service->createLoan($expired->id, [['book_id' => $book->id, 'quantity' => 1]]))
        ->toThrow(MemberNotEligibleException::class, 'berakhir');
});

it('invariant: peminjaman minimal 1 buku dengan jumlah minimal 1', function () {
    $member = libraryMember();
    $book = libraryBook();

    expect(fn () => $this->service->createLoan($member->id, []))->toThrow(InvalidArgumentException::class);
    expect(fn () => $this->service->createLoan($member->id, [['book_id' => $book->id, 'quantity' => 0]]))
        ->toThrow(InvalidArgumentException::class);
});

it('pengembalian tepat waktu: stok pulih, status anggota aktif, tanpa denda', function () {
    Event::fake([BookReturned::class]);
    $member = libraryMember();
    $book = libraryBook(5);
    $loan = $this->service->createLoan($member->id, [['book_id' => $book->id, 'quantity' => 2]]);

    $returned = $this->service->returnLoan($loan);

    expect($returned->status)->toBe('returned')
        ->and($returned->returned_at)->not->toBeNull()
        ->and($returned->fines)->toHaveCount(0)
        ->and($book->fresh()->stock)->toBe(5)
        ->and($member->fresh()->active_loans_count)->toBe(0)
        ->and($member->fresh()->status)->toBe('active');

    Event::assertDispatched(BookReturned::class);
});

it('pengembalian terlambat: denda dihitung otomatis lewat Value Object Denda', function () {
    $member = libraryMember();
    $book = libraryBook(5);
    $loan = $this->service->createLoan($member->id, [['book_id' => $book->id, 'quantity' => 1]]);

    $this->travelTo(now()->addDays(17)); // jatuh tempo 14 hari -> terlambat 3 hari

    $returned = $this->service->returnLoan($loan);

    expect($returned->fines)->toHaveCount(1)
        ->and($returned->fines->first()->days_late)->toBe(3)
        ->and((float) $returned->fines->first()->amount)->toBe(3000.0)
        ->and(Fine::count())->toBe(1);
});

it('tidak bisa mengembalikan peminjaman dua kali', function () {
    $member = libraryMember();
    $loan = $this->service->createLoan($member->id, [['book_id' => libraryBook()->id, 'quantity' => 1]]);
    $this->service->returnLoan($loan);

    expect(fn () => $this->service->returnLoan($loan))->toThrow(DomainException::class);
});

it('listener BookBorrowed mencatat log', function () {
    $member = libraryMember();
    $book = libraryBook();
    Event::fake([BookBorrowed::class]);
    $loan = $this->service->createLoan($member->id, [['book_id' => $book->id, 'quantity' => 1]]);

    Log::shouldReceive('info')->once()->with('Buku dipinjam', Mockery::on(
        fn ($ctx) => $ctx['loan_number'] === $loan->loan_number && $ctx['books'][0]['isbn'] === $book->isbn
    ));

    (new LogBookBorrowed())->handle(new BookBorrowed($loan));
});

it('listener terdaftar untuk event BookBorrowed', function () {
    expect(Event::hasListeners(BookBorrowed::class))->toBeTrue();
});

it('API: POST /library/loans berhasil, dan 422 saat stok tidak cukup', function () {
    $user = User::factory()->create();
    $member = libraryMember();
    $ok = libraryBook(5);
    $low = libraryBook(1);

    $this->actingAs($user)
        ->postJson('/library/loans', ['member_id' => $member->id, 'items' => [['book_id' => $ok->id, 'quantity' => 1]]])
        ->assertCreated()
        ->assertJsonPath('details.0.book_title', $ok->title);

    $this->actingAs($user)
        ->postJson('/library/loans', ['member_id' => $member->id, 'items' => [['book_id' => $low->id, 'quantity' => 2]]])
        ->assertStatus(422)
        ->assertJsonPath('error', 'insufficient_stock');

    expect(Loan::count())->toBe(1)->and($low->fresh()->stock)->toBe(1);
});
