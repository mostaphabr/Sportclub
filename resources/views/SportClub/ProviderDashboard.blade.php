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
                        <img src="{{ $pitche->imageUrl() }}" class="d-block w-100" style="max-height: 420px; object-fit: cover"
                            alt="terrain-image">
                        <div class="carousel-caption">

                        </div>

                        <div class="carousel-caption d-none d-md-block text-light">
                            <h3>{{ $pitche->name }}</h3>
                            <p class="text-warning fw-bold">upcoming reservations : <span>{{ $pitche->upcoming_reservations_count }}</span> · total : <span>{{ $pitche->reservations_count }}</span></p>
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

        <h3 class="h4 mt-5 mb-3">My terrains</h3>
        @if ($pitches->isEmpty())
            <p class="text-muted">No terrain is linked to your account yet. Ask an administrator to add yours.</p>
        @else
            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <div class="card shadow-sm h-100"><div class="card-body">
                        <div class="small text-muted text-uppercase">Revenue {{ now()->format('F') }}</div>
                        <div class="fs-3 fw-bold">{{ number_format((int) $pitches->sum('revenue_month'), 0, ',', ' ') }} {{ config('sportclub.currency') }}</div>
                    </div></div>
                </div>
                <div class="col-sm-4">
                    <div class="card shadow-sm h-100"><div class="card-body">
                        <div class="small text-muted text-uppercase">Bookings {{ now()->format('F') }}</div>
                        <div class="fs-3 fw-bold">{{ $pitches->sum('bookings_month') }}</div>
                    </div></div>
                </div>
                <div class="col-sm-4">
                    <div class="card shadow-sm h-100"><div class="card-body">
                        <div class="small text-muted text-uppercase">Upcoming bookings</div>
                        <div class="fs-3 fw-bold">{{ $pitches->sum('upcoming_reservations_count') }}</div>
                    </div></div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Terrain</th>
                            <th>Price</th>
                            <th class="text-center">Upcoming</th>
                            <th class="text-center">This month</th>
                            <th class="text-end">Revenue (month)</th>
                            <th class="text-end">Revenue (total)</th>
                            <th>Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pitches as $pitche)
                            <tr>
                                <td><a href="{{ route('MyTerrainReservations', ['id' => $pitche->id]) }}">{{ $pitche->name }}</a></td>
                                <td class="text-nowrap">{{ $pitche->priceLabel() ?? '—' }}</td>
                                <td class="text-center">{{ $pitche->upcoming_reservations_count }}</td>
                                <td class="text-center">{{ $pitche->bookings_month }}</td>
                                <td class="text-end">{{ number_format((int) $pitche->revenue_month, 0, ',', ' ') }} {{ config('sportclub.currency') }}</td>
                                <td class="text-end">{{ number_format((int) $pitche->revenue_total, 0, ',', ' ') }} {{ config('sportclub.currency') }}</td>
                                <td>@include('SportClub.partials.stars', ['rating' => $pitche->averageRating(), 'count' => $pitche->reviewsCount()])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif



    </section>


</main><!-- End #main -->


@endsection
@section('title', 'pitches')