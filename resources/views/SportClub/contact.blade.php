@extends('SportClub.layouts.parts')

@section('content')
    <div id="main">
        <!-- ======= Contact Section ======= -->
        <section id="contact" class="contact">
            <div class="container" data-aos="fade-up">

                <div class="section-header">
                    <h2>Contact</h2>
                    <p>A question about a terrain, a booking or your account? Send us a message.</p>
                </div>

                <div class="row gx-lg-0 gy-4">

                    <div class="col-lg-12">
                        @if (session('message'))
                            <div class="alert alert-success">{{ session('message') }}</div>
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

                        <form action="{{ route('contact.store') }}" method="post" role="form" class="contact-form p-4 p-lg-5 bg-light rounded">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <input type="text" name="name" class="form-control" id="name" placeholder="Your Name"
                                        value="{{ old('name', optional(auth()->user())->name) }}" required maxlength="255">
                                </div>
                                <div class="col-md-6 form-group mt-3 mt-md-0">
                                    <input type="email" class="form-control" name="email" id="email" placeholder="Your Email"
                                        value="{{ old('email', optional(auth()->user())->email) }}" required maxlength="255">
                                </div>
                            </div>
                            <div class="form-group mt-3">
                                <input type="text" class="form-control" name="subject" id="subject" placeholder="Subject"
                                    value="{{ old('subject') }}" required maxlength="255">
                            </div>
                            <div class="form-group mt-3">
                                <textarea class="form-control" name="message" rows="7" placeholder="Message" required maxlength="5000">{{ old('message') }}</textarea>
                            </div>
                            <div class="text-center mt-4"><button type="submit" class="btn btn-info btn-lg">Send Message</button></div>
                        </form>
                    </div><!-- End Contact Form -->

                </div>

            </div>
        </section><!-- End Contact Section -->
    </div>

@endsection
@section('title' , 'contact')
