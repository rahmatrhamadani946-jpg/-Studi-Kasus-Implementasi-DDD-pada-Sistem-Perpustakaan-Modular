<?php

namespace Modules\Catalog\Models;

use Database\Factories\AuthorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Author extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'bio'];

    protected static function newFactory(): AuthorFactory
    {
        return AuthorFactory::new();
    }

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class);
    }
}
