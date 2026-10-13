<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Reservation;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
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
}