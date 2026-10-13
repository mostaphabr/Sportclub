<?php

namespace App\Http\Controllers;

use App\Models\MatchRequest;
use Illuminate\Support\Facades\Auth;

class MyReservationsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Provider two functions
    public function ProviderDashboard() {

        $user = Auth::user();
        $month = [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];

        $pitches = $user->pitches()
            ->withCount([
                'reservations',
                'reservations as upcoming_reservations_count' => fn ($query) => $query->upcoming(),
                'reservations as bookings_month' => fn ($query) => $query->whereBetween('date', $month),
            ])
            ->withSum(['reservations as revenue_month' => fn ($query) => $query->whereBetween('date', $month)], 'price')
            ->withSum('reservations as revenue_total', 'price')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->get();

        return view('SportClub/ProviderDashboard', compact('pitches'));
    }

    /**
     * Provider: reservations on one of *their* pitches (404 for anyone else's pitch).
     */
    public function MyTerrainReservations($id) {

        $pitche = Auth::user()->pitches()->findOrFail($id);

        $upcoming = $pitche->reservations()->upcoming()->with(['user.profile', 'pitche'])
            ->orderBy('date')->orderBy('debut')->get();
        $past = $pitche->reservations()->past()->with(['user.profile', 'pitche'])
            ->orderByDesc('date')->orderBy('debut')->take(50)->get();

        $title = 'Reservations for '.$pitche->name;
        $showPlayer = true;
        $joining = collect();

        return view('SportClub/pitches-reservations', compact('upcoming', 'past', 'title', 'showPlayer', 'joining'));
    }


    // normale user func
    public function Myreservation() {

        $user = Auth::user();

        $upcoming = $user->reservations()->upcoming()->with(['pitche', 'matchRequests.user.profile'])
            ->orderBy('date')->orderBy('debut')->get();
        $past = $user->reservations()->past()->with('pitche')
            ->orderByDesc('date')->orderBy('debut')->take(50)->get();

        // Matches of other players that I was accepted in.
        $joining = MatchRequest::accepted()
            ->where('user_id', $user->id)
            ->whereHas('reservation', fn ($q) => $q->upcoming())
            ->with(['reservation.pitche', 'reservation.user.profile'])
            ->get()
            ->sortBy(fn ($m) => [$m->reservation->date->timestamp, $m->reservation->debut])
            ->values();

        $title = 'My reservations';
        $showPlayer = false;

        return view('SportClub/pitches-reservations', compact('upcoming', 'past', 'title', 'showPlayer', 'joining'));
    }
}
