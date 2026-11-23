@extends('layouts.app')

@section('title', "Privacy policy")

@section('content')
    <h1>Privacy policy</h1>
    <p>Accept our privacy policy to log in to our services. Here it is in short. <a target="_blank" rel="noopener noreferrer" href="{{ config('app.dpp_url') }}">Read the full privacy policy</a>.</p>

    @include('parts.dpp-bullets')

    <form class="dpp-accept" method="POST" action="{{ route('dpp.accept') }}">
        @csrf
        <button type="submit" class="btn btn-primary">Yes, I accept</button>
        <span class="small text-muted">Last updated {{ config('app.dpp_date') }}</span>
    </form>
@endsection
