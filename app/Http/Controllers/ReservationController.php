<?php

namespace App\Http\Controllers;

use App\Models\Pitche;
use App\Models\Reservation;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class ReservationController extends Controller
{
    public function __construct() {
        $this->middleware('auth')->except('index') ;
    }

    /**
     * Bookings are always for the next day ("tomorrow only").
     */
    public static function playDay(): Carbon
    {
        return today()->addDay();
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

    public function create()
    {
        return redirect()->route('reservation.index');
    }

    /**
     * Book one hour on a pitch for tomorrow.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Reservation::class);

        $validated = $request->validate([
            'pitche_id' => ['required', 'integer', 'exists:pitches,id'],
            'hour' => ['required', 'regex:/^\d{1,2}-\d{1,2}$/'],
        ], [
            'hour.required' => 'Please choose an hour.',
            'hour.regex' => 'Please choose an hour from the list.',
        ]);

        $pitche = Pitche::findOrFail($validated['pitche_id']);
        [$debut, $fin] = array_map('intval', explode('-', $validated['hour']));
        $date = self::playDay()->toDateString();

        // The hour must be one of this pitch's opening slots.
        if (! in_array(['debut' => $debut, 'fin' => $fin], $pitche->slots(), true)) {
            return back()->withErrors(['hour' => 'This hour is outside the opening hours of this pitch.'])->withInput();
        }

        if ($this->isTaken($pitche->id, $date, $debut)) {
            return back()->withErrors(['hour' => 'Sorry, this hour was just booked by someone else. Please choose another one.'])->withInput();
        }

        // Fair use: a player can only book a few hours per day (config/sportclub.php).
        $limit = self::dailyLimitFor($request->user());
        $alreadyBooked = self::hoursBookedOn($request->user()->id, $date);
        if ($limit !== null && $alreadyBooked >= $limit) {
            return back()->withErrors([
                'hour' => "You can book at most {$limit} hours per day, and you already have {$alreadyBooked} for tomorrow.",
            ])->withInput();
        }

        $reservation = new Reservation();
        $reservation->user_id = $request->user()->id;
        $reservation->pitche_id = $pitche->id;
        $reservation->date = $date;
        $reservation->debut = $debut;
        $reservation->fin = $fin;

        try {
            $reservation->save();
        } catch (QueryException $e) {
            // Two people pressed "reserve" at the same moment: the unique index stops the second one.
            if ($this->isTaken($pitche->id, $date, $debut)) {
                return back()->withErrors(['hour' => 'Sorry, this hour was just booked by someone else. Please choose another one.'])->withInput();
            }
            throw $e;
        }

        return redirect()->route('reservation.index')->with(
            'message',
            'Your reservation at '.$pitche->name.' on '.self::playDay()->format('l d/m').' from '
            .$reservation->timeRange().' is confirmed. We look forward to welcoming you!'
        );
    }

    /**
     * Booking page for one pitch: shows tomorrow's hours, free and booked.
     */
    public function show(string $name)
    {
        $pitche = Pitche::where('name', $name)->with('user.profile')->firstOrFail();
        $routeName = Route::currentRouteName();
        $playDay = self::playDay();

        $taken = $pitche->reservations()
            ->whereDate('date', $playDay->toDateString())
            ->pluck('debut')
            ->map(fn ($hour) => (int) $hour)
            ->all();

        $slots = array_map(fn ($slot) => $slot + ['taken' => in_array($slot['debut'], $taken, true)], $pitche->slots());

        $disponible = array_values(array_filter($slots, fn ($slot) => ! $slot['taken']));

        $user = auth()->user();

        return view('SportClub/reservation', [
            'pitche' => $pitche,
            'slots' => $slots,
            'disponible' => $disponible,
            'playDay' => $playDay,
            'routeName' => $routeName,
            'dailyLimit' => self::dailyLimitFor($user),
            'hoursBooked' => self::hoursBookedOn($user->id, $playDay->toDateString()),
        ]);
    }

    public function edit(string $id)
    {
        abort(404);
    }

    public function update(Request $request, string $id)
    {
        abort(404);
    }

    /**
     * Cancel a reservation (player, the pitch's provider, or an admin).
     */
    public function destroy(Reservation $reservation)
    {
        $this->authorize('delete', $reservation);

        $reservation->delete();

        return back()->with('message', 'The reservation has been cancelled.');
    }

    /** Max hours per day for this user, or null when there is no limit (admins). */
    public static function dailyLimitFor($user): ?int
    {
        $limit = (int) config('sportclub.max_hours_per_day', 2);

        return ($user && $user->isAdmin()) || $limit <= 0 ? null : $limit;
    }

    /** Hours this user has booked on a given day (each reservation is one hour). */
    public static function hoursBookedOn(int $userId, string $date): int
    {
        return Reservation::where('user_id', $userId)->whereDate('date', $date)->count();
    }

    private function isTaken(int $pitcheId, string $date, int $debut): bool
    {
        return Reservation::where('pitche_id', $pitcheId)
            ->whereDate('date', $date)
            ->where('debut', $debut)
            ->exists();
    }
}
