<?php

namespace App\Http\Controllers;

use App\Models\Pitche;
use App\Models\Reservation;
use App\Notifications\NewReservationForProvider;
use App\Notifications\ReservationCancelled;
use App\Notifications\ReservationConfirmed;
use App\Support\Notify;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class ReservationController extends Controller
{
    public const SORTS = [
        'name' => 'Name (A-Z)',
        'rating' => 'Best rated',
        'price_asc' => 'Price: low to high',
        'price_desc' => 'Price: high to low',
    ];

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

    /**
     * Terrains list with search and filters:
     * ?q=name or city  &size=10  &hour=20 (free tomorrow at 20:00)  &max_price=200  &sort=rating
     */
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'size' => (int) $request->query('size', 0),
            'hour' => $request->filled('hour') ? (int) $request->query('hour') : null,
            'max_price' => $request->filled('max_price') ? max(0, (int) $request->query('max_price')) : null,
            'sort' => array_key_exists($request->query('sort'), self::SORTS) ? $request->query('sort') : 'name',
        ];

        $pitches = Pitche::query()
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $query->where(fn ($q) => $q->where('name', 'like', "%{$filters['q']}%")
                    ->orWhere('localisation', 'like', "%{$filters['q']}%"));
            })
            ->when($filters['size'] > 0, fn ($query) => $query->where('capacity', $filters['size']))
            ->when($filters['max_price'] !== null, fn ($query) => $query->whereNotNull('price_per_hour')->where('price_per_hour', '<=', $filters['max_price']))
            ->get();

        // "Free tomorrow at H": inside opening hours and not booked yet.
        if ($filters['hour'] !== null) {
            $taken = Reservation::whereDate('date', self::playDay()->toDateString())
                ->where('debut', $filters['hour'])
                ->pluck('pitche_id')
                ->all();

            $pitches = $pitches->filter(fn (Pitche $p) => $p->isOpenAt($filters['hour']) && ! in_array($p->id, $taken));
        }

        $pitches = match ($filters['sort']) {
            'rating' => $pitches->sortByDesc(fn ($p) => [$p->reviews_avg_rating ?? -1, $p->reviews_count]),
            'price_asc' => $pitches->sortBy(fn ($p) => $p->price_per_hour ?? PHP_INT_MAX),
            'price_desc' => $pitches->sortByDesc(fn ($p) => $p->price_per_hour ?? -1),
            default => $pitches->sortBy(fn ($p) => mb_strtolower($p->name)),
        };

        $perPage = 6;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $terrain = new LengthAwarePaginator(
            $pitches->values()->forPage($page, $perPage),
            $pitches->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $routeName = Route::currentRouteName();
        $user_role = Auth::check() ? auth()->user()->role : null;
        $sizes = Pitche::query()->select('capacity')->distinct()->orderBy('capacity')->pluck('capacity');
        $sorts = self::SORTS;

        return view('SportClub/pitches', compact('terrain', 'user_role', 'routeName', 'filters', 'sizes', 'sorts'));
    }

    public function create()
    {
        return redirect()->route('reservation.index');
    }

    /**
     * Book one hour on a pitch for tomorrow (optionally looking for teammates).
     */
    public function store(Request $request)
    {
        $this->authorize('create', Reservation::class);

        $validated = $request->validate([
            'pitche_id' => ['required', 'integer', 'exists:pitches,id'],
            'hour' => ['required', 'regex:/^\d{1,2}-\d{1,2}$/'],
            'players_needed' => ['nullable', 'integer', 'min:0', 'max:29'],
            'match_note' => ['nullable', 'string', 'max:255'],
        ], [
            'hour.required' => 'Please choose an hour.',
            'hour.regex' => 'Please choose an hour from the list.',
        ]);

        $pitche = Pitche::with('user')->findOrFail($validated['pitche_id']);
        [$debut, $fin] = array_map('intval', explode('-', $validated['hour']));
        $date = self::playDay()->toDateString();
        $playersNeeded = (int) ($validated['players_needed'] ?? 0);

        // The hour must be one of this pitch's opening slots.
        if (! in_array(['debut' => $debut, 'fin' => $fin], $pitche->slots(), true)) {
            return back()->withErrors(['hour' => 'This hour is outside the opening hours of this pitch.'])->withInput();
        }

        if ($playersNeeded > max(0, (int) $pitche->capacity - 1)) {
            return back()->withErrors(['players_needed' => 'This terrain is for '.$pitche->capacity.' players, so you can look for at most '.max(0, $pitche->capacity - 1).'.'])->withInput();
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
        $reservation->price = $pitche->price_per_hour; // price at booking time
        $reservation->players_needed = $playersNeeded;
        $reservation->match_note = $validated['match_note'] ?? null;

        try {
            $reservation->save();
        } catch (QueryException $e) {
            // Two people pressed "reserve" at the same moment: the unique index stops the second one.
            if ($this->isTaken($pitche->id, $date, $debut)) {
                return back()->withErrors(['hour' => 'Sorry, this hour was just booked by someone else. Please choose another one.'])->withInput();
            }
            throw $e;
        }

        $reservation->setRelation('pitche', $pitche);
        $reservation->setRelation('user', $request->user());
        Notify::send($request->user(), new ReservationConfirmed($reservation));
        Notify::send($pitche->user, new NewReservationForProvider($reservation));

        $message = 'Your reservation at '.$pitche->name.' on '.self::playDay()->format('l d/m').' from '
            .$reservation->timeRange().' is confirmed. We look forward to welcoming you!';
        if ($playersNeeded > 0) {
            $message .= ' Your match is listed in "Find a match" so '.$playersNeeded.' player(s) can ask to join.';
        }

        return redirect()->route('reservation.index')->with('message', $message);
    }

    /**
     * Booking page for one pitch: tomorrow's hours, price, rating and reviews.
     */
    public function show(string $name)
    {
        $pitche = Pitche::where('name', $name)
            ->with('user.profile')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->firstOrFail();
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
        $reviews = $pitche->reviews()->with('user.profile')->latest()->take(20)->get();

        return view('SportClub/reservation', [
            'pitche' => $pitche,
            'slots' => $slots,
            'disponible' => $disponible,
            'playDay' => $playDay,
            'routeName' => $routeName,
            'dailyLimit' => self::dailyLimitFor($user),
            'hoursBooked' => self::hoursBookedOn($user->id, $playDay->toDateString()),
            'reviews' => $reviews,
            'myReview' => $reviews->firstWhere('user_id', $user->id) ?? $pitche->reviews()->where('user_id', $user->id)->first(),
            'canReview' => ReviewController::canReview($user, $pitche),
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
     * Change how many teammates the booker is looking for (and the note).
     */
    public function updateTeam(Request $request, Reservation $reservation)
    {
        $this->authorize('manageTeam', $reservation);

        $reservation->loadMissing('pitche');
        $max = max(0, (int) $reservation->pitche->capacity - 1);

        $validated = $request->validate([
            'players_needed' => ['required', 'integer', 'min:0', 'max:'.$max],
            'match_note' => ['nullable', 'string', 'max:255'],
        ]);

        $accepted = $reservation->acceptedCount();
        if ($validated['players_needed'] < $accepted) {
            return back()->withErrors(['players_needed' => "You already accepted {$accepted} player(s). Remove some before lowering the number."]);
        }

        $reservation->players_needed = $validated['players_needed'];
        $reservation->match_note = $validated['match_note'] ?? null;
        $reservation->save();

        return back()->with('message', $reservation->players_needed > 0
            ? 'Your match is listed in "Find a match" with '.$reservation->spotsLeft().' open spot(s).'
            : 'Your match is no longer listed in "Find a match".');
    }

    /**
     * Cancel a reservation (player, the pitch's provider, or an admin).
     * Everyone concerned gets an email.
     */
    public function destroy(Reservation $reservation)
    {
        $this->authorize('delete', $reservation);

        $reservation->load(['pitche.user', 'user', 'matchRequests.user']);
        $actor = auth()->user();

        $cancelledBy = match (true) {
            (int) $actor->id === (int) $reservation->user_id => 'the organizer, '.$actor->name,
            $actor->isAdmin() => 'an administrator',
            default => 'the terrain owner',
        };

        $toWarn = collect([$reservation->user, optional($reservation->pitche)->user])
            ->merge($reservation->matchRequests->where('status', 'accepted')->pluck('user'))
            ->filter(fn ($user) => $user && (int) $user->id !== (int) $actor->id);

        if ($reservation->pitche) {
            Notify::send($toWarn, ReservationCancelled::for($reservation, $cancelledBy));
        }

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
