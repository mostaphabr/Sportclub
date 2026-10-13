<?php

namespace App\Http\Controllers;

use App\Models\MatchRequest;
use App\Models\Reservation;
use App\Notifications\JoinRequestAnswered;
use App\Notifications\JoinRequestReceived;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * "Find a match": players who booked a terrain can look for teammates;
 * other players ask to join, and the booker accepts or declines.
 */
class MatchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Upcoming matches that still need players + the requests I sent.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $open = Reservation::openMatches()
            ->with(['pitche', 'user.profile'])
            ->where('user_id', '!=', $user->id)
            ->orderBy('date')
            ->orderBy('debut')
            ->get()
            ->filter(fn (Reservation $r) => $r->pitche && $r->hasNotStarted() && $r->spotsLeft() > 0)
            ->values();

        $perPage = 9;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $matches = new LengthAwarePaginator($open->forPage($page, $perPage), $open->count(), $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]);

        $myRequests = $user->matchRequests()
            ->with(['reservation.pitche', 'reservation.user.profile'])
            ->latest()
            ->get()
            ->filter(fn (MatchRequest $m) => $m->reservation && $m->reservation->pitche && $m->reservation->hasNotStarted())
            ->values();

        $requestStatus = $myRequests->pluck('status', 'reservation_id')->all();
        $canJoin = $user->can('join', Reservation::class);

        return view('SportClub/matches', compact('matches', 'myRequests', 'requestStatus', 'canJoin'));
    }

    /**
     * Ask to join a match.
     */
    public function store(Request $request, Reservation $reservation)
    {
        $user = $request->user();
        $this->authorize('join', Reservation::class);

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:255'],
        ]);

        $error = match (true) {
            (int) $reservation->user_id === (int) $user->id => 'This is your own booking.',
            ! $reservation->hasNotStarted() => 'This match has already started.',
            $reservation->spotsLeft() < 1 => 'Sorry, this match is already full.',
            $reservation->matchRequests()->where('user_id', $user->id)->exists() => 'You already asked to join this match.',
            default => null,
        };

        if ($error) {
            return back()->with('error', $error);
        }

        $matchRequest = new MatchRequest();
        $matchRequest->reservation_id = $reservation->id;
        $matchRequest->user_id = $user->id;
        $matchRequest->status = MatchRequest::PENDING;
        $matchRequest->message = $validated['message'] ?? null;
        $matchRequest->save();

        $matchRequest->load(['reservation.pitche', 'reservation.user', 'user']);
        Notify::send($reservation->user, new JoinRequestReceived($matchRequest));

        return back()->with('message', 'Request sent! '.$reservation->user->name.' will accept or decline it. You will get an email.');
    }

    /**
     * The booker accepts a player.
     */
    public function accept(MatchRequest $matchRequest)
    {
        $this->authorize('respond', $matchRequest);
        $reservation = $matchRequest->reservation;

        if (! $matchRequest->isPending()) {
            return back()->with('error', 'This request was already answered.');
        }

        if ($reservation->spotsLeft() < 1) {
            return back()->with('error', 'Your match is already full. Raise the number of players needed first.');
        }

        $matchRequest->status = MatchRequest::ACCEPTED;
        $matchRequest->save();

        $matchRequest->load(['reservation.pitche', 'reservation.user.profile', 'user']);
        Notify::send($matchRequest->user, new JoinRequestAnswered($matchRequest));

        return back()->with('message', $matchRequest->user->name.' joined your match.');
    }

    /**
     * The booker declines a player.
     */
    public function decline(MatchRequest $matchRequest)
    {
        $this->authorize('respond', $matchRequest);

        if (! $matchRequest->isPending()) {
            return back()->with('error', 'This request was already answered.');
        }

        $matchRequest->status = MatchRequest::DECLINED;
        $matchRequest->save();

        $matchRequest->load(['reservation.pitche', 'reservation.user', 'user']);
        Notify::send($matchRequest->user, new JoinRequestAnswered($matchRequest));

        return back()->with('message', 'Request declined.');
    }

    /**
     * The player cancels their request / leaves the match, or the booker removes a player.
     */
    public function destroy(MatchRequest $matchRequest)
    {
        $this->authorize('delete', $matchRequest);

        $leaving = (int) $matchRequest->user_id === (int) auth()->id();
        $matchRequest->delete();

        return back()->with('message', $leaving ? 'You left this match.' : 'The player was removed from your match.');
    }
}
