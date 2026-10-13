@extends('SportClub.layouts.parts')
@section('content')
<main id="main">



    <section class="container p-lg-5 ">
        @if (count($reservations) > 0)

        <div>
            <h3 class="btn btn-warning fw-bold mb-3 ">reservations count : {{$reservations->count()}}</h3>

        </div>




        <div class="accordion accordion-flush" id="accordionFlushExample">
            @foreach ($reservations as $index => $reservation)
            <div class="accordion-item">
                <h2 class="accordion-header" id="flush-heading-{{ $index }}">
                    <button
                        class="accordion-button bg-danger collapsed text-white"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#flush-collapse-{{ $index }}"
                        aria-expanded="false"
                        aria-controls="flush-collapse-{{ $index }}">

                        <div class="container-fluid">
                            <div class="row justify-content-between w-100">
                                <div class="col-auto">{{ $reservation->pitche->name }}</div>
                                <div class="col-auto"> from :{{ $reservation->debut }}:00 to {{ $reservation->fin }}:00</div>
                            </div>
                        </div>
                    </button>

                </h2>
                <div
                    id="flush-collapse-{{ $index }}"
                    class="accordion-collapse collapse"
                    aria-labelledby="flush-heading-{{ $index }}"
                    data-bs-parent="#accordionFlushExample">
                    <div class="accordion-body " style="background:whitesmoke;">
                        <table>
                            <tr>
                                <td>
                                    <h3>{{ $reservation->pitche->name }}:</h3>
                                </td>
                                <td>
                                    <h3>
                                        <span class="text-warning">
                                            from {{ $reservation->debut }}:00 to {{ $reservation->fin }}:00
                                        </span>
                                    </h3>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h3>Reserved at:</h3>
                                </td>
                                <td>
                                    <h3>{{ $reservation->created_at->format('d / m') }}</h3>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h3>Day of play:</h3>
                                </td>
                                <td>
                                    <h3>{{ $reservation->created_at->addDay()->format('d / m') }}</h3>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            @endforeach
        </div>



        <!-- <div class="border border-5 border-success  p-lg-5 p-sm-4">

            @foreach ( $reservations as $reservation)
            <div class="alert alert-light mb-5 " role="alert">
                <ul class="list-group ">
                    <li class="list-group-item text-capitalize fw-light fs-3">{{$reservation->pitche->name}} : <span class="text-warning"> from {{$reservation->debut}} to {{$reservation->fin}}</span> </li>
                    <li class="list-group-item text-capitalize fw-light fs-3">reserved at : <span class="text-warning">{{$reservation->created_at->format('d / m')}}</span> <br></li>
                    <li class="list-group-item text-capitalize fw-light fs-3">day of play : <span class="text-warning">{{$reservation->created_at->addDay()->format('d / m')}}</span> </li>
                    @if (auth()->user()->role == 'provider')
                    <li class="list-group-item text-capitalize fw-light fs-3">
                        user name : <span class="text-warning">{{ $reservation->user->name }}</span>
                    </li>
                    <li class="list-group-item text-capitalize fw-light fs-3">
                        user phone : + 212 <span class="text-warning">{{ $reservation->user->profile->phone_number ?? 'Not Available' }}</span>
                    </li>

                    @endif
                </ul>

                <br>


            </div>
            <hr class="hr-divider border-3 text-success">

            @endforeach
        </div> -->




        @else
        <h1 class="text-success">you dont have any reservations</h1>
        @endif
    </section>


</main><!-- End #main -->


@endsection


@section('title', 'pitches')