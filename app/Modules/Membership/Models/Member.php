<?php

namespace Modules\Membership\Models;

use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'member_role_id',
        'member_number',
        'name',
        'email',
        'phone',
        'address',
        'status',
        'active_loans_count',
        'joined_at',
        'membership_expires_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'membership_expires_at' => 'datetime',
    ];

    protected static function newFactory(): MemberFactory
    {
        return MemberFactory::new();
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(MemberRole::class, 'member_role_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', 'suspended');
    }

    public function scopeSearch(Builder $query, string $keyword): Builder
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('email', 'like', "%{$keyword}%")
                ->orWhere('member_number', 'like', "%{$keyword}%");
        });
    }
}
