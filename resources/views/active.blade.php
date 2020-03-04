@extends('layouts.app')

@section('content')
    @include('layouts.header')

    @if(is_null($user))
        <div class="alert alert-info text-sm" role="alert">
            <p>User not found!</p>
        </div>
    @else
        @if($user->rating > 2)
            @if($user->atc_active == 1)
                <div class="alert alert-success text-sm" role="alert">
                    <p>{{ $user->id }} ({{ $user->first_name }}) is <b>active</b>.</p>
                </div>
            @else
                <div class="alert alert-danger text-sm" role="alert">
                    <p>{{ $user->id }} ({{ $user->first_name }}) is <b>inactive</b>.</p>
                </div>
            @endif
        @else
            <div class="alert alert-info text-sm" role="alert">
                <p>{{ $user->id }} ({{ $user->first_name }}) does not hold an ATC rating of S2 or higher.</p>
            </div>
        @endif
    @endif
@endsection