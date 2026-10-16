@extends('SportClub.layouts.parts')


@section('content')
<section id="hero" class="hero hero-video">
    {{-- Background video (muted + playsinline so phones autoplay it) --}}
    <video class="hero-video-bg" autoplay muted loop playsinline preload="metadata"
           poster="{{ asset('SportClub/assets/img/hero-poster.jpg') }}" aria-hidden="true">
        <source src="{{ asset('SportClub/assets/img/hero-video.mp4') }}" type="video/mp4">
    </video>
    <div class="hero-video-overlay"></div>

    <div class="container position-relative">
        <div class="row gy-5" data-aos="fade-in">
            <div class="col-lg-6 order-2 order-lg-1 d-flex flex-column justify-content-center text-center text-lg-start">
                <h2>Welcome to <span>SportClub</span></h2>
                <p style="color:whitesmoke;" >Welcome to our platform, where you can easily search for terrain in your area, reserve your desired football field time, find your team members, and join the platform community blog! Start planning your next game hassle-free.






                </p>
                <div class="d-flex justify-content-center justify-content-lg-start">
                    @auth
                    @if (auth()->user()->role !== 'provider' && auth()->user()->role !== 'admin')
                    <a href="{{ url('/reservation') }}" class="btn-get-started text-capitalize">Reservation</a>
                    @endif
                    @else
                    <a href="{{ route('register') }}" class="btn-get-started text-capitalize">Get Started</a>
                    @endauth


                </div>
            </div>
            <div class="col-lg-6 order-1 order-lg-2">
                <img style="border-radius: 6px;" src={{ url('SportClub/assets/img/sportclub-landing-page-photo.jpg') }}
                    class="img-fluid" alt="" data-aos="zoom-out" data-aos-delay="100">
            </div>
        </div>
    </div>

    <div class="icon-boxes position-relative">
        <div class="container position-relative">
            <div class="row gy-4 mt-5">

                <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="icon-box">
                        <div class="icon"><i class="bi bi-easel"></i></div>
                        <h4 class="title"><a href="{{ route('reservation.index') }}" class="stretched-link">Real-Time Availability</a></h4>
                    </div>
                </div><!--End Icon Box -->

                <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="icon-box">
                        <div class="icon"><i class="bi bi-people"></i></div>
                        <h4 class="title"><a href="{{ route('matches.index') }}" class="stretched-link">Find Teammates</a></h4>
                    </div>
                </div><!--End Icon Box -->

                <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="icon-box">
                        <div class="icon"><i class="bi bi-geo-alt"></i></div>
                        <h4 class="title"><a href="{{ route('reservation.index', ['q' => 'Khemisset']) }}" class="stretched-link">Khemisset , morocco</a></h4>
                    </div>
                </div><!--End Icon Box -->

                <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="500">
                    <div class="icon-box">
                        <div class="icon"><i class="bi bi-star"></i></div>
                        <h4 class="title"><a href="{{ route('reservation.index', ['sort' => 'rating']) }}" class="stretched-link">Rated by Players</a></h4>
                    </div>
                </div><!--End Icon Box -->

            </div>
        </div>
    </div>

    </div>
</section>
<!-- End Hero Section -->

<main id="main">
    <!-- ======= Recent Blog Posts Section ======= -->
    @if ($posts)

    <section id="recent-posts" class="recent-posts sections-bg">
        <div class="container" data-aos="fade-up">

            <div class="section-header">
                <h2>Recent Blog Posts</h2>

            </div>

            <div class="row gy-4 posts-list">

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

                    </article>
                </div><!-- End post list item -->
                @endforeach




                <!-- End blog pagination -->

            </div><!-- End recent posts list -->

        </div>
    </section><!-- End Recent Blog Posts Section -->
    @endif

   



</main><!-- End #main -->

@endsection
@section('title', 'home')
<!-- ======= Hero Section ======= -->