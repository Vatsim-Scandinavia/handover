@extends('layouts.app')

@section('content')

        @include('layouts.header')

        <form class="form-horizontal" method="POST" action="{{ route('login') }}">
                @csrf
                <button type="submit" class="btn btn btn-primary">Login with VATSIM</button>
        </form>

@endsection