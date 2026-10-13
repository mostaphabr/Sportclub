@extends('SportClub.layouts.parts')

@section('content')
    <div id="main">
        <!-- ======= Contact Section ======= -->
        <section id="contact" class="contact">
            <div class="container" data-aos="fade-up">

                <div class="section-header">
                    <h2>{{ $pitche->name }}</h2>
                    <p class="mb-0">{{ $pitche->formatLabel() }} · @include('SportClub.partials.stars', ['rating' => $pitche->averageRating(), 'count' => $pitche->reviewsCount()])</p>

                </div>

                <div class="row gx-lg-0 gy-4">

                    <div class="col-lg-4">

                        <div class="info-container d-flex flex-column align-items-center justify-content-center">
                            <div class="info-item d-flex">
                                <i class="bi bi-geo-alt flex-shrink-0"></i>
                                <div>
                                    <h4>Location:</h4>
                                    <p>{{$pitche->localisation}} </p>
                                </div>
                            </div><!-- End Info Item -->

                            <div class="info-item d-flex">
                                <i class="bi bi-envelope flex-shrink-0"></i>
                                <div>
                                    <h4>Email:</h4>
                                    <p>{{ optional($pitche->user)->email }}</p>
                                </div>
                            </div><!-- End Info Item -->

                            <div class="info-item d-flex">
                                <i class="bi bi-phone flex-shrink-0"></i>
                                <div>
                                    <h4>Call:</h4>
                                    <p>{{ optional(optional($pitche->user)->profile)->phone_number ?? '—' }}</p>
                                </div>
                            </div><!-- End Info Item -->

                            <div class="info-item d-flex">
                                <i class="bi bi-clock flex-shrink-0"></i>
                                <div>
                                    <h4>Open Hours:</h4>
                                    <p>{{$pitche->openhours}}</p>
                                </div>
                            </div><!-- End Info Item -->

                            <div class="info-item d-flex">
                                <i class="bi bi-cash-coin flex-shrink-0"></i>
                                <div>
                                    <h4>Price:</h4>
                                    <p>{{ $pitche->priceLabel() ?? 'Ask the terrain owner' }}</p>
                                </div>
                            </div><!-- End Info Item -->
                        </div>

                    </div>

                    <div class="col-lg-8">
                        <form action={{ route('reservation.store') }} id="reservation-form" method="post" >
                            @csrf

                            <div class="input-group mb-5 ">
                                <label class="input-group-text text-info  fs-4 " for="inputGroupSelect01">terrain</label>

                                @if($pitche)
                               
                                <select class="form-select fs-4" id="inputGroupSelect01" name='pitche_id'>
                                    <option value="{{$pitche['id']}}"  selected>{{$pitche->name}}</option>
                                </select>
                                @else
                                    go back to terrain 
                                @endif

                            </div>
                            <div class="alert alert-info d-flex align-items-center gap-2 mb-4">
                                <i class="bi bi-calendar-event fs-4"></i>
                                <div>Bookings are for <strong>tomorrow, {{ $playDay->format('l d/m/Y') }}</strong>. Each slot is one hour.</div>
                            </div>

                            <div class="input-group mb-3 ">
                                <label class="input-group-text text-success fs-3 " for="inputGroupSelect02">disponible hours</label>
                                <select class="form-select fs-3" id="inputGroupSelect02" name='hour' required>
                                    <option value="" selected disabled>Choose an hour</option>

                                        @foreach ($slots as $item)
                                            @php $value = $item['debut'].'-'.$item['fin']; @endphp
                                            <option value="{{ $value }}" @disabled($item['taken']) @selected(old('hour') === $value && ! $item['taken'])>
                                                {{ sprintf('%02d:00', $item['debut']) }} - {{ sprintf('%02d:00', $item['fin']) }}{{ $item['taken'] ? '  (booked)' : '' }}
                                            </option>
                                        @endforeach
                                    
                                </select>
                               
                            </div>
                            <p class="text-muted mb-1">{{ count($disponible) }} of {{ count($slots) }} hours still free for tomorrow.</p>

                            @if ((int) $pitche->capacity > 1)
                                <div class="border rounded p-3 my-3 bg-light">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="needPlayers"
                                            data-bs-toggle="collapse" data-bs-target="#teamFields" @checked(old('players_needed') > 0)
                                            onchange="var f = document.getElementById('players_needed'); if (!this.checked) { f.value = 0; } else if (f.value == 0) { f.value = 1; }">
                                        <label class="form-check-label fw-semibold" for="needPlayers"><i class="bi bi-people"></i> Looking for teammates?</label>
                                    </div>
                                    <div class="collapse {{ old('players_needed') > 0 ? 'show' : '' }}" id="teamFields">
                                        <div class="row g-2 mt-1">
                                            <div class="col-sm-4">
                                                <label for="players_needed" class="form-label small text-muted mb-1">Players needed</label>
                                                <input type="number" min="0" max="{{ max(0, (int) $pitche->capacity - 1) }}" class="form-control"
                                                    id="players_needed" name="players_needed" value="{{ old('players_needed', 0) }}">
                                            </div>
                                            <div class="col-sm-8">
                                                <label for="match_note" class="form-label small text-muted mb-1">Note for players (optional)</label>
                                                <input type="text" maxlength="255" class="form-control" id="match_note" name="match_note"
                                                    value="{{ old('match_note') }}" placeholder="e.g. friendly game, intermediate level">
                                            </div>
                                        </div>
                                        <div class="form-text">Your match will appear in "Find a match"; you accept or decline each player.</div>
                                    </div>
                                </div>
                                @error('players_needed')
                                    <p class="alert alert-warning">{{ $message }}</p>
                                @enderror
                            @endif
                            @if ($dailyLimit !== null && $hoursBooked < $dailyLimit)
                                <p class="text-muted mb-4">You have booked <strong>{{ $hoursBooked }} of {{ $dailyLimit }}</strong> hours allowed per day for tomorrow.</p>
                            @else
                                <div class="mb-4"></div>
                            @endif
                           @error('hour')
                           <p class="alert alert-warning">  {{$message}}</p>
                            @enderror
                           @error('pitche_id')
                           <p class="alert alert-warning">  {{$message}}</p>
                            @enderror
                          
                            @if (auth()->user()->cannot('create', App\Models\Reservation::class))
                                <p class="alert alert-secondary text-center">Provider accounts manage terrains and can't book them.</p>
                            @elseif ($dailyLimit !== null && $hoursBooked >= $dailyLimit)
                                <p class="alert alert-secondary text-center">You already booked your {{ $dailyLimit }} hours for tomorrow. See <a href="{{ route('MyReservations') }}">My Reservations</a>.</p>
                            @elseif (count($disponible) > 0)
                                <div class="text-center"><button type="submit" class="btn btn-info btn-lg ">reserve</button></div>
                            @else
                                <p class="alert alert-secondary text-center">This terrain is fully booked for tomorrow. Try another terrain.</p>
                            @endif
                        </form>
                    </div><!-- End Contact Form -->

                </div>

            </div>

        </section><!-- End Contact Section -->

        {{-- ======= Ratings & reviews ======= --}}
        <section id="reviews" class="pt-0">
            <div class="container">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <h3 class="m-0">Reviews</h3>
                    @include('SportClub.partials.stars', ['rating' => $pitche->averageRating(), 'count' => $pitche->reviewsCount()])
                </div>

                @if (session('message') && str_contains(session('message'), 'review'))
                    <div class="alert alert-success">{{ session('message') }}</div>
                @endif
                @error('rating')
                    <div class="alert alert-warning">{{ $message }}</div>
                @enderror

                @if ($canReview)
                    <form action="{{ route('reviews.store', $pitche) }}" method="POST" class="card card-body shadow-sm mb-4">
                        @csrf
                        <div class="fw-semibold mb-2">{{ $myReview ? 'Update your review' : 'You played here: rate this terrain' }}</div>
                        <div class="d-flex flex-wrap gap-3 mb-2" role="radiogroup" aria-label="Rating">
                            @for ($i = 5; $i >= 1; $i--)
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="rating" id="rating{{ $i }}" value="{{ $i }}"
                                        @checked((int) old('rating', optional($myReview)->rating) === $i) required>
                                    <label class="form-check-label" for="rating{{ $i }}">
                                        @for ($j = 1; $j <= $i; $j++)<i class="bi bi-star-fill text-warning"></i>@endfor
                                    </label>
                                </div>
                            @endfor
                        </div>
                        <textarea name="comment" class="form-control mb-2" rows="2" maxlength="1000" placeholder="What was good or bad? (optional)">{{ old('comment', optional($myReview)->comment) }}</textarea>
                        <div><button type="submit" class="btn btn-info">{{ $myReview ? 'Update review' : 'Send review' }}</button></div>
                    </form>
                @elseif (! auth()->user()->isProvider())
                    <p class="text-muted small">You can rate this terrain after playing here.</p>
                @endif

                @forelse ($reviews as $review)
                    <div class="border-bottom py-3 d-flex gap-3">
                        <img src="{{ $review->user->avatarUrl() }}" alt="" class="rounded-circle flex-shrink-0" style="width: 44px; height: 44px; object-fit: cover">
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <div>
                                    <a href="{{ route('profile', $review->user) }}" class="fw-semibold">{{ $review->user->name }}</a>
                                    <span class="ms-2">@include('SportClub.partials.stars', ['rating' => (float) $review->rating])</span>
                                </div>
                                <div class="small text-muted">{{ $review->updated_at?->format('d/m/Y') }}</div>
                            </div>
                            @if ($review->comment)
                                <p class="mb-1 mt-1">{{ $review->comment }}</p>
                            @endif
                            @can('delete', $review)
                                <form action="{{ route('reviews.destroy', $review) }}" method="POST" onsubmit="return confirm('Delete this review?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0">delete</button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @empty
                    <p class="text-muted">No reviews yet.</p>
                @endforelse
            </div>
        </section>
    </div>
<script>

if (!window.location.hash) {
    var scrollDiv = document.getElementById("reservation-form").offsetTop;
    window.scrollTo({ top: scrollDiv, behavior:'smooth' });
}
</script>
    
@endsection
@section('title', 'reservation')
