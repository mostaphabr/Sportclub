@extends('SportClub.layouts.parts')
@section('content')
<main id="main">

    <section class="container p-lg-5 py-5">

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <h2 class="m-0">{{ $title }}</h2>
            <h3 class="btn btn-warning fw-bold m-0">upcoming : {{ $upcoming->count() }}</h3>
        </div>

        @if (session('message'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- ---------------------------------------------------------- upcoming --}}
        @if ($upcoming->count() > 0)
            <div class="accordion accordion-flush mb-5" id="upcomingReservations">
                @foreach ($upcoming as $index => $reservation)
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="upcoming-heading-{{ $index }}">
                            <button class="accordion-button bg-danger collapsed text-white" type="button"
                                data-bs-toggle="collapse" data-bs-target="#upcoming-collapse-{{ $index }}"
                                aria-expanded="false" aria-controls="upcoming-collapse-{{ $index }}">
                                <div class="container-fluid">
                                    <div class="row justify-content-between w-100">
                                        <div class="col-auto">{{ optional($reservation->pitche)->name }}</div>
                                        <div class="col-auto">{{ $reservation->date?->format('D d/m') }} · {{ $reservation->timeRange() }}</div>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="upcoming-collapse-{{ $index }}" class="accordion-collapse collapse"
                            aria-labelledby="upcoming-heading-{{ $index }}" data-bs-parent="#upcomingReservations">
                            <div class="accordion-body" style="background:whitesmoke;">
                                <table class="table table-borderless mb-3 w-auto" style="--bs-table-bg: transparent">
                                    <tr>
                                        <th class="pe-4">Terrain</th>
                                        <td>{{ optional($reservation->pitche)->name }} <span class="text-muted">— {{ optional($reservation->pitche)->localisation }}</span></td>
                                    </tr>
                                    <tr>
                                        <th class="pe-4">Day of play</th>
                                        <td class="text-warning fw-bold">{{ $reservation->date?->format('l d/m/Y') }}</td>
                                    </tr>
                                    <tr>
                                        <th class="pe-4">Time</th>
                                        <td class="text-warning fw-bold">{{ $reservation->timeRange() }}</td>
                                    </tr>
                                    <tr>
                                        <th class="pe-4">Reserved at</th>
                                        <td>{{ $reservation->created_at?->format('d/m/Y H:i') }}</td>
                                    </tr>
                                    @if ($showPlayer)
                                        <tr>
                                            <th class="pe-4">Player</th>
                                            <td>{{ optional($reservation->user)->name }}</td>
                                        </tr>
                                        <tr>
                                            <th class="pe-4">Phone</th>
                                            <td>{{ optional(optional($reservation->user)->profile)->phone_number ?? 'Not available' }}</td>
                                        </tr>
                                    @endif
                                </table>

                                @can('delete', $reservation)
                                    <form action="{{ route('reservation.destroy', $reservation) }}" method="POST"
                                        onsubmit="return confirm('Cancel this reservation?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">Cancel reservation</button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="mb-5">
                <h1 class="text-success h3">no upcoming reservations</h1>
                @unless ($showPlayer)
                    <a href="{{ route('reservation.index') }}" class="btn btn-info mt-2">Book a terrain</a>
                @endunless
            </div>
        @endif

        {{-- ---------------------------------------------------------- past --}}
        @if ($past->count() > 0)
            <h4 class="text-muted">Past reservations</h4>
            <div class="table-responsive">
                <table class="table table-sm align-middle text-muted">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Terrain</th>
                            @if ($showPlayer)
                                <th>Player</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($past as $reservation)
                            <tr>
                                <td>{{ $reservation->date?->format('d/m/Y') }}</td>
                                <td>{{ $reservation->timeRange() }}</td>
                                <td>{{ optional($reservation->pitche)->name }}</td>
                                @if ($showPlayer)
                                    <td>{{ optional($reservation->user)->name }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

</main><!-- End #main -->

@endsection

@section('title', 'reservations')
