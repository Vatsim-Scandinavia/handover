@extends('layouts.app')

@section('content')
    @include('layouts.header')

    <h2 class="h5 mt-3">You're logged out</h2>
    <p>You are logged out of Handover. You stay logged in to other services until you log out of them too.</p>
    <a href="{{ route('landing') }}" class="btn btn-primary">Log in again</a>
@endsection
