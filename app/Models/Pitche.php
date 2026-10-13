<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Support\Uploads;
use Illuminate\Database\Eloquent\Model;

class Pitche extends Model
{
    use HasFactory;

    protected $casts = [
        'capacity' => 'integer',
        'price_per_hour' => 'integer',
    ];

    /** Hours used when "openhours" can't be read (same as the old hard-coded calendar). */
    public const DEFAULT_OPEN = 8;
    public const DEFAULT_CLOSE = 20;

    public function reservations(){
        return $this->hasMany(Reservation::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function reviews(){
        return $this->hasMany(Review::class);
    }

    /** "5 vs 5" for a capacity of 10. */
    public function formatLabel(): string
    {
        $side = intdiv((int) $this->capacity, 2);

        return "{$side} vs {$side}";
    }

    /** "150 DH / hour", or null when no price is set. */
    public function priceLabel(): ?string
    {
        return $this->price_per_hour !== null
            ? number_format((int) $this->price_per_hour, 0, ',', ' ').' '.config('sportclub.currency', 'DH').' / hour'
            : null;
    }

    /**
     * Average rating (1 decimal) — uses withAvg('reviews', 'rating') when loaded.
     */
    public function averageRating(): ?float
    {
        $avg = $this->reviews_avg_rating ?? ($this->relationLoaded('reviews') ? $this->reviews->avg('rating') : $this->reviews()->avg('rating'));

        return $avg !== null ? round((float) $avg, 1) : null;
    }

    public function reviewsCount(): int
    {
        return (int) ($this->reviews_count ?? ($this->relationLoaded('reviews') ? $this->reviews->count() : $this->reviews()->count()));
    }

    /** Is hour $hour (e.g. 20 for 20:00-21:00) inside the opening hours? */
    public function isOpenAt(int $hour): bool
    {
        [$open, $close] = $this->openingRange();

        return $hour >= $open && $hour < $close;
    }

    /** URL of the pitch photo. */
    public function imageUrl(): ?string
    {
        return Uploads::url('pitches', $this->image);
    }

    /**
     * One-hour slots built from the "openhours" text the admin typed,
     * e.g. "8:00-21:00" or "9h-23h" gives 8-9, 9-10, ... 20-21.
     *
     * @return array<int, array{debut:int, fin:int}>
     */
    public function slots(): array
    {
        [$open, $close] = $this->openingRange();

        $slots = [];
        for ($hour = $open; $hour < $close; $hour++) {
            $slots[] = ['debut' => $hour, 'fin' => $hour + 1];
        }

        return $slots;
    }

    /**
     * @return array{0:int, 1:int} [opening hour, closing hour]
     */
    public function openingRange(): array
    {
        $pattern = '/(\d{1,2})\s*(?:[:hH]\s*\d{0,2})?\s*(?:-|–|to|à|a)\s*(\d{1,2})/u';

        if ($this->openhours && preg_match($pattern, $this->openhours, $m)) {
            $open = (int) $m[1];
            $close = (int) $m[2];

            if ($open >= 0 && $close <= 24 && $open < $close) {
                return [$open, $close];
            }
        }

        return [self::DEFAULT_OPEN, self::DEFAULT_CLOSE + 1];
    }
}
