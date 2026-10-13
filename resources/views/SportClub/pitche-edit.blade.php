@extends('SportClub.layouts.parts')

@section('content')
    <div class="container my-5 border border-2 rounded">
        <div class="p-5">
            <a href="{{ route('AdminDashbord.index', ['tab' => 'pitches']) }}" class="small">&larr; Back to dashboard</a>
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
            <form action="{{ route('AdminDashbord.update', $pitche->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <label for="name">name</label>
                <input type="text" class="form-control mb-2" id="name" value="{{ old('name', $pitche->name) }}" name="name" required>

                <label for="provider">provider</label>
                @if (count($providers) > 0)
                    <select name="provider" id="provider" class="form-select">
                        <option class="bg-info" disabled>select provider</option>
                        @foreach ($providers as $provider)
                            <option value="{{ $provider->id }}" @selected(old('provider', $pitche->user_id) == $provider->id)> {{ $provider->name }} </option>
                        @endforeach
                    </select>
                    <br>
                @else
                    <p class="alert alert-danger ">no providers founded , add a providers in your databases </p>
                @endif


                <label for="capacity">capacity</label>
                <input type="number" min="2" max="30" value="{{ old('capacity', $pitche->capacity) }}" class="form-control mb-2" id="capacity" name="capacity">

                <label for="localisation">localisation</label>
                <input type="text" value="{{ old('localisation', $pitche->localisation) }}" class="form-control mb-2" id="localisation" name="localisation">

                <label for="openhours">openhours</label>
                <input type="text" placeholder="example : 8:00-21:00" value="{{ old('openhours', $pitche->openhours) }}" class="form-control mb-2" id="openhours" name="openhours">

                <label for="image">image (leave empty to keep the current one)</label>
                <input type="file" class="form-control mb-4" id="image" name="image" accept="image/*">
                <input type="submit" class="btn btn-info" value="Save">
            </form>
        </div>

    </div>
@endsection

@section('title', 'pitche-edit')
