@extends('layouts.app')

@section('content')
    @include('layouts.header')

    <h2 class="h5 mt-3">Handover can't complete this logout</h2>
    <p>{{ $message }}</p>
    @auth
        <p>You are still logged in to Handover.</p>
    @endauth
    <p class="mb-0">If this keeps happening, contact the people who run the service.</p>
@endsection
