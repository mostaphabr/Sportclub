@extends('SportClub.layouts.parts')

@section('content')
<main id="main">
    <div class="breadcrumbs">
        <div class="page-header d-flex align-items-center">
            <div class="container position-relative">
                <div class="row d-flex justify-content-center">
                    <div class="col-lg-8 text-center">
                        <h2 class="text-warning">Find a match</h2>
                        <p>Players who booked a terrain for tomorrow and need teammates. Ask to join, the organizer accepts you.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="container py-5">

        @if (session('message'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        {{-- My requests --}}
        @if ($myRequests->count() > 0)
            <h3 class="h5 mb-3">My requests</h3>
            <div class="table-responsive mb-5">
                <table class="table align-middle">
                    <tbody>
                        @foreach ($myRequests as $joinRequest)
                            @php $match = $joinRequest->reservation; @endphp
                            <tr>
                                <td class="fw-semibold">{{ $match->pitche->name }}</td>
                                <td class="text-nowrap">{{ $match->date->format('D d/m') }} · {{ $match->timeRange() }}</td>
                                <td>organized by {{ $match->user->name }}</td>
                                <td>
                                    @if ($joinRequest->status === 'accepted')
                                        <span class="badge bg-success">accepted</span>
                                        <span class="small text-muted ms-1">{{ optional($match->user->profile)->phone_number }}</span>
                                    @elseif ($joinRequest->status === 'declined')
                                        <span class="badge bg-secondary">declined</span>
                                    @else
                                        <span class="badge bg-warning text-dark">waiting for answer</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($joinRequest->status !== 'declined')
                                        <form action="{{ route('match-requests.destroy', $joinRequest) }}" method="POST"
                                            onsubmit="return confirm('{{ $joinRequest->status === 'accepted' ? 'Leave this match?' : 'Cancel your request?' }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ $joinRequest->status === 'accepted' ? 'Leave' : 'Cancel' }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Open matches --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h3 class="h5 m-0">Open matches</h3>
            <a href="{{ route('reservation.index') }}" class="btn btn-sm btn-outline-secondary">Book a terrain and look for players</a>
        </div>

        <div class="row g-4">
            @forelse ($matches as $match)
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 shadow-sm">
                        <img src="{{ $match->pitche->imageUrl() }}" class="card-img-top" alt="{{ $match->pitche->name }}" style="height: 150px; object-fit: cover">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <h4 class="h5 card-title mb-1">{{ $match->pitche->name }}</h4>
                                <span class="badge bg-info text-dark text-nowrap">{{ $match->spotsLeft() }} {{ $match->spotsLeft() > 1 ? 'spots' : 'spot' }} left</span>
                            </div>
                            <div class="small text-muted mb-2">
                                <i class="bi bi-calendar-event"></i> {{ $match->date->format('l d/m') }} · {{ $match->timeRange() }}<br>
                                <i class="bi bi-geo-alt"></i> {{ $match->pitche->localisation }} · {{ $match->pitche->formatLabel() }}
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <img src="{{ $match->user->avatarUrl() }}" alt="" class="rounded-circle" style="width: 28px; height: 28px; object-fit: cover">
                                <span class="small">organized by <a href="{{ route('profile', $match->user) }}">{{ $match->user->name }}</a></span>
                            </div>
                            @if ($match->match_note)
                                <p class="small fst-italic mb-2">"{{ $match->match_note }}"</p>
                            @endif

                            <div class="mt-auto">
                                @if (($requestStatus[$match->id] ?? null) === 'accepted')
                                    <span class="btn btn-sm btn-success disabled w-100">You're in the team</span>
                                @elseif (isset($requestStatus[$match->id]))
                                    <span class="btn btn-sm btn-outline-secondary disabled w-100">{{ $requestStatus[$match->id] === 'declined' ? 'Request declined' : 'Request sent' }}</span>
                                @elseif ($canJoin)
                                    <form action="{{ route('matches.join', $match) }}" method="POST">
                                        @csrf
                                        <input type="text" name="message" maxlength="255" class="form-control form-control-sm mb-2" placeholder="Message (optional), e.g. I play defender">
                                        <button type="submit" class="btn btn-sm btn-info w-100">Ask to join</button>
                                    </form>
                                @else
                                    <span class="small text-muted">Provider accounts can't join matches.</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <p class="alert alert-secondary mb-0">No open match right now. Book a terrain and switch on "Looking for teammates?" to create one.</p>
                </div>
            @endforelse
        </div>

        <div class="d-flex justify-content-center mt-4">{{ $matches->links() }}</div>
    </section>
</main>
@endsection

@section('title', 'find a match')
