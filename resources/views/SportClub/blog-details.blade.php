@extends('SportClub.layouts.parts')

@section('content')


    <main id="main">

        <!-- ======= Breadcrumbs ======= -->
        <div class="breadcrumbs">
            <div class="page-header d-flex align-items-center" style="background-image: url('');">
                <div class="container position-relative">
                    <div class="row d-flex justify-content-center">
                        <div class="col-lg-6 text-center">
                            <h2>comments section</h2>
                        </div>
                    </div>
                </div>
            </div>
            <nav>
                <div class="container">
                    <ol>
                        <li><a href="{{ route('home') }}">Home</a></li>
                        <li>comments </li>
                    </ol>
                </div>
            </nav>
        </div><!-- End Breadcrumbs -->

        <!-- ======= Blog Details Section ======= -->
        <section id="blog" class="blog">
            <div class="container" data-aos="fade-up">

                <div class="row g-5 p-2">

                    <div class="col-lg-6 ">

                        <article class="blog-details">

                            <div class="post-img">
                                <img src={{ asset('images/post/' . $post->image) }} alt="" class=" img-fluid">
                            </div>

                            <h2 class="title">
                                {{ $post->title }}
                            </h2>

                            <div class="meta-top">
                                <ul>
                                    <li class="d-flex align-items-center"><i class="bi bi-person"></i> <a
                                            href="{{route('profile' ,  $post->user->id)}}">
                                            {{ $post->user->name }}</a></li>
                                    <li class="d-flex align-items-center"><i class="bi bi-clock"></i> <a
                                            href="blog-details.html"><time datetime="2020-01-01">
                                                {{ $post->created_at }}</time></a></li>
                                    <li class="d-flex align-items-center"><i class="bi bi-chat-dots"></i> <a
                                            href="blog-details.html">
                                            {{ count($post->comments) }}</a></li>
                                </ul>
                            </div><!-- End meta top -->

                            <div class="content">

                                <blockquote>
                                    <p>

                                        {{ $post->description }}
                                    </p>
                                </blockquote>



                            </div><!-- End post content -->



                        </article><!-- End blog post -->


                    </div>
                    <div class="col-lg-6">
                        <div class="comments">

                            <h4 class="comments-count">{{ count($post->comments) }} Comments</h4>

                            @if (count($post->comments) > 0)
                                @foreach ($post->comments as $comment)
                                    <div id="comment" class="comment">
                                        <div class="d-flex">
                                            <div>
                                                <p><a href="{{route('profile' , $comment->user->id )}}">{{ $comment->user->name }}</a> </p>
                                                <time datetime="2020-01-01"
                                                    class="text-warning">{{ $comment->created_at }}</time>
                                                <p>
                                                    {{ $comment->content }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif


                            <!-- End comment #1 -->



                            <div class="reply-form">

                                <h4>Leave a Reply</h4>
                                <form action="{{ route('comment.store') }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="col form-group">
                                            <textarea name="comment" class="form-control" placeholder="Your Comment*"></textarea>
                                            <input class="d-none" type="text" name="id"
                                                value="{{ $post->id }}">


                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Post Comment</button>

                                </form>

                            </div>

                        </div><!-- End blog comments -->
                    </div>

                </div>

            </div>
        </section><!-- End Blog Details Section -->

    </main><!-- End #main -->


@endsection
@section('title', 'blog-details')
