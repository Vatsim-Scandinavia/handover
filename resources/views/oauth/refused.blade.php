@extends('layouts.app')

@section('title', "Can't sign in")

@section('content')
    <h1>You can't sign in to {{ $client->name }}</h1>
    <p>{{ $client->name }} is only open to members of certain groups, and you are not a member of any of them.</p>
    <p>If you think you should have access, contact the people who run {{ $client->name }}.</p>

    <div class="member-actions">
        <a href="{{ route('landing') }}" class="btn btn-primary">See your groups</a>
        <x-logout-button class="btn btn-outline-secondary" />
    </div>
@endsection
