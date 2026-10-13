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
