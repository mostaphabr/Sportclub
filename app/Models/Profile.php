<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Support\Uploads;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** URL of the profile picture, or the default avatar. */
    public function pictureUrl(): string
    {
        return Uploads::url('profile', $this->profile_picture, Uploads::DEFAULT_AVATAR);
    }
}
