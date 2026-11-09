<?php

namespace Modules\Membership\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Membership\Models\Member;

class MemberController extends Controller
{
    /** GET /library/members?q= */
    public function index(Request $request): JsonResponse
    {
        $members = Member::query()
            ->with('role')
            ->when($request->query('q'), fn ($query, $q) => $query->search($q))
            ->orderBy('name')
            ->paginate(15);

        return response()->json($members);
    }

    public function show(Member $member): JsonResponse
    {
        return response()->json($member->load('role'));
    }

    /** POST /library/members (admin) */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'member_role_id' => ['required', 'integer', 'exists:member_roles,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:members,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'membership_expires_at' => ['nullable', 'date'],
        ]);

        $member = Member::create($data + [
            'member_number' => 'MBR-' . strtoupper(Str::random(6)),
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return response()->json($member->load('role'), 201);
    }

    /** DELETE /library/members/{member} (admin) -> soft delete */
    public function destroy(Member $member): JsonResponse
    {
        $member->delete();

        return response()->json(['message' => 'Anggota dihapus (soft delete).']);
    }

    /** GET /library/members-trashed (admin) */
    public function trashed(): JsonResponse
    {
        return response()->json(Member::onlyTrashed()->with('role')->orderBy('name')->get());
    }

    /** PATCH /library/members/{id}/restore (admin) */
    public function restore(int $id): JsonResponse
    {
        $member = Member::onlyTrashed()->findOrFail($id);
        $member->restore();

        return response()->json($member->load('role'));
    }
}
