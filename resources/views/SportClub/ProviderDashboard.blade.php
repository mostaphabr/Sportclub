@extends('SportClub.layouts.parts')
@section('content')
<main id="main">
    <style>

    </style>



    <section class="container p-5 ">

        <div id="carouselExampleDark" class="carousel carousel-dark slide">
            <div class="carousel-indicators">
                @foreach ($pitches as $index => $pitche)
                <button type="button" data-bs-target="#carouselExampleDark" data-bs-slide-to="{{ $index }}"
                    class="{{ $index == 0 ? 'active' : '' }}" aria-current="{{ $index == 0 ? 'true' : '' }}"
                    aria-label="Slide {{ $index + 1 }}"></button>
                @endforeach
            </div>
            <div class="carousel-inner">
                @foreach ($pitches as $index => $pitche)
                <div class="carousel-item {{ $index == 0 ? 'active' : '' }}" data-bs-interval="10000">
                    <a href="{{route('MyTerrainReservations', ['id' => $pitche->id]) }}">
                        <img src="{{ asset('images/pitches/' . $pitche->image) }}" class="d-block w-100 w-100  "
                            alt="terrain-image">
                        <div class="carousel-caption">

                        </div>

                        <div class="carousel-caption d-none d-md-block text-light">
                            <h3>{{ $pitche->name }}</h3>
                            <p class="text-warning fw-bold">reservation count : <span>{{ count($pitche->reservations) }}</span></p>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>

        {{-- --------------------------------------------------- --}}



    </section>


</main><!-- End #main -->


@endsection
@section('title', 'pitches')