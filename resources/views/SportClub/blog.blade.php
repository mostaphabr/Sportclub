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

                            @if (session('message'))
                                <div class="alert alert-success text-dark">{{ session('message') }}</div>
                            @endif

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
                        <a class=" m-left-2 nav-item " href="{{ route('profile', auth()->user()) }}" role="button">
                            <img class="rounded-circle" style="width: 65px; height: 65px; object-fit: cover"
                                src="{{ auth()->user()->avatarUrl() }}" alt="">
                            <h5 class=" d-flex mx-auto pt-1 fw-light text-capitalize ">profile</h5>
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
                                            <img src="{{ $post->imageUrl() }}" alt="post-image"
                                                class="img-fluid">
                                        </div>



                                        <h2 class="title">
                                            <p>{{ $post->title }}</p>
                                        </h2>


                                    </a>

                                    <div class="d-flex align-items-center">
                                        <img src="{{ $post->user->avatarUrl() }}"
                                            alt="" class="img-fluid post-author-img flex-shrink-0">


                                        <div class="post-meta">
                                            <a href="{{ route('profile', $post->user) }}"
                                                class="post-author-list">{{ $post->user->name }}</a>
                                            <p class="post-date">
                                                <time datetime="2022-01-01">{{ $post->created_at }}</time>
                                            </p>
                                        </div>
                                    </div>

                                    @can('delete', $post)
                                        <form action="{{ route('posts.destroy', $post) }}" method="POST" class="mt-3"
                                            onsubmit="return confirm('Delete this post and its comments?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i> {{ auth()->id() == $post->user_id ? 'Delete' : 'Remove (admin)' }}
                                            </button>
                                        </form>
                                    @endcan

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
