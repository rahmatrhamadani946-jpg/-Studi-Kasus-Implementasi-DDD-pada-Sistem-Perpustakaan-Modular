<?php

namespace Modules\Circulation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Circulation\Models\Loan;
use Modules\Circulation\Services\LoanService;
use Modules\Shared\Contracts\MemberServiceInterface;

class LoanController extends Controller
{
    private const RELATIONS = ['details', 'fines'];

    public function __construct(
        private LoanService $loanService,
        private MemberServiceInterface $memberService
    ) {
    }

    /** GET /library/loans?status=borrowed|returned|overdue */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $loans = Loan::query()
            ->with(self::RELATIONS)
            ->when($status === 'overdue', fn ($q) => $q->overdue())
            ->when(in_array($status, ['borrowed', 'returned'], true), fn ($q) => $q->byStatus($status))
            ->latest('borrowed_at')
            ->paginate(15);

        $this->attachMembers($loans->getCollection());

        return response()->json($loans);
    }

    public function show(Loan $loan): JsonResponse
    {
        $loan->load(self::RELATIONS);
        $this->attachMembers(collect([$loan]));

        return response()->json($loan);
    }

    /** POST /library/loans */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'member_id' => ['required', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.book_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        $loan = $this->loanService->createLoan($data['member_id'], $data['items'], $data['notes'] ?? null);

        return response()->json($loan->load(self::RELATIONS), 201);
    }

    /** POST /library/loans/{loan}/return */
    public function returnLoan(Loan $loan): JsonResponse
    {
        return response()->json($this->loanService->returnLoan($loan));
    }

    /** DELETE /library/loans/{loan} (admin) -> soft delete */
    public function destroy(Loan $loan): JsonResponse
    {
        $loan->delete();

        return response()->json(['message' => 'Peminjaman dihapus (soft delete).']);
    }

    /** GET /library/loans-trashed (admin) */
    public function trashed(): JsonResponse
    {
        $loans = Loan::onlyTrashed()->with(self::RELATIONS)->latest('borrowed_at')->get();
        $this->attachMembers($loans);

        return response()->json($loans);
    }

    /** PATCH /library/loans/{id}/restore (admin) */
    public function restore(int $id): JsonResponse
    {
        $loan = Loan::onlyTrashed()->findOrFail($id);
        $loan->restore();

        return response()->json($loan->load(self::RELATIONS));
    }

    /**
     * Data anggota diambil lewat contract dalam SATU query (bukan N+1),
     * tanpa mengakses model Member milik context lain.
     */
    private function attachMembers(Collection $loans): void
    {
        $members = $this->memberService->getMembersByIds($loans->pluck('member_id')->unique()->all());

        $loans->each(fn (Loan $loan) => $loan->setAttribute('member', $members[$loan->member_id] ?? null));
    }
}
