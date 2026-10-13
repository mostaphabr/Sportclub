<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $comment =new Comment ;
        $validatedData = $request->validate([
            'comment' => 'required|string|max:2000',
            'id' => 'required|integer|exists:posts,id',
        ]) ;
        $comment->user_id = Auth::id();
        $comment->post_id = $request->id ;
        $comment->content = $validatedData['comment'] ;
        $comment->save();

        return back();
    }

    /**
     * Display the specified resource.
     */
    public function show(Comment $comment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Comment $comment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Comment $comment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Comment $comment)
    {
        // Admins can remove any comment; authors can remove their own.
        $this->authorize('delete', $comment);

        $comment->delete();

        return back()->with('message', 'The comment has been deleted.');
    }
}
