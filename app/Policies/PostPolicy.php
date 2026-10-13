<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

/**
 * Laravel finds this policy automatically for App\Models\Post.
 * Admins moderate every post; authors manage their own.
 */
class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Post $post): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Post $post): bool
    {
        return (int) $user->id === (int) $post->user_id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->isAdmin() || (int) $user->id === (int) $post->user_id;
    }

    public function restore(User $user, Post $post): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Post $post): bool
    {
        return $user->isAdmin();
    }
}
