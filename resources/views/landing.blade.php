@extends('layouts.app')

@section('content')

        <h3>Handover</h3>
        <p>Centralised Authentication Service</p>

        <hr>

        @if(Session::has('error'))
                <div class="alert-box alert"><i class="fa fa-lg fa-exclamation-circle"></i> {!! Session::get('error') !!}</div>
        @endif

        <form class="form-horizontal" method="POST" action="{{ route('login') }}">
                @csrf
                <button type="submit" class="btn btn btn-primary">Login with VATSIM</button>
        </form>

@endsection