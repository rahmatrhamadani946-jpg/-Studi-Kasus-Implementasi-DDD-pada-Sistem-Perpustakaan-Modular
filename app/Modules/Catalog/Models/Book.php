<?php

namespace Modules\Catalog\Models;

use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\ValueObjects\Isbn;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'publisher_id',
        'title',
        'isbn',
        'description',
        'published_year',
        'stock',
    ];

    protected static function newFactory(): BookFactory
    {
        return BookFactory::new();
    }

    /**
     * Setiap ISBN yang disimpan divalidasi lewat Value Object Isbn
     * dan disimpan dalam bentuk kanonik ISBN-13.
     */
    protected function isbn(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => (new Isbn($value))->toIsbn13(),
        );
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class);
    }

    // Many-to-Many dengan pivot added_at
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(BookCategory::class, 'book_book_category', 'book_id', 'book_category_id')
            ->withPivot('added_at');
    }

    // ---- Query scopes ----

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeSearch(Builder $query, string $keyword): Builder
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('title', 'like', "%{$keyword}%")
                ->orWhere('isbn', 'like', "%{$keyword}%")
                ->orWhere('description', 'like', "%{$keyword}%");
        });
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->whereHas('categories', function ($q) use ($categoryId) {
            $q->where('book_categories.id', $categoryId);
        });
    }

    public function isInStock(int $quantity = 1): bool
    {
        return $this->stock >= $quantity;
    }
}
