<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    /** Players book pitches; providers manage pitches, so they don't book. */
    public function create(User $user): bool
    {
        return ! $user->isProvider();
    }

    /**
     * Cancel a reservation:
     * - admin: any reservation
     * - provider: reservations on their own pitches
     * - player: their own, as long as the day of play hasn't passed
     */
    public function delete(User $user, Reservation $reservation): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isProvider()) {
            return $reservation->pitche && (int) $reservation->pitche->user_id === (int) $user->id;
        }

        return (int) $user->id === (int) $reservation->user_id && $reservation->isUpcoming();
    }
}
