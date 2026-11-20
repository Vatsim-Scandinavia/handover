@extends('layouts.app')

@section('content')
    @include('layouts.header')

    <h2 class="h5 mt-3">You can't sign in to {{ $client->name }}</h2>
    <p>{{ $client->name }} is only open to members of certain groups, and you are not a member of any of them.</p>
    <p class="mb-0">If you think you should have access, contact the people who run {{ $client->name }}.</p>
@endsection
