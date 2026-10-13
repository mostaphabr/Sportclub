@extends('SportClub.layouts.parts')

@section('content')
    <div id="main">
        <!-- ======= Contact Section ======= -->
        <section id="contact" class="contact">
            <div class="container" data-aos="fade-up">

                <div class="section-header">
                    <h2>Reservations</h2>

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
    </div>
<script>

var scrollDiv = document.getElementById("reservation-form").offsetTop;
window.scrollTo({ top: scrollDiv, behavior:'smooth' });
</script>
    
@endsection
@section('title', 'reservation')
