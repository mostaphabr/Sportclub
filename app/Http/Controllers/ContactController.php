<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function show()
    {
        return view('SportClub/contact');
    }

    /**
     * Save a message from the Contact page. Admins read it in the dashboard (Messages tab).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        ContactMessage::create($validated + ['user_id' => optional($request->user())->id]);

        return redirect()->route('contact')->with('message', 'Thank you! Your message has been sent. We will answer you by email.');
    }
}
