<?php

namespace Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Models\Book;
use Modules\Shared\ValueObjects\Isbn;

class BookController extends Controller
{
    /** Relasi yang selalu di-eager load agar tidak terjadi N+1. */
    private const RELATIONS = ['authors', 'publisher', 'categories'];

    /**
     * GET /library/books?q=&category_id=&available=1
     * Query scopes: search, byCategory, available.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer'],
            'available' => ['nullable', 'boolean'],
        ]);

        $books = Book::query()
            ->with(self::RELATIONS)
            ->when($data['q'] ?? null, fn ($query, $q) => $query->search($q))
            ->when($data['category_id'] ?? null, fn ($query, $id) => $query->byCategory((int) $id))
            ->when($request->boolean('available'), fn ($query) => $query->available())
            ->orderBy('title')
            ->paginate(15);

        return response()->json($books);
    }

    public function show(Book $book): JsonResponse
    {
        return response()->json($book->load(self::RELATIONS));
    }

    /** POST /library/books (admin) */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'string', function ($attribute, $value, $fail) {
                if (!Isbn::isValid($value)) {
                    $fail('Format ISBN tidak valid.');
                }
            }],
            'description' => ['nullable', 'string'],
            'published_year' => ['nullable', 'integer', 'between:1000,2100'],
            'stock' => ['required', 'integer', 'min:0'],
            'publisher_id' => ['nullable', 'integer', 'exists:publishers,id'],
            'author_ids' => ['nullable', 'array'],
            'author_ids.*' => ['integer', 'exists:authors,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:book_categories,id'],
        ]);

        // ISBN disimpan kanonik (ISBN-13), jadi keunikan dicek pada bentuk kanonik
        $canonical = (new Isbn($data['isbn']))->toIsbn13();

        if (Book::withTrashed()->where('isbn', $canonical)->exists()) {
            throw ValidationException::withMessages(['isbn' => 'ISBN sudah terdaftar.']);
        }

        $book = DB::transaction(function () use ($data) {
            $book = Book::create($data);

            $book->authors()->sync($data['author_ids'] ?? []);

            foreach ($data['category_ids'] ?? [] as $categoryId) {
                $book->categories()->attach($categoryId, ['added_at' => now()]);
            }

            return $book;
        });

        return response()->json($book->load(self::RELATIONS), 201);
    }

    /** DELETE /library/books/{book} (admin) -> soft delete */
    public function destroy(Book $book): JsonResponse
    {
        $book->delete();

        return response()->json(['message' => 'Buku dihapus (soft delete).']);
    }

    /** GET /library/books-trashed (admin) */
    public function trashed(): JsonResponse
    {
        return response()->json(
            Book::onlyTrashed()->with(self::RELATIONS)->orderBy('title')->get()
        );
    }

    /** PATCH /library/books/{id}/restore (admin) */
    public function restore(int $id): JsonResponse
    {
        $book = Book::onlyTrashed()->findOrFail($id);
        $book->restore();

        return response()->json($book->load(self::RELATIONS));
    }
}
