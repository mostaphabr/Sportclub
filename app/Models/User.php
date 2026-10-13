<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Reservation;
use App\Support\Uploads;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /** Roles a user can have (stored in users.role). */
    public const ROLES = ['user', 'provider', 'admin'];

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];


    public function profile()
    {
        return $this->hasOne(Profile::class);
    }


    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function pitches(){
        return $this->hasMany(Pitche::class);
    }
    public function posts(){
        return $this->hasMany(Post::class);
    }

    public function comments(){
        return $this->hasMany(Comment::class);
    }

    public function reviews(){
        return $this->hasMany(Review::class);
    }

    public function matchRequests(){
        return $this->hasMany(MatchRequest::class);
    }

    /** Profile picture URL (default avatar when there is none). */
    public function avatarUrl(): string
    {
        return $this->profile ? $this->profile->pictureUrl() : asset(Uploads::DEFAULT_AVATAR);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isProvider(): bool
    {
        return $this->role === 'provider';
    }
}
