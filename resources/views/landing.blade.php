@extends('layouts.app')

@section('content')
    <h1>{{ config('app.name') }}</h1>
    <p class="text-muted">{{ config('app.tagline') }}</p>
    <p>Log in once with your VATSIM account to use {{ config('app.owner') }}'s services.</p>

    <a href="{{ route('login') }}" class="btn btn-primary">Log in with VATSIM</a>
@endsection
