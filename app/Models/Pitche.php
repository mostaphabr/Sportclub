<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pitche extends Model
{
    use HasFactory;

    /** Hours used when "openhours" can't be read (same as the old hard-coded calendar). */
    public const DEFAULT_OPEN = 8;
    public const DEFAULT_CLOSE = 20;

    public function reservations(){
        return $this->hasMany(Reservation::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
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
