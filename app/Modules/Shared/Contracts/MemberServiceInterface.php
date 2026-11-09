<?php

namespace Modules\Shared\Contracts;

use Modules\Shared\Exceptions\MemberNotEligibleException;

/**
 * Contract Membership Context yang dipakai Circulation Context.
 */
interface MemberServiceInterface
{
    /**
     * Memeriksa apakah anggota boleh meminjam $bookCount buku.
     *
     * @return array{member_id:int,name:string,max_loans:int,loan_period_days:int,active_loans:int}
     * @throws MemberNotEligibleException
     */
    public function checkEligibility(int $memberId, int $bookCount): array;

    /** Dipanggil saat peminjaman dibuat: tambah pinjaman aktif & ubah status anggota. */
    public function markBorrowing(int $memberId, int $bookCount): void;

    /** Dipanggil saat buku dikembalikan: kurangi pinjaman aktif & pulihkan status. */
    public function markReturned(int $memberId, int $bookCount): void;

    /**
     * @param  int[]  $memberIds
     * @return array<int, array{id:int,name:string,member_number:string}>
     */
    public function getMembersByIds(array $memberIds): array;
}
