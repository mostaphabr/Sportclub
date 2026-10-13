<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ProfileController extends Controller
{


    public function index($id){
        $profile=Profile::where('id',$id)->with('user')->first();
        $posts=$profile->user->posts;
        

        return view('SportClub/profile',compact('profile','posts'));

    }



    public function store(Request $request){
        $validatedData=$request->validate([ 
            'image'=>'image',
            'name'=>'string',
            'email'=>'email',
            'bio'=>'nullable|string',
            'phone'=>'nullable|string  ',
        ]);
        
        $user=auth()->user();
        $user->name =$validatedData['name'] ;
        $user->email =$validatedData['email'] ;
        // ------------------------------
        $profile =auth()->user()->profile ;
        $profile->Bio=$validatedData['bio'];
        $profile->phone_number=$validatedData['phone'];

        if($request->hasFile('image')){
            $image_name = uniqid().'-'.Str::slug( $validatedData['name']).'.'.$validatedData['image']->extension()  ;

            if ($profile->profile_picture) {
                $imagePath = 'images/pitches'.'/'. $profile->image;
                if (File::exists($imagePath)) {
                    File::delete($imagePath) ;
                }
            }
            $profile->profile_picture=$image_name ;

            $validatedData['image']->move(public_path('images/profile') , $image_name);
        }
       


        $user->save(); 
        $profile->save();

        return back();


    }
}


