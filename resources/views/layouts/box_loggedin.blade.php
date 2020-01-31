@extends('layouts.box')

@section('slickNav')
    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <a class="nav-link" href="{{route('logout')}}">Logout</a>
        </li>
    </ul>
@endsection