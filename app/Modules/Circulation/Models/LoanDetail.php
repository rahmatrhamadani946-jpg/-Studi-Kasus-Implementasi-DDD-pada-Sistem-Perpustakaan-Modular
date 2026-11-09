<?php

namespace Modules\Circulation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanDetail extends Model
{
    protected $fillable = ['loan_id', 'book_id', 'book_title', 'book_isbn', 'quantity'];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
