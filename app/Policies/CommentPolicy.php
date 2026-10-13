<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

/**
 * Admins can remove any comment; authors can remove their own.
 */
class CommentPolicy
{
    public function delete(User $user, Comment $comment): bool
    {
        return $user->isAdmin() || (int) $user->id === (int) $comment->user_id;
    }
}
