@extends('layouts.app')

@section('title', "Logout failed")

@section('content')
    <h1>{{ config('app.name') }} can't complete this logout</h1>
    <p>{{ $message }}</p>
    @auth
        <p>You are still logged in to {{ config('app.name') }}.</p>
    @endauth
    <p class="mb-0">If this keeps happening, contact the people who run the service.</p>
@endsection
