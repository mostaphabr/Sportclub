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
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
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
                                        <div class="col-auto">
                                            {{ optional($reservation->pitche)->name }}
                                            @if (! $showPlayer && $reservation->relationLoaded('matchRequests') && $reservation->matchRequests->where('status', 'pending')->count())
                                                <span class="badge bg-warning text-dark ms-2">{{ $reservation->matchRequests->where('status', 'pending')->count() }} join request(s)</span>
                                            @endif
                                        </div>
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
                                    @if ($reservation->price !== null)
                                        <tr>
                                            <th class="pe-4">Price</th>
                                            <td>{{ $reservation->price }} {{ config('sportclub.currency') }}</td>
                                        </tr>
                                    @endif
                                    @if ($reservation->players_needed > 0)
                                        <tr>
                                            <th class="pe-4">Teammates</th>
                                            <td>{{ $reservation->acceptedCount() }} of {{ $reservation->players_needed }} found</td>
                                        </tr>
                                    @endif
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

                                {{-- Find teammates: only the player who booked manages it --}}
                                @if (! $showPlayer && auth()->user()->can('manageTeam', $reservation))
                                    @php
                                        $pending = $reservation->matchRequests->where('status', 'pending');
                                        $accepted = $reservation->matchRequests->where('status', 'accepted');
                                    @endphp
                                    <div class="border rounded bg-white p-3 mb-3">
                                        <h5 class="h6"><i class="bi bi-people"></i> Teammates</h5>
                                        <form action="{{ route('reservation.team', $reservation) }}" method="POST" class="row g-2 align-items-end mb-2">
                                            @csrf
                                            @method('PATCH')
                                            <div class="col-sm-3">
                                                <label class="form-label small text-muted mb-1" for="players_needed_{{ $reservation->id }}">Players needed</label>
                                                <input type="number" min="0" max="{{ max(0, (int) optional($reservation->pitche)->capacity - 1) }}" class="form-control form-control-sm"
                                                    id="players_needed_{{ $reservation->id }}" name="players_needed" value="{{ $reservation->players_needed }}">
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="form-label small text-muted mb-1" for="match_note_{{ $reservation->id }}">Note</label>
                                                <input type="text" maxlength="255" class="form-control form-control-sm" id="match_note_{{ $reservation->id }}"
                                                    name="match_note" value="{{ $reservation->match_note }}" placeholder="e.g. friendly game">
                                            </div>
                                            <div class="col-sm-3 d-grid">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                            </div>
                                        </form>
                                        <div class="form-text mb-2">Set more than 0 to list your match in <a href="{{ route('matches.index') }}">Find a match</a>.</div>

                                        @foreach ($pending as $request)
                                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border-top py-2">
                                                <div>
                                                    <a href="{{ route('profile', $request->user) }}" class="fw-semibold">{{ $request->user->name }}</a>
                                                    <span class="badge bg-warning text-dark">wants to join</span>
                                                    @if ($request->message)<div class="small text-muted">"{{ $request->message }}"</div>@endif
                                                </div>
                                                <div class="d-flex gap-1">
                                                    <form action="{{ route('match-requests.accept', $request) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-success" @disabled($reservation->spotsLeft() < 1)>Accept</button>
                                                    </form>
                                                    <form action="{{ route('match-requests.decline', $request) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Decline</button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endforeach

                                        @foreach ($accepted as $request)
                                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border-top py-2">
                                                <div>
                                                    <a href="{{ route('profile', $request->user) }}" class="fw-semibold">{{ $request->user->name }}</a>
                                                    <span class="badge bg-success">in the team</span>
                                                    <span class="small text-muted ms-1">{{ optional($request->user->profile)->phone_number }}</span>
                                                </div>
                                                <form action="{{ route('match-requests.destroy', $request) }}" method="POST" onsubmit="return confirm('Remove this player from your match?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-link text-danger">remove</button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

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

        {{-- ---------------------------------------------------------- matches I joined --}}
        @if ($joining->count() > 0)
            <h4 class="mb-3"><i class="bi bi-people"></i> Matches I'm joining</h4>
            <div class="table-responsive mb-5">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Terrain</th>
                            <th>Organizer</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($joining as $request)
                            <tr>
                                <td>{{ $request->reservation->date->format('D d/m') }}</td>
                                <td>{{ $request->reservation->timeRange() }}</td>
                                <td>{{ optional($request->reservation->pitche)->name }}</td>
                                <td>
                                    {{ $request->reservation->user->name }}
                                    <div class="small text-muted">{{ optional($request->reservation->user->profile)->phone_number }}</div>
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('match-requests.destroy', $request) }}" method="POST" onsubmit="return confirm('Leave this match?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Leave</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
                            @else
                                <th></th>
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
                                @elseif ($reservation->pitche)
                                    <td class="text-end"><a href="{{ route('reservation.show', $reservation->pitche->name) }}#reviews" class="btn btn-sm btn-outline-warning"><i class="bi bi-star"></i> Rate</a></td>
                                @else
                                    <td></td>
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
