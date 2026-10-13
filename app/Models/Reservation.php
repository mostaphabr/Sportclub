<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $casts = [
        'debut' => 'integer',
        'fin' => 'integer',
        'price' => 'integer',
        'players_needed' => 'integer',
    ];

    /**
     * Day of play. Always stored as "Y-m-d", read back as a Carbon date.
     */
    protected function date(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->startOfDay() : null,
            set: fn ($value) => $value ? Carbon::parse($value)->toDateString() : null,
        );
    }

    public function pitche(){
        return $this->belongsTo(Pitche::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }

    /** Players who asked to join this booking ("find teammates"). */
    public function matchRequests(){
        return $this->hasMany(MatchRequest::class);
    }

    /** How many players were accepted (uses withCount when loaded). */
    public function acceptedCount(): int
    {
        if (isset($this->accepted_requests_count)) {
            return (int) $this->accepted_requests_count;
        }

        return $this->relationLoaded('matchRequests')
            ? $this->matchRequests->where('status', MatchRequest::ACCEPTED)->count()
            : $this->matchRequests()->accepted()->count();
    }

    /** Open spots still free for teammates. */
    public function spotsLeft(): int
    {
        return max(0, (int) $this->players_needed - $this->acceptedCount());
    }

    /** The match hasn't started yet. */
    public function hasNotStarted(): bool
    {
        if (! $this->date) {
            return false;
        }

        return $this->date->gt(today()) || ($this->date->isSameDay(today()) && $this->debut > (int) now()->format('G'));
    }

    /** The hour has been played (so the player can rate the pitch). */
    public function hasBeenPlayed(): bool
    {
        if (! $this->date) {
            return false;
        }

        return $this->date->lt(today()) || ($this->date->isSameDay(today()) && $this->fin <= (int) now()->format('G'));
    }

    /** Matches looking for players: not started, spots left. */
    public function scopeOpenMatches(Builder $query): Builder
    {
        return $query->upcoming()
            ->where('players_needed', '>', 0)
            ->withCount(['matchRequests as accepted_requests_count' => fn ($q) => $q->accepted()]);
    }

    /** Reservations whose day of play is today or later. */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('date', '>=', today()->toDateString());
    }

    /** Reservations whose day of play has passed. */
    public function scopePast(Builder $query): Builder
    {
        return $query->whereDate('date', '<', today()->toDateString());
    }

    /** Still possible to cancel: the day of play hasn't passed yet. */
    public function isUpcoming(): bool
    {
        return $this->date !== null && $this->date->gte(today());
    }

    /** "10:00 - 11:00" */
    public function timeRange(): string
    {
        return sprintf('%02d:00 - %02d:00', $this->debut, $this->fin);
    }
}
