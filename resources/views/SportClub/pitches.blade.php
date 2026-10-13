@extends('SportClub.layouts.parts')
@section('content')
<main id="main">

    <!-- ======= Breadcrumbs ======= -->
    <div class="breadcrumbs">
        <div class="page-header d-flex align-items-center" style="background-image: url('');">
            <div class="container position-relative">
                <div class="row d-flex justify-content-center">
                    <div class="col-lg-6 text-center">
                        <h2 class="text-info">terrain</h2>
                        <p class="text-warning">best football pitches in country, here you can play , like a
                            professionel</p>
                    </div>
                </div>
            </div>
        </div>
        <nav>
            <div class="container">
                <ol>
                    <li><a href="{{ route('home') }}">Home</a></li>
                    <li>terrain</li>
                </ol>
            </div>
        </nav>
        {{-- ALERTS DE NOTIFIATIONS --}}
        @if (session('message'))

        <div class="alert alert-dark">
         you can  see your reservations details here, 
        <a href="{{ url('MyReservations') }}">click </a>
    </div>


        <!-- Modal -->
    <div class="modal fade show" id="exampleModal" tabindex="-1" 
         aria-labelledby="exampleModalLabel" aria-hidden="false" 
         style="display: block;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                <i class="bi bi-bell-fill"></i>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{session('message')}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="{{ url(path: 'MyReservations') }}"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">details</button>
                    </a>
                    
                </div>
            </div>
        </div>
    </div>

    <script>
        // Automatically show the modal if the session message is present
        window.addEventListener('load', () => {
            const myModal = new bootstrap.Modal(document.getElementById('exampleModal'));
            myModal.show();
        });
    </script>
       
        @endif

        @if (session('error'))

        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
        @endif
        {{--END ALERTS DE NOTIFIATIONS --}}

    </div><!-- End Breadcrumbs -->

    <!-- ======= pitches Section ======= -->
    <section id="blog" class="blog">
        <div class="container" data-aos="fade-up">

            <div class="row gy-4 posts-list">


                @foreach ($terrain as $pitche)
                @php
                $capacity = $pitche->capacity / 2;
                @endphp

                <div class="col-lg-4 col-md-6">
                    <a href={{ route('reservation.show', $pitche->name) }}>


                        <div>
                            <article>
                                <div   class="post-img bg-success  d-flex   justify-content-center">
                                    <img  style="height: 200px;" src="{{ $pitche->imageUrl() }}" alt=""
                                        class="img-fluid w-100">
                                </div>
                                <p class="post-category">{{ $capacity . '  vs  ' . $capacity }}</p>
                                <h2 class="title">
                                    <a class="text-success">{{ $pitche->name }}</a>
                                </h2>
                                <span class="text-warning">rating : 9/10</span>
                                {{-- For Admins  --}}


                                @auth
                                @if ($user_role == 'admin')
                                <div class="mt-2">

                                    <form action={{ route('AdminDashbord.destroy', $pitche->id) }} method='POST'>
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">delete</button>
                                        <a class="btn btn-outline-primary"
                                            href={{ route('AdminDashbord.edit', $pitche->id) }}>edit</a>
                                    </form>


                                </div>
                                @endif

                                @endauth


                                {{-- For Admins  --}}


                            </article>
                        </div>
                    </a>

                </div>
                @endforeach





                <div class="row mt-3">
                    <div class="col-lg-12 d-flex justify-content-center">
                        {{ $terrain->links() }}
                    </div>
                </div>

                <!-- End pitches pagination -->

            </div>
    </section><!-- End pitches Section -->

</main><!-- End #main -->


@endsection
@section('title', 'pitches')