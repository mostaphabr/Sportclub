@extends('SportClub.layouts.parts')

@section('content')
    <div class="container   my-5  border border-2  rounded">
        <h1 class="p-2 text-center ">Admin Dashbord</h1>
        <div class="row">
            <div class="col-lg-12 p-5 ">


                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>

                    </div>
                @endif

                <h3 class="text-warning">add terrain</h3>
                <form action={{ route('AdminDashbord.store') }} method="POST" enctype="multipart/form-data">
                    @csrf
                    <label for="name">name</label>
                    <input type="text" class="form-control mb-2" id="name" name="name">

                    <label for="provider">provider</label>
                    @if (count($providers) > 0)
                        <select name="provider" id="provider" class="form-select">
                            <option class="bg-info" selected disabled>select provider</option>

                            @foreach ($providers as $provider)
                                <option value="{{ $provider->id }}"> {{ $provider->name }} </option>
                            @endforeach
                        </select>
                        <br>
                    @else
                        <p class="alert alert-danger ">no providers founded , add a providers in your databases </p>
                    @endif


                    <label for="capacity">capacity</label>
                    <input type="text" class="form-control mb-2" id="capacity" name="capacity">

                    <label for="localisation">localisation</label>
                    <input type="text" class="form-control mb-2" id="localisation" name="localisation">

                  

                    <label for="openhours">openhours</label>
                    <input type="text" placeholder="example : 8:00-21:00" class="form-control mb-2" id="openhours" name="openhours">

                    <label for="image">image</label>
                    <input type="file" class="form-control mb-4" id="image" name="image">

                    <input type="submit" name="btn" value="addterrain" class="btn btn-info"></input>

            </div>

            <div class="col-lg-12 p-5">
                <h3 class="text-warning">Add User</h3>

                </form>

                <form action={{ route('AdminDashbord.store') }} method="POST" enctype="multipart/form-data">
                    @csrf
                    <label for="name">name</label>
                    <input type="text" class="form-control mb-2" id="name" name="name">

                    <label for="email">email</label>
                    <input type="email" class="form-control mb-2" id="email" name="email">

                    <label for="role">Role</label>
                    <select name="role" id="role" class="form-control" >
                        
                        <option value="provider" selected >provider</option>
                        <option value="user"  >user</option>
                        <option value="admin"  >admin</option>
                    </select>

<br>
                    <input type="submit" name="btn" value="adduser" class="btn btn-info"></input>
                </form>
            </div>
        </div>


    </div>
@endsection

@section('title', 'dashbord')
