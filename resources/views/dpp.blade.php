@extends('layouts.app')

@section('content')

        @include('layouts.header')

        <div class="dpp bt py-4 text-left">
            <h2 class="mb-2">Privacy Policy</h2>
            <p>Accept our privacy policy to login into our services.<br>
            Last update: {{ Config::get('app.dpp_date') }}, <a target="_blank" href="{{ Config::get('app.dpp_url') }}">read the full privacy policy.</a></p>

            <h2 class="mb-3">In short terms</h2>
            @include('parts.dpp-bullets')

            <form class="form-horizontal" method="POST" action="{{ route('dpp.accept') }}">
                    @csrf
                    <button type="submit" class="btn btn btn-primary mt-4">Yes, I accept</button>
            </form>
        </div>

@endsection