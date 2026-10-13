@extends('SportClub.layouts.parts')

@section('content')

    <main id="main">

        <!-- ======= Breadcrumbs ======= -->
        <div class="breadcrumbs">
            <div class="page-header d-flex align-items-center" style="background-image: url('');">
                <div class="container position-relative">
                    <div class="row d-flex justify-content-center">
                        <div class="col-lg-8 text-center">
                            <h2 class="text-warning">Blog</h2>

                            @if ($errors->any())

                                <div class="alert alert-danger">
                                    <ul class="text-dark">
                                        @foreach ($errors->all() as $error)
                                        <li>{{$error}}</li>
                                        @endforeach
                                    </ul>

                                </div>
                            @endif

                            <a class="btn btn-outline-light mt-3" data-bs-toggle="collapse" href="#postform" role="button"
                                aria-expanded="false" aria-controls="postform">
                                create post
                            </a>
                            <div class="collapse" id="postform">
                                <form action={{ route('posts.store') }} method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="image" class="form-label text-light fs-4">image</label>
                                        <input type="file" class="form-control" name="image" id="image">
                                      
                                    </div>
                                    <div class="mb-3">
                                        <label for="title" class="form-label text-light fs-4">title</label>
                                        <input type="text" class="form-control" name="title" id="title">
                                      
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label text-light fs-4">description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                       
                                    </div>
                                    <input type="submit" class="btn btn-light" value="submit">
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <nav class="p-0">
                <div class="container  d-flex justify-content-between ">
                    <ol>
                        <li><a href={{ route('home') }}>Home</a></li>
                        <li>Blog</li>
                    </ol>
                    <div>
                        <a class=" m-left-2 nav-item " href="/profile/{{ auth()->user()->profile->id }}" role="button">

                            @if (auth()->user()->profile->profile_picture)
                                <img class="rounded-circle" style="width: 65px ; height:65px"
                                    src={{ asset('images/profile/' . auth()->user()->profile->profile_picture) }}
                                    alt="">
                                <h5 class=" d-flex mx-auto pt-1 fw-light text-capitalize ">profile</h5>
                            @else
                                <img class="rounded-circle" style="width: 63px ; height:63px"
                                    src={{ asset('images/profile/Default.jpg') }}>
                                <h5 class=" d-flex mx-auto pt-1 fw-light text-capitalize ">profile</h5>
                            @endif
                        </a>
                    </div>

                </div>
            </nav>
        </div><!-- End Breadcrumbs -->

        <!-- ======= Blog Section ======= -->
        <section id="blog" class="blog">
            <div class="container" data-aos="fade-up">

                <div class="row gy-4 posts-list">

                    @if ($posts)
                        @foreach ($posts as $post)
                            <div class="col-xl-4 col-md-6">
                                <article>

                                    <a href="{{ route('posts.show', $post->id) }}">
                                        <div class="post-img">
                                            <img src={{ asset('images/post/' . $post->image) }} alt="post-image"
                                                class="img-fluid">
                                        </div>



                                        <h2 class="title">
                                            <p>{{ $post->title }}</p>
                                        </h2>


                                    </a>

                                    <div class="d-flex align-items-center">
                                        @if ($post->user->profile->profile_picture)
                                            <img src="{{ asset('images/profile/' . $post->user->profile->profile_picture) }}"
                                                alt="" class="img-fluid post-author-img flex-shrink-0">
                                        @else
                                            <img src="{{ asset('images/profile/Default.jpg') }}" alt=""
                                                class="img-fluid post-author-img flex-shrink-0">
                                        @endif


                                        <div class="post-meta">
                                            <a href="{{ route('profile', $post->user->id) }}"
                                                class="post-author-list">{{ $post->user->name }}</a>
                                            <p class="post-date">
                                                <time datetime="2022-01-01">{{ $post->created_at }}</time>
                                            </p>
                                        </div>
                                    </div>

                                </article>
                            </div><!-- End post list item -->
                        @endforeach
                    @endif



                    <!-- End blog iination -->

                </div>

                <div class="paginator">
                    {{ $posts->links() }}
                </div>
        </section><!-- End Blog Section -->

    </main><!-- End #main -->


@endsection

@section('title', 'blog')
