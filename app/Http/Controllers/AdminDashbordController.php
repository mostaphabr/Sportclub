<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Pitche;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Auth\Events\Validated;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AdminDashbordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {


        if (Auth::check()) {
            $user = auth()->user();
            if ($user->role === 'admin') {
                $providers = User::where('role', 'provider')->get();

                return view('SportClub/Dashbord', compact('providers'));
            } else return abort(401);
        } else {
            return redirect()->route('login');
        }
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



        if ($request->input('btn') == 'adduser') {
           dd($request);
        }else
        $validatedData = $request->validate([
            'name' => 'required',
            'provider' => 'required|integer',
            'capacity' => 'required|integer',
            'localisation' => 'required|string',
            'openhours' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:6048'

        ]);


        $image_name = uniqid() . '-' . Str::slug($validatedData['name'], '-') . '.' . $validatedData['image']->extension();

        $validatedData['image']->move(public_path('images/pitches'), $image_name);


        $pitche = new Pitche;
        $pitche->name = $request->name;
        $pitche->user_id = $request->provider;
        $pitche->capacity = $request->capacity;
        $pitche->localisation = $request->localisation;
        $pitche->openhours = $request->openhours;
        $pitche->image = $image_name;

        $pitche->save();

        return redirect()->route('reservation.index')->with('message' , 'terrain has been added');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {

        $pitche = Pitche::find($id);
        $providers = User::where('role', 'provider')->get();

        return view('SportClub/pitche-edit', compact('pitche', 'providers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'provider' => 'required|integer',
            'capacity' => 'required|integer',
            'image' => 'image|mimes:jpeg,png,jpg,svg,webp|max:6048'
        ]);
        $pitche = Pitche::find($id);
        $pitche->name = $request->name;
        $pitche->user_id = $request->provider;
        $pitche->capacity = $request->capacity;

        if ($request->hasFile('image')) {
            $image_name = uniqid() . "-" . Str::slug($validatedData['name'], '-') . '.' . $validatedData['image']->extension();

            $imagePath = 'images/pitches'.'/'. $pitche->image;

            if (File::exists($imagePath)) {

                File::delete($imagePath);
            }
            $validatedData['image']->move(public_path('images/pitches'), $image_name);


            $pitche->image = $image_name;
        }
        $pitche->save();
        return redirect()->route('reservation.index')->with('message', 'terrain has been edited');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {

            $pitche = Pitche::findOrFail($id);
            $pitche->delete();
            return redirect()->back()->with('message', 'item has been deleted');
        } catch (ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'record not found');
        }
    }
}