@extends('layouts.app')

@section('content')

        <h3>Handover</h3>
        <p>Centralised Authentication Service</p>

        <hr>

        <form class="form-horizontal" method="POST" action="{{ route('login') }}">
                @csrf
                <button type="submit" class="btn btn btn-primary">Login with VATSIM</button>
        </form>

@endsection