@extends('layouts.app')

@section('content')

        <h3>Handover</h3>
        <p>Centralised Authentication Service</p>

        <hr>

        <a href="{{route('login')}}" class="btn btn btn-primary">Login with VATSIM</a>

@endsection