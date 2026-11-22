@extends('layouts.app')

@section('content')
    @include('layouts.header')

    <h2 class="h5 mt-3">Log out of Handover?</h2>
    @if($client)
        <p>{{ $client->name }} asked to log you out of Handover.</p>
    @endif
    <p>You stay logged in to other services until you log out of them too.</p>

    <form method="POST" action="{{ route('oidc.logout') }}" class="d-flex justify-content-center gap-2">
        @csrf
        @if($client)
            <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
        @endif
        @if($redirectUri !== null)
            <input type="hidden" name="post_logout_redirect_uri" value="{{ $redirectUri }}">
        @endif
        @if($state !== null)
            <input type="hidden" name="state" value="{{ $state }}">
        @endif
        <button type="submit" class="btn btn-primary">Log out</button>
        <a href="{{ route('landing') }}" class="btn btn-outline-secondary">Stay logged in</a>
    </form>
@endsection
