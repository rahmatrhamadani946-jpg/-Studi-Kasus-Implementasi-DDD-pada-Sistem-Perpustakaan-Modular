<?php

namespace Modules\Circulation\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Circulation\Events\BookBorrowed;
use Modules\Circulation\Events\BookReturned;
use Modules\Circulation\Models\Fine;
use Modules\Circulation\Models\Loan;
use Modules\Circulation\Models\LoanDetail;
use Modules\Shared\Contracts\BookServiceInterface;
use Modules\Shared\Contracts\MemberServiceInterface;
use Modules\Shared\Exceptions\InsufficientStockException;
use Modules\Shared\ValueObjects\Denda;

class LoanService
{
    public function __construct(
        private BookServiceInterface $bookService,
        private MemberServiceInterface $memberService
    ) {
    }

    /**
     * Membuat peminjaman dalam satu transaction:
     * kurangi stok, buat record peminjaman, update status anggota.
     * Jika salah satu gagal (mis. stok tidak mencukupi) semuanya di-rollback.
     *
     * @param  array<int, array{book_id:int, quantity:int}>  $items
     */
    public function createLoan(int $memberId, array $items, ?string $notes = null): Loan
    {
        $items = $this->normalizeItems($items);
        $totalBooks = array_sum(array_column($items, 'quantity'));

        $loan = DB::transaction(function () use ($memberId, $items, $totalBooks, $notes) {
            // Aturan keanggotaan dicek lewat contract (Membership Context)
            $policy = $this->memberService->checkEligibility($memberId, $totalBooks);

            $loan = Loan::create([
                'loan_number' => 'LN-' . strtoupper(uniqid()),
                'member_id' => $memberId,
                'borrowed_at' => now(),
                'due_at' => now()->addDays($policy['loan_period_days']),
                'status' => 'borrowed',
                'notes' => $notes,
            ]);

            foreach ($items as $item) {
                // Data buku diambil lewat contract (Catalog Context)
                $book = $this->bookService->getBookForLoan($item['book_id']);

                if (!$book) {
                    throw new InvalidArgumentException("Buku tidak ditemukan: {$item['book_id']}");
                }

                if (
                    !$this->bookService->isStockAvailable($book['id'], $item['quantity'])
                    || !$this->bookService->reduceStock($book['id'], $item['quantity'])
                ) {
                    throw new InsufficientStockException(
                        $book['title'],
                        $book['stock'],
                        $item['quantity']
                    );
                }

                // Snapshot data buku saat dipinjam
                LoanDetail::create([
                    'loan_id' => $loan->id,
                    'book_id' => $book['id'],
                    'book_title' => $book['title'],
                    'book_isbn' => $book['isbn'],
                    'quantity' => $item['quantity'],
                ]);
            }

            $this->memberService->markBorrowing($memberId, $totalBooks);

            return $loan->load('details');
        });

        // Domain event hanya ditembakkan setelah transaction berhasil di-commit
        event(new BookBorrowed($loan));

        return $loan;
    }

    /**
     * Pengembalian buku: stok dipulihkan, status anggota diperbarui,
     * dan denda dihitung otomatis lewat Value Object Denda.
     */
    public function returnLoan(Loan $loan): Loan
    {
        $returned = DB::transaction(function () use ($loan) {
            $loan = Loan::lockForUpdate()->findOrFail($loan->id);

            if ($loan->isReturned()) {
                throw new DomainException("Peminjaman {$loan->loan_number} sudah dikembalikan.");
            }

            $loan->load('details');
            $returnedAt = now();

            foreach ($loan->details as $detail) {
                $this->bookService->increaseStock($detail->book_id, $detail->quantity);
            }

            $this->memberService->markReturned($loan->member_id, $loan->details->sum('quantity'));

            $loan->update([
                'returned_at' => $returnedAt,
                'status' => 'returned',
            ]);

            $denda = Denda::calculate($loan->due_at, $returnedAt);

            if ($denda->hasFine()) {
                Fine::create([
                    'loan_id' => $loan->id,
                    'member_id' => $loan->member_id,
                    'days_late' => $denda->getDaysLate(),
                    'amount' => $denda->getAmount()->getAmount(),
                ]);
            }

            return $loan->load('details', 'fines');
        });

        event(new BookReturned($returned));

        return $returned;
    }

    /**
     * Invariant Loan aggregate: minimal 1 item, quantity >= 1.
     * Item dengan book_id sama digabung.
     *
     * @return array<int, array{book_id:int, quantity:int}>
     */
    private function normalizeItems(array $items): array
    {
        if (empty($items)) {
            throw new InvalidArgumentException('Peminjaman minimal berisi 1 buku.');
        }

        $merged = [];

        foreach ($items as $item) {
            $bookId = (int) $item['book_id'];
            $quantity = (int) $item['quantity'];

            if ($quantity < 1) {
                throw new InvalidArgumentException('Jumlah buku minimal 1.');
            }

            $merged[$bookId] = [
                'book_id' => $bookId,
                'quantity' => ($merged[$bookId]['quantity'] ?? 0) + $quantity,
            ];
        }

        return array_values($merged);
    }
}
