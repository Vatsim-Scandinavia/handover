@extends('layouts.app')

@section('title', "Logged out")

@section('content')
    <h1>You're logged out</h1>
    <p>You are logged out of {{ config('app.name') }}. You stay logged in to other services until you log out of them too.</p>
    <a href="{{ route('landing') }}" class="btn btn-primary">Log in again</a>
@endsection
