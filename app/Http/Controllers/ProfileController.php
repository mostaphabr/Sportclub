<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Public profile of a user: /profile/{user id}
     */
    public function index(User $user)
    {
        // Older accounts may have no profile row yet: create an empty one.
        $profile = $user->profile ?? $user->profile()->save(new Profile());
        $profile->setRelation('user', $user);

        $posts = $user->posts()->latest()->get();

        return view('SportClub/profile', compact('profile', 'posts'));
    }

    /**
     * Update the logged-in user's own profile.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'bio' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:20'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
        ], [
            'email.unique' => 'This email is already used by another account.',
        ]);

        $user->name = $validatedData['name'];
        $user->email = strtolower($validatedData['email']);

        $profile = $user->profile ?? new Profile();
        $profile->Bio = $validatedData['bio'] ?? null;
        $profile->phone_number = $validatedData['phone'] ?? null;

        if ($request->hasFile('image')) {
            $image_name = uniqid().'-'.Str::slug($validatedData['name']).'.'.$validatedData['image']->extension();

            // Remove the previous picture so old files don't pile up.
            Uploads::delete('profile', $profile->profile_picture);

            $profile->profile_picture = Uploads::store($validatedData['image'], 'profile', $image_name);
        }

        $user->save();
        $user->profile()->save($profile);

        return back()->with('message', 'Your profile has been updated.');
    }
}
