<?php

/*
|--------------------------------------------------------------------------
| SportClub settings
|--------------------------------------------------------------------------
| Business rules of the platform in one place. Each value can be
| overridden from .env without touching the code.
*/

return [

    // A player can book at most this many hours per day (all pitches together).
    // Admins are not limited.
    'max_hours_per_day' => (int) env('SPORTCLUB_MAX_HOURS_PER_DAY', 2),

    // Currency shown next to prices.
    'currency' => env('SPORTCLUB_CURRENCY', 'DH'),

];
