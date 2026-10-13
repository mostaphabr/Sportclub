<?php

namespace App\Http\Controllers;

use App\Models\MatchRequest;
use App\Models\Pitche;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Players rate a terrain (1-5 stars) after playing there.
 */
class ReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Has this user played on this pitch (as the booker or as an accepted teammate)?
     */
    public static function canReview(?User $user, Pitche $pitche): bool
    {
        if (! $user || $user->isProvider()) {
            return false;
        }

        $booked = Reservation::where('pitche_id', $pitche->id)
            ->where('user_id', $user->id)
            ->whereDate('date', '<=', today()->toDateString())
            ->get()
            ->contains(fn (Reservation $r) => $r->hasBeenPlayed());

        if ($booked) {
            return true;
        }

        return MatchRequest::accepted()
            ->where('user_id', $user->id)
            ->whereHas('reservation', fn ($q) => $q->where('pitche_id', $pitche->id)->whereDate('date', '<=', today()->toDateString()))
            ->with('reservation')
            ->get()
            ->contains(fn (MatchRequest $m) => $m->reservation->hasBeenPlayed());
    }

    /**
     * Add or update my review of a pitch.
     */
    public function store(Request $request, Pitche $pitche)
    {
        if (! self::canReview($request->user(), $pitche)) {
            return back()->withErrors(['rating' => 'You can rate a terrain after you have played there.']);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        Review::updateOrCreate(
            ['user_id' => $request->user()->id, 'pitche_id' => $pitche->id],
            ['rating' => $validated['rating'], 'comment' => $validated['comment'] ?? null],
        );

        return redirect()->to(route('reservation.show', $pitche->name).'#reviews')
            ->with('message', 'Thanks! Your review has been saved.');
    }

    /**
     * Delete a review (its author, or an admin for moderation).
     */
    public function destroy(Review $review)
    {
        $this->authorize('delete', $review);

        $review->delete();

        return back()->with('message', 'The review has been deleted.');
    }
}
