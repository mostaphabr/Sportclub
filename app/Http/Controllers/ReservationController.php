<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Pitche;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function __construct() {
        $this->middleware('auth')->except('index') ;
    } 


    public function index()
    {
        $terrain = Pitche::paginate(3);
        $routeName = Route::currentRouteName();

        $user_role = null;
        if(Auth::check()){
               $user_role = auth()->user()->role ;
        }
     
        return view('SportClub/pitches', compact('terrain' , 'user_role' ,'routeName'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        
        $request->validate([
            'pitche_id' => 'required' ,
            'hour' => 'required' 
        ]);

        $reservation = new Reservation() ;

        $user = auth()->user() ;

        $reservation->user_id = $user->id ;
        $reservation->pitche_id = $request->pitche_id ;

        $hours =  explode( '-',$request->hour ) ;

       
        $reservation->debut = $hours[0] ;
        $reservation->fin =$hours[1];
        $reservation->save();
       
        return redirect()->route('reservation.index')->with('message' , 'Your reservation has been successfully confirmed. We look forward to welcoming you!');
       
    }

    /**
     * Display the specified resource.
     */
    public function show(string $name)
    {
        $calendrier = [
            ['debut' => 8, 'fin' => 9],
            ['debut' => 9, 'fin' => 10],
            ['debut' => 10, 'fin' => 11],
            ['debut' => 11, 'fin' => 12],
            ['debut' => 12, 'fin' => 13],
            ['debut' => 13, 'fin' => 14],
            ['debut' => 14, 'fin' => 15],
            ['debut' => 15, 'fin' => 16],
            ['debut' => 16, 'fin' => 17],
            ['debut' => 17, 'fin' => 18],
            ['debut' => 18, 'fin' => 19],
            ['debut' => 19, 'fin' => 20],
        ];
        
        

        
        $pitche = Pitche::where('name', $name)->firstOrFail();
        $routeName = Route::currentRouteName();


        $in_reservation = $pitche->reservations->select('debut', 'fin')->toArray();
        
        // Convert multidimensional arrays to simple arrays of string representations
        $calendrier_flat = array_map('serialize', $calendrier);
        $in_reservation_flat = array_map('serialize', $in_reservation);

        // Find the difference
        $disponible_flat = array_diff($calendrier_flat, $in_reservation_flat);

        // Convert the result back to multidimensional arrays
        $disponible = array_map('unserialize', $disponible_flat);
            


      
        
        
        
       


        return view('SportClub/reservation', ['pitche' => $pitche, 'disponible' => $disponible ,'routeName'=>$routeName ]);
        // return view('SportClub/reservation', ['field' => $name , 'reservations' => $in_reservation ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
       
    }
}
