<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\ContactMessage;
use App\Models\Pitche;
use App\Models\Post;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Support\Uploads;
use Illuminate\Support\Str;

/**
 * Admin dashboard. Every route to this controller goes through the
 * "auth" + "admin" middleware (see routes/web.php).
 */
class AdminDashbordController extends Controller
{
    public const TABS = ['overview', 'pitches', 'users', 'posts', 'reservations', 'messages'];

    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Dashboard with tabs: overview, pitches, users, posts, reservations.
     */
    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'overview';
        $search = trim((string) $request->query('q', ''));
        $playDay = ReservationController::playDay();

        $providers = User::where('role', 'provider')->orderBy('name')->get();

        $stats = [
            'users' => User::count(),
            'players' => User::where('role', 'user')->count(),
            'providers' => $providers->count(),
            'admins' => User::where('role', 'admin')->count(),
            'pitches' => Pitche::count(),
            'posts' => Post::count(),
            'comments' => Comment::count(),
            'reservations_next' => Reservation::whereDate('date', $playDay->toDateString())->count(),
            'reservations_upcoming' => Reservation::upcoming()->count(),
        ];

        $pitches = Pitche::with('user')
            ->withCount(['reservations as upcoming_reservations_count' => fn ($query) => $query->upcoming()])
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->withCount(['posts', 'reservations'])
            ->orderBy('name')
            ->paginate(10, ['*'], 'users_page')
            ->withQueryString();

        $posts = Post::with('user')
            ->withCount('comments')
            ->latest()
            ->paginate(10, ['*'], 'posts_page')
            ->withQueryString();

        $reservations = Reservation::upcoming()
            ->with(['user.profile', 'pitche'])
            ->orderBy('date')
            ->orderBy('debut')
            ->paginate(15, ['*'], 'reservations_page')
            ->withQueryString();

        $messages = ContactMessage::with('user')
            ->orderByRaw('read_at is not null')
            ->latest()
            ->paginate(15, ['*'], 'messages_page')
            ->withQueryString();
        $unreadMessages = ContactMessage::unread()->count();

        return view('SportClub/Dashbord', compact(
            'tab', 'search', 'playDay', 'providers', 'stats', 'pitches', 'users', 'posts', 'reservations',
            'messages', 'unreadMessages'
        ));
    }

    public function create()
    {
        return redirect()->route('AdminDashbord.index', ['tab' => 'pitches']);
    }

    /**
     * Add a pitch ("terrain").
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:pitches,name',
            'provider' => 'required|integer|exists:users,id',
            'capacity' => 'required|integer|min:2|max:30',
            'localisation' => 'required|string|max:255',
            'openhours' => ['required', 'string', 'regex:/^\s*\d{1,2}\s*(?:[:hH]\s*\d{0,2})?\s*-\s*\d{1,2}\s*(?:[:hH]\s*\d{0,2})?\s*$/'],
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:6048'
        ], [
            'openhours.regex' => 'Open hours must look like 8:00-21:00.',
        ]);

        $image_name = uniqid() . '-' . Str::slug($validatedData['name'], '-') . '.' . $validatedData['image']->extension();

        Uploads::store($validatedData['image'], 'pitches', $image_name);

        $pitche = new Pitche;
        $pitche->name = $validatedData['name'];
        $pitche->user_id = $validatedData['provider'];
        $pitche->capacity = $validatedData['capacity'];
        $pitche->localisation = $validatedData['localisation'];
        $pitche->openhours = $validatedData['openhours'];
        $pitche->image = $image_name;

        $pitche->save();

        return redirect()->route('AdminDashbord.index', ['tab' => 'pitches'])
            ->with('message', 'Terrain "'.$pitche->name.'" has been added.');
    }

    public function show(string $id)
    {
        return redirect()->route('AdminDashbord.index');
    }

    public function edit(string $id)
    {
        $pitche = Pitche::findOrFail($id);
        $providers = User::where('role', 'provider')->orderBy('name')->get();

        return view('SportClub/pitche-edit', compact('pitche', 'providers'));
    }

    public function update(Request $request, string $id)
    {
        $pitche = Pitche::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:pitches,name,'.$pitche->id,
            'provider' => 'required|integer|exists:users,id',
            'capacity' => 'required|integer|min:2|max:30',
            'localisation' => 'nullable|string|max:255',
            'openhours' => ['nullable', 'string', 'regex:/^\s*\d{1,2}\s*(?:[:hH]\s*\d{0,2})?\s*-\s*\d{1,2}\s*(?:[:hH]\s*\d{0,2})?\s*$/'],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:6048'
        ], [
            'openhours.regex' => 'Open hours must look like 8:00-21:00.',
        ]);

        $pitche->name = trim($validatedData['name']);
        $pitche->user_id = $validatedData['provider'];
        $pitche->capacity = $validatedData['capacity'];
        if (! empty($validatedData['localisation'])) {
            $pitche->localisation = $validatedData['localisation'];
        }
        if (! empty($validatedData['openhours'])) {
            $pitche->openhours = $validatedData['openhours'];
        }

        if ($request->hasFile('image')) {
            $image_name = uniqid() . "-" . Str::slug($validatedData['name'], '-') . '.' . $validatedData['image']->extension();

            Uploads::delete('pitches', $pitche->image);
            Uploads::store($validatedData['image'], 'pitches', $image_name);

            $pitche->image = $image_name;
        }
        $pitche->save();

        return redirect()->route('AdminDashbord.index', ['tab' => 'pitches'])
            ->with('message', 'Terrain "'.$pitche->name.'" has been edited.');
    }

    public function destroy(string $id)
    {
        try {
            $pitche = Pitche::findOrFail($id);

            Uploads::delete('pitches', $pitche->image);

            $pitche->delete(); // its reservations are removed too (cascade)

            return redirect()->back()->with('message', 'Terrain has been deleted.');
        } catch (ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Record not found.');
        }
    }
}
