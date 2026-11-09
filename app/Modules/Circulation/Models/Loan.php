<?php

namespace Modules\Circulation\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Aggregate Root Circulation Context (Peminjaman).
 * Sengaja TIDAK punya relasi Eloquent ke Member (context lain);
 * hanya menyimpan member_id sebagai referensi.
 */
class Loan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'loan_number',
        'member_id',
        'borrowed_at',
        'due_at',
        'returned_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'borrowed_at' => 'datetime',
        'due_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(LoanDetail::class);
    }

    public function fines(): HasMany
    {
        return $this->hasMany(Fine::class);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'borrowed')->where('due_at', '<', now());
    }

    public function isReturned(): bool
    {
        return $this->returned_at !== null;
    }
}
