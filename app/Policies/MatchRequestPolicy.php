<?php

namespace App\Policies;

use App\Models\MatchRequest;
use App\Models\User;

class MatchRequestPolicy
{
    /** Accept or decline: only the player who booked the match. */
    public function respond(User $user, MatchRequest $matchRequest): bool
    {
        return (int) $user->id === (int) optional($matchRequest->reservation)->user_id;
    }

    /** Cancel / leave (the requester), remove a player (the booker), or an admin. */
    public function delete(User $user, MatchRequest $matchRequest): bool
    {
        return $user->isAdmin()
            || (int) $user->id === (int) $matchRequest->user_id
            || $this->respond($user, $matchRequest);
    }
}
