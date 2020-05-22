@extends('layouts.app')

@section('content')

        @include('layouts.header')

        <a href="{{ route('login') }}" class="btn btn btn-primary">Login with VATSIM</a>

@endsection