<?php

namespace Modules\Membership\Services;

use Modules\Membership\Models\Member;
use Modules\Shared\Contracts\MemberServiceInterface;
use Modules\Shared\Exceptions\MemberNotEligibleException;

class MemberService implements MemberServiceInterface
{
    public function checkEligibility(int $memberId, int $bookCount): array
    {
        $member = Member::with('role')->lockForUpdate()->find($memberId);

        if (!$member) {
            throw MemberNotEligibleException::notFound($memberId);
        }

        if ($member->status === 'suspended') {
            throw new MemberNotEligibleException("Anggota {$member->name} sedang ditangguhkan.");
        }

        if ($member->membership_expires_at && $member->membership_expires_at->isPast()) {
            throw new MemberNotEligibleException("Keanggotaan {$member->name} telah berakhir.");
        }

        $maxLoans = $member->role->max_loans;

        if ($member->active_loans_count + $bookCount > $maxLoans) {
            throw new MemberNotEligibleException(
                "Batas peminjaman terlampaui. {$member->name} sedang meminjam " .
                "{$member->active_loans_count} buku, batas maksimal {$maxLoans} buku."
            );
        }

        return [
            'member_id' => $member->id,
            'name' => $member->name,
            'max_loans' => $maxLoans,
            'loan_period_days' => $member->role->loan_period_days,
            'active_loans' => $member->active_loans_count,
        ];
    }

    public function markBorrowing(int $memberId, int $bookCount): void
    {
        $member = Member::lockForUpdate()->findOrFail($memberId);

        $member->active_loans_count += $bookCount;

        if ($member->status !== 'suspended') {
            $member->status = 'borrowing';
        }

        $member->save();
    }

    public function markReturned(int $memberId, int $bookCount): void
    {
        $member = Member::withTrashed()->lockForUpdate()->findOrFail($memberId);

        $member->active_loans_count = max(0, $member->active_loans_count - $bookCount);

        if ($member->status !== 'suspended') {
            $member->status = $member->active_loans_count > 0 ? 'borrowing' : 'active';
        }

        $member->save();
    }

    public function getMembersByIds(array $memberIds): array
    {
        return Member::withTrashed()
            ->whereIn('id', $memberIds)
            ->get(['id', 'name', 'member_number'])
            ->mapWithKeys(fn (Member $m) => [$m->id => [
                'id' => $m->id,
                'name' => $m->name,
                'member_number' => $m->member_number,
            ]])
            ->all();
    }
}
