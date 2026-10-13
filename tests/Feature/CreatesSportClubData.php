<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Pitche;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Reservation;
use App\Models\User;

/**
 * Small helpers to create SportClub records in tests.
 */
trait CreatesSportClubData
{
    protected function makeUser(string $role = 'user', array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->role = $role;
        $user->save();
        $user->profile()->save(new Profile());

        return $user->fresh();
    }

    protected function makePitch(?User $provider = null, array $attributes = []): Pitche
    {
        $provider ??= $this->makeUser('provider');

        $pitche = new Pitche();
        $pitche->user_id = $provider->id;
        $pitche->name = $attributes['name'] ?? 'Terrain '.uniqid();
        $pitche->capacity = $attributes['capacity'] ?? 10;
        $pitche->image = 'test.jpg';
        $pitche->localisation = 'Khemisset';
        $pitche->openhours = $attributes['openhours'] ?? '8:00-21:00';
        $pitche->save();

        return $pitche;
    }

    protected function makeReservation(User $user, Pitche $pitche, string $date, int $debut): Reservation
    {
        $reservation = new Reservation();
        $reservation->user_id = $user->id;
        $reservation->pitche_id = $pitche->id;
        $reservation->date = $date;
        $reservation->debut = $debut;
        $reservation->fin = $debut + 1;
        $reservation->save();

        return $reservation;
    }

    protected function makePost(User $author, string $title = 'Match report'): Post
    {
        $post = new Post();
        $post->user_id = $author->id;
        $post->title = $title;
        $post->description = 'Some text';
        $post->image = 'test.jpg';
        $post->save();

        return $post;
    }

    protected function makeComment(User $author, Post $post, string $content = 'Nice!'): Comment
    {
        $comment = new Comment();
        $comment->user_id = $author->id;
        $comment->post_id = $post->id;
        $comment->content = $content;
        $comment->save();

        return $comment;
    }
}
