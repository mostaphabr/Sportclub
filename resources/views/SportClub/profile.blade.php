@extends('SportClub.layouts.parts')

@section('content')

    <main id="main">

        <!-- ======= Breadcrumbs ======= -->
        <div class="breadcrumbs">
            <div class="page-header d-flex align-items-center" style="background-image: url('');">
                <div class="container position-relative">
                    <div class="row d-flex justify-content-center">
                        <div class="col-lg-8 text-center">
                                <img class="rounded-circle" style="width: 200px; height: 200px; object-fit: cover"
                                    src="{{ $profile->pictureUrl() }}" alt="{{ $profile->user->name }}">
                                <h1 class="text-warning fw-light text-capitalize">{{$profile->user->name}}</h1>
                        </div>
                    </div>
                </div>
            </div>
           
        </div><!-- End Breadcrumbs -->

        <!-- ======= Blog Section ======= -->
        <div class="container   my-5  border border-2  rounded">
            <div class="p-5 ">
                <h1>Profile informations</h1>

                @if (session('message'))
                    <div class="alert alert-success">{{ session('message') }}</div>
                @endif
    
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
    
                    </div>
                @endif
                
                @if ($profile->user->id == Auth::id())
                     <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <label for="name">name</label>
                    <input type="text" value="{{ old('name', $profile->user->name) }}" class="form-control mb-2" id="name" name="name" required>

                    <label for="bio">bio </label>
                    <textarea class="form-control mb-2" id="bio" name="bio" maxlength="1000">{{ old('bio', $profile->Bio) }}</textarea>
                    <label for="phone">phone</label>
                    <input type="tel" value="{{ old('phone', $profile->phone_number) }}" class="form-control mb-2" id="phone" name="phone" maxlength="20">
                    <label for="email">email</label>
                    <input type="email" value="{{ old('email', $profile->user->email) }}" class="form-control mb-2" id="email" name="email" required>

                    <label for="image">image</label>
                    <input type="file" class="form-control mb-2" id="image" name="image" accept="image/*">
    
                    <input type="submit" value="edit" class="btn btn-info"></input>
                </form>
                @else

                <form action="/store" method="POST" enctype="multipart/form-data" class="text-capitalize">
                    @csrf
                    @method('PUT')
                    <label for="name">name</label>
                    <input disabled  type="text" value="{{$profile->user->name}}" class="form-control mb-2" id="name" name="name">
                    <label for="role" > Role :  <span class="text-primary">{{$profile->user->role}}</span></label>
                    <br>
                    

                    <label for="bio">bio </label>
                    <textarea disabled type="text"   class="form-control mb-2" id="bio" name="bio">{{$profile->Bio}}</textarea>
                    <label for="phone">phone number</label>
                    <input disabled type="phone" value="{{$profile->phone_number}}" class="form-control mb-2" id="phone" name="phone">

                    
                </form>

                @endif
               
            </div>
    
        </div>

           <!-- ======= Blog Section ======= -->
           <section id="blog" class="blog">
            <div class="container" data-aos="fade-up">

                <div class="row gy-4 posts-list">

                    @if ($posts)
                        @foreach ($posts as $post)
                             <div class="col-xl-4 col-md-6">
                        <article>

                            <div class="post-img">
                                <img src="{{ $post->imageUrl() }}" alt="post-image" class="img-fluid">
                            </div>

                           

                            <h2 class="title">
                                <a href="{{route('posts.show' , $post->id)}}">{{$post->title}}</a>
                            </h2>

                            @can('delete', $post)
                                <form action="{{ route('posts.destroy', $post) }}" method="POST"
                                    onsubmit="return confirm('Delete this post and its comments?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
                                </form>
                            @endcan

                           

                        </article>
                    </div><!-- End post list item -->
                        @endforeach
                    @endif
                   


                    <!-- End blog pagination -->

                </div>

               
        </section><!-- End Blog Section -->

    </main><!-- End #main -->


@endsection

@section('title', 'profile')
