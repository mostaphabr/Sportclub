@extends('SportClub.layouts.parts')

@section('content')
    <div class="container   my-5  border border-2  rounded">
        <div class="p-5 ">
            <h1>Admin dashbord</h1>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>

                </div>
            @endif
            <h3 class="text-warning">edit terrain</h3>
            <form action={{ route('AdminDashbord.update' , $pitche->id) }} method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <label for="name">name</label>
                <input type="text" class="form-control mb-2" id="name" value="{{$pitche->name}} "name="name">

                <label for="provider">provider</label>
                @if (count($providers) > 0)
                    <select name="provider" id="provider" class="form-select">
                        <option class="bg-info"  disabled>select provider</option>
                        @foreach ($providers as $provider)
                        @if ($provider->id == $pitche->user_id)
                        <option value={{ $provider->id }} selected> {{ $provider->name }} </option>
                        @else
                            <option value="{{ $provider->id }}"> {{ $provider->name }} </option>
                            @endif
                        @endforeach
                    </select>
                    <br>
                @else
                    <p class="alert alert-danger ">no providers founded , add a providers in your databases </p>
                @endif


                <label for="capacity">capacity</label>
                <input type="text"  value={{$pitche->capacity}} class="form-control mb-2" id="capacity" name="capacity">

                <label for="image">image</label>
                <input type="file" class="form-control mb-4" id="image" name="image">
                <input type="submit" class="btn btn-info"></input>
            </form>
        </div>

    </div>
@endsection

@section('terrain', 'pitche-edit')
