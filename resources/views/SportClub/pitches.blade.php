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
    <section id="blog" class="blog pt-4">
        <div class="container" data-aos="fade-up">

            {{-- Search & filters --}}
            <form method="GET" action="{{ route('reservation.index') }}" class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-lg-3 col-md-6">
                            <label for="q" class="form-label small text-muted mb-1">Name or city</label>
                            <input type="search" id="q" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="e.g. Khemisset">
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label for="size" class="form-label small text-muted mb-1">Size</label>
                            <select id="size" name="size" class="form-select">
                                <option value="">Any size</option>
                                @foreach ($sizes as $size)
                                    <option value="{{ $size }}" @selected($filters['size'] == $size)>{{ intdiv($size, 2) }} vs {{ intdiv($size, 2) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <label for="hour" class="form-label small text-muted mb-1">Free tomorrow at</label>
                            <select id="hour" name="hour" class="form-select">
                                <option value="">Any time</option>
                                @for ($h = 6; $h <= 23; $h++)
                                    <option value="{{ $h }}" @selected($filters['hour'] === $h)>{{ sprintf('%02d:00', $h) }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <label for="max_price" class="form-label small text-muted mb-1">Max price / hour</label>
                            <input type="number" min="0" id="max_price" name="max_price" value="{{ $filters['max_price'] }}" class="form-control" placeholder="{{ config('sportclub.currency') }}">
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <label for="sort" class="form-label small text-muted mb-1">Sort by</label>
                            <select id="sort" name="sort" class="form-select">
                                @foreach ($sorts as $value => $label)
                                    <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-1 col-md-12 d-grid">
                            <button type="submit" class="btn btn-info"><i class="bi bi-search"></i><span class="d-lg-none ms-1">Search</span></button>
                        </div>
                    </div>
                    @if ($filters['q'] !== '' || $filters['size'] || $filters['hour'] !== null || $filters['max_price'] !== null)
                        <div class="small mt-2">
                            {{ $terrain->total() }} {{ \Illuminate\Support\Str::plural('terrain', $terrain->total()) }} found ·
                            <a href="{{ route('reservation.index') }}">clear filters</a>
                        </div>
                    @endif
                </div>
            </form>

            <div class="row gy-4 posts-list">

                @forelse ($terrain as $pitche)
                <div class="col-lg-4 col-md-6">
                    <article class="h-100 d-flex flex-column">
                        <a href="{{ route('reservation.show', $pitche->name) }}" class="post-img bg-success d-flex justify-content-center">
                            <img style="height: 200px; object-fit: cover" src="{{ $pitche->imageUrl() }}" alt="{{ $pitche->name }}" class="img-fluid w-100">
                        </a>
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="post-category mb-0">{{ $pitche->formatLabel() }}</p>
                            @if ($pitche->priceLabel())
                                <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle fs-6">{{ $pitche->priceLabel() }}</span>
                            @endif
                        </div>
                        <h2 class="title mt-2">
                            <a href="{{ route('reservation.show', $pitche->name) }}" class="text-success">{{ $pitche->name }}</a>
                        </h2>
                        <div class="small text-muted mb-2"><i class="bi bi-geo-alt"></i> {{ $pitche->localisation }} · <i class="bi bi-clock"></i> {{ $pitche->openhours }}</div>
                        @include('SportClub.partials.stars', ['rating' => $pitche->averageRating(), 'count' => $pitche->reviewsCount()])

                        <div class="mt-auto pt-3 d-flex flex-wrap gap-2">
                            <a href="{{ route('reservation.show', $pitche->name) }}" class="btn btn-sm btn-info">Book</a>

                            {{-- For Admins --}}
                            @auth
                            @if ($user_role == 'admin')
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('AdminDashbord.edit', $pitche->id) }}">edit</a>
                                <form action="{{ route('AdminDashbord.destroy', $pitche->id) }}" method="POST"
                                    onsubmit="return confirm('Delete {{ addslashes($pitche->name) }}? Its reservations will be deleted too.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">delete</button>
                                </form>
                            @endif
                            @endauth
                        </div>
                    </article>
                </div>
                @empty
                <div class="col-12">
                    <p class="alert alert-secondary">No terrain matches these filters. <a href="{{ route('reservation.index') }}">Show all terrains</a>.</p>
                </div>
                @endforelse

                <div class="row mt-3">
                    <div class="col-lg-12 d-flex justify-content-center">
                        {{ $terrain->links() }}
                    </div>
                </div>

                <!-- End pitches pagination -->

            </div>
        </div>
    </section><!-- End pitches Section -->

</main><!-- End #main -->


@endsection
@section('title', 'pitches')