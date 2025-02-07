@extends('layouts.app')
@section('content')
    <main class="pt-90">
        <div class="mb-4 pb-4"></div>
        <section class="contact-us container">
            <div class="mw-930">
                <h2 class="page-title">CONTACT US</h2>
            </div>
        </section>

        <hr class="mt-2 text-secondary " />
        <div class="mb-4 pb-4"></div>

        <section class="contact-us container">
            <div class="mw-930">
                <div class="contact-us__form">
                    @if (Session::has('success'))
                        <div class="alert alert-success alert-dismissable fade show">
                            {{ Session::get('success') }}
                        </div>
                    @endif
                    <form name="contact-us-form" action="{{ route('contact.store') }}" class="needs-validation"
                        novalidate="" method="POST">
                        @csrf
                        <h3 class="mb-5">Get In Touch</h3>
                        <div class="form-floating my-4">
                            <input value="{{old('name')}}" type="text" class="form-control" name="name" placeholder="Name *" required="">
                            <label for="contact_us_name">Name *</label>
                        </div>
                        @error('name')
                            <span class="alert alert-danger text-center">{{ $message }}</span>
                        @enderror
                        <div class="form-floating my-4">
                            <input value="{{old('phone')}}" type="text" class="form-control" name="phone" placeholder="Phone *" required="">
                            <label for="contact_us_name">Phone *</label>
                        </div>
                        @error('phone')
                            <span class="alert alert-danger text-center">{{ $message }}</span>
                        @enderror
                        <div class="form-floating my-4">
                            <input value="{{old('email')}}" type="email" class="form-control" name="email" placeholder="Email address *"
                                required="">
                            <label for="contact_us_name">Email address *</label>
                        </div>
                        @error('email')
                            <span class="alert alert-danger text-center">{{ $message }}</span>
                        @enderror
                        <div class="my-4">
                            <textarea class="form-control form-control_gray" name="comment" placeholder="Your Message" cols="30" rows="8"
                                required=""></textarea>
                                @error('comment')
                                <span class="alert alert-danger text-center">{{ $message }}</span>
                                @enderror
                        </div>

                        <div class="my-4">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>
@endsection
