<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\MatchRequest;
use App\Models\Post;
use App\Models\Reservation;
use App\Models\Review;
use App\Policies\CommentPolicy;
use App\Policies\MatchRequestPolicy;
use App\Policies\PostPolicy;
use App\Policies\ReservationPolicy;
use App\Policies\ReviewPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Post::class => PostPolicy::class,
        Comment::class => CommentPolicy::class,
        Reservation::class => ReservationPolicy::class,
        MatchRequest::class => MatchRequestPolicy::class,
        Review::class => ReviewPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        //
    }
}
