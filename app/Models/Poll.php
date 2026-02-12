<?php

namespace App\Models;

use App\Enums\PollStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Poll extends Model
{
    /** @use HasFactory<\Database\Factories\PollFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'slug',
        'title',
        'description',
        'status',
        'expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'status' => PollStatus::class,
        ];
    }

    /**
     * Scope to polls owned by the currently authenticated user.
     */
    public function scopeOwnedByCurrentUser(Builder $query): Builder
    {
        return $query->where('user_id', Auth::id());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isAcceptingVotes(): bool
    {
        return $this->status->canReceiveResponses() && ! $this->isExpired();
    }
}
