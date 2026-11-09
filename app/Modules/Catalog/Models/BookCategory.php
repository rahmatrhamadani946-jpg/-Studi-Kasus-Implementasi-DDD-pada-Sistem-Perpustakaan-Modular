<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookCategory extends Model
{
    protected $table = 'book_categories';

    protected $fillable = ['name', 'slug', 'description', 'parent_id'];

    // Self-referencing: kategori -> sub-kategori
    public function parent(): BelongsTo
    {
        return $this->belongsTo(BookCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(BookCategory::class, 'parent_id');
    }

    // Many-to-Many dengan pivot added_at
    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'book_book_category', 'book_category_id', 'book_id')
            ->withPivot('added_at');
    }
}
