<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PostController extends Controller
{
    /**
     * Blog: all posts, newest first.
     */
    public function index()
    {
        $posts = Post::with('user.profile')->latest()->paginate(3);
        return view('SportClub.blog',compact('posts'));
    }

    public function create()
    {
        return redirect()->route('posts.index');
    }

    /**
     * Publish a post.
     */
    public function store(Request $request)
    {
        $validatedData=$request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:6048',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
        ]);
        $post= new Post ;

        $post->user_id = Auth::id();
        $post->title = $validatedData['title'];
        $post->description = $validatedData['description'];

        $image_name = uniqid().'-'.Str::slug($validatedData['title']).'.'.$validatedData['image']->extension();
        $post->image = Uploads::store($validatedData['image'], 'post', $image_name);

        $post->save();
        return back()->with('message' , 'post has been published');
    }

    /**
     * One post with its comments.
     */
    public function show( $id)
    {
        $post = Post::with(['user.profile', 'comments.user'])->findOrFail($id);

        return view('SportClub/blog-details' , compact('post'));
    }

    /**
     * Edit form (author only).
     */
    public function edit(Post $post)
    {
        $this->authorize('update', $post);

        return view('SportClub/post-edit', compact('post'));
    }

    /**
     * Save changes to a post (author only). The image is optional: keep the old one if none is sent.
     */
    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $validatedData = $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:6048',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
        ]);

        $post->title = $validatedData['title'];
        $post->description = $validatedData['description'];

        if ($request->hasFile('image')) {
            $image_name = uniqid().'-'.Str::slug($validatedData['title']).'.'.$validatedData['image']->extension();

            Uploads::delete('post', $post->image);
            $post->image = Uploads::store($validatedData['image'], 'post', $image_name);
        }

        $post->save();

        return redirect()->route('posts.show', $post->id)->with('message', 'Your post has been updated.');
    }

    /**
     * Delete a post: admins can remove any post (moderation); authors can remove their own.
     */
    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        Uploads::delete('post', $post->image);

        $title = $post->title;
        $post->delete(); // comments are removed too (cascade)

        $message = (int) auth()->id() === (int) $post->user_id
            ? 'Your post "'.$title.'" has been deleted.'
            : 'The post "'.$title.'" has been removed.';

        // Coming from the post page itself? That page no longer exists, so go to the blog.
        if (url()->previous() === route('posts.show', $post->id)) {
            return redirect()->route('posts.index')->with('message', $message);
        }

        return back()->with('message', $message);
    }
}
