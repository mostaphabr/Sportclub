<?php

namespace App\Http\Controllers;

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
        $pitches = $user->pitches()->withCount([
            'reservations',
            'reservations as upcoming_reservations_count' => fn ($query) => $query->upcoming(),
        ])->get();

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

        return view('SportClub/pitches-reservations', compact('upcoming', 'past', 'title', 'showPlayer'));
    }


    // normale user func
    public function Myreservation() {

        $user = Auth::user();

        $upcoming = $user->reservations()->upcoming()->with('pitche')
            ->orderBy('date')->orderBy('debut')->get();
        $past = $user->reservations()->past()->with('pitche')
            ->orderByDesc('date')->orderBy('debut')->take(50)->get();

        $title = 'My reservations';
        $showPlayer = false;

        return view('SportClub/pitches-reservations', compact('upcoming', 'past', 'title', 'showPlayer'));
    }
}
