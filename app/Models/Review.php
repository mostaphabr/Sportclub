<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A player's rating (1-5) and optional comment about a pitch.
 */
class Review extends Model
{
    protected $fillable = ['user_id', 'pitche_id', 'rating', 'comment'];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pitche()
    {
        return $this->belongsTo(Pitche::class);
    }
}
