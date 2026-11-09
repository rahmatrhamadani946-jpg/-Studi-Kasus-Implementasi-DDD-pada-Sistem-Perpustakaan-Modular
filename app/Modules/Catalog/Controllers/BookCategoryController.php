<?php

namespace Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalog\Models\BookCategory;

class BookCategoryController extends Controller
{
    /** GET /library/categories -> pohon kategori (2 tingkat) + jumlah buku */
    public function index(): JsonResponse
    {
        $categories = BookCategory::query()
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->withCount('books')])
            ->withCount('books')
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    /** POST /library/categories (admin) */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:book_categories,slug'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:book_categories,id'],
        ]);

        return response()->json(BookCategory::create($data)->load('parent'), 201);
    }
}
