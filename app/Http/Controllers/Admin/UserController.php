<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Admin: add users to the database and manage them.
 * Routes are in routes/web.php under the "admin." name prefix, behind auth + admin.
 */
class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Create a user with a generated password.
     * The password is shown once to the admin, who passes it on to the user.
     */
    public function store(Request $request)
    {
        $validated = $request->validateWithBag('addUser', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(User::ROLES)],
        ]);

        $password = self::generatePassword();

        $user = DB::transaction(function () use ($validated, $password) {
            $user = new User();
            $user->name = $validated['name'];
            $user->email = strtolower($validated['email']);
            $user->password = Hash::make($password);
            $user->role = $validated['role'];
            $user->save();

            // Same as registration: every user gets an (empty) profile.
            $user->profile()->save(new Profile());

            return $user;
        });

        return redirect()->route('AdminDashbord.index', ['tab' => 'users'])
            ->with('message', ucfirst($user->role).' "'.$user->name.'" has been added.')
            ->with('generated_password', [
                'name' => $user->name,
                'email' => $user->email,
                'password' => $password,
            ]);
    }

    /**
     * Change someone's role (user / provider / admin).
     */
    public function updateRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(User::ROLES)],
        ]);

        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot change your own role.');
        }

        if ($user->isProvider() && $validated['role'] !== 'provider' && $user->pitches()->exists()) {
            return back()->with('error', $user->name.' still owns pitches. Give those pitches to another provider first.');
        }

        $user->role = $validated['role'];
        $user->save();

        return back()->with('message', $user->name.' is now '.$user->role.'.');
    }

    /**
     * Generate a new password for a user (e.g. they lost the first one).
     */
    public function resetPassword(Request $request, User $user)
    {
        $password = self::generatePassword();

        $user->password = Hash::make($password);
        $user->setRememberToken(Str::random(60));
        $user->save();

        return back()
            ->with('message', 'A new password has been generated for '.$user->name.'.')
            ->with('generated_password', [
                'name' => $user->name,
                'email' => $user->email,
                'password' => $password,
            ]);
    }

    /**
     * Remove a user. Their profile, posts, comments, reservations and pitches go too (cascade).
     */
    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot delete your own account from the dashboard.');
        }

        $name = $user->name;

        $picture = optional($user->profile)->profile_picture;
        if ($picture && File::exists(public_path('images/profile/'.$picture))) {
            File::delete(public_path('images/profile/'.$picture));
        }

        $user->delete();

        return back()->with('message', 'User "'.$name.'" has been deleted.');
    }

    /** 12 characters, letters and numbers only, so it's easy to pass on. */
    private static function generatePassword(): string
    {
        return Str::password(12, true, true, false, false);
    }
}
