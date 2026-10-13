<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /** Authors can remove their review; admins can remove any review (moderation). */
    public function delete(User $user, Review $review): bool
    {
        return $user->isAdmin() || (int) $user->id === (int) $review->user_id;
    }
}
