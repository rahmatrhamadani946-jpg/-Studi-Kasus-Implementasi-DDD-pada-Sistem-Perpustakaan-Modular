<?php

namespace Modules\Shared\Exceptions;

use Exception;

class MemberNotEligibleException extends Exception
{
    public static function notFound(int $memberId): self
    {
        return new self("Anggota tidak ditemukan: {$memberId}");
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'error' => 'member_not_eligible',
            ], 422);
        }

        return back()->withErrors(['member' => $this->getMessage()]);
    }
}
