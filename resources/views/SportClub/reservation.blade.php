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
                                    <p>{{$pitche->user->email}}</p>
                                </div>
                            </div><!-- End Info Item -->

                            <div class="info-item d-flex">
                                <i class="bi bi-phone flex-shrink-0"></i>
                                <div>
                                    <h4>Call:</h4>
                                    <p>{{$pitche->user->profile->phone_number}}</p>
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
                            <div class="input-group mb-5 ">
                                <label class="input-group-text text-success fs-3 " for="inputGroupSelect02">disponible hours</label>
                                <select class="form-select fs-3" id="inputGroupSelect02" name='hour'>
                                    <option selected disabled>Choose an hour</option>

                                        @foreach ($disponible as $item)
                                            <option value="{{ $item['debut'] }}-{{ $item['fin'] }}">
                                                {{ $item['debut'] }} - {{ $item['fin'] }}
                                            </option>
                                        @endforeach
                                    
                                </select>
                               
                            </div>
                           @error('hour')
                           <p class="alert alert-warning">  {{$message}}</p>
                            @enderror
                          
                            <div class="text-center"><button type="submit" class="btn btn-info btn-lg ">reserve</button></div>
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
