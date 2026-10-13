<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $posts = Post::with('user')->paginate(3);
        return view('SportClub.blog',compact('posts'));
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
        $validatedData=$request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:6048',
            'title' => 'required|string',
            'description' => 'required|string',
        ]);
        $post= new Post ;

        $post->user_id = Auth::id();
        $post->title = $validatedData['title'];
        $post->description = $validatedData['description'];


        if($request->hasFile('image')){
            $image_name = uniqid().'-'.Str::slug( $validatedData['title']).'.'.$validatedData['image']->extension()  ;

            $post->image=$image_name ;
            $validatedData['image']->move(public_path('images/post') , $image_name);
          
        }
        
       
        $post->save();
        return back()->with('message' , 'post has been published');

    }

    /**
     * Display the specified resource.
     */
    public function show( $id)
    {
        $post = Post::find($id);
        
        return view('SportClub/blog-details' , compact('post'));

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit( $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        //
    }
}