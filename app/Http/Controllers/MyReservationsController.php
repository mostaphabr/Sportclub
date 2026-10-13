<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyReservationsController extends Controller
{

    // Provider two functions
    public function ProviderDashboard() {

        $user = Auth::user();
        $pitches = $user->pitches;
    
    
        return view('SportClub/ProviderDashboard', compact('pitches'));
    }

    public function MyTerrainReservations($id) {

        $user = Auth::user();
        $pitche = $user->pitches->find($id);
    
        $reservations = $pitche->reservations()->with('user')->get();
    
    
        return view('SportClub/pitches-reservations', compact('reservations'));
    }


    // normale user func
    public function Myreservation() {

        $user = Auth::user();
        $reservations = $user->reservations()->with('pitche')->get();
    
        return view('SportClub/pitches-reservations', compact('reservations'));
    }
}