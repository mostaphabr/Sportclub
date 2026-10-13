<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;

/**
 * Admin: messages sent from the Contact page.
 */
class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function markRead(ContactMessage $message)
    {
        $message->read_at = $message->read_at ? null : now();
        $message->save();

        return back();
    }

    public function destroy(ContactMessage $message)
    {
        $message->delete();

        return back()->with('message', 'The message has been deleted.');
    }
}
