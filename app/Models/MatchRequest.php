<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A player asking to join someone else's booking ("find teammates").
 */
class MatchRequest extends Model
{
    public const PENDING = 'pending';
    public const ACCEPTED = 'accepted';
    public const DECLINED = 'declined';

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', self::ACCEPTED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::PENDING);
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::ACCEPTED;
    }
}
