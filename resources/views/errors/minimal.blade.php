{{-- Laravel's error views extend this. layouts/base needs no session, auth
     or database, so it renders when those are down. --}}
@extends('layouts.base')

@php
    $code = (int) trim($__env->yieldContent('code'));
    $app = config('app.name');
    $explanation = match ($code) {
        401 => "You need to log in to $app to see this page.",
        403 => "Your account doesn't have access to this page.",
        404 => "This page doesn't exist. Check the address, or start again from your account.",
        419 => 'The page was open too long and its form expired. Go back, reload the page and try again.',
        429 => 'Too many requests in a short time. Wait a minute, then try again.',
        500 => "Something went wrong in $app. Try again in a moment.",
        503 => "$app is briefly unavailable, often for an update. Try again in a few minutes.",
        default => null,
    };
    $contact = in_array($code, [403, 500, 503], true);
@endphp

@section('content')
    <h1>@yield('message')</h1>
    <p class="text-muted">Error @yield('code')</p>

    @if($explanation)
        <p>{{ $explanation }}</p>
    @endif
    @if($code === 404)
        <p class="small">You asked for <code>/{{ ltrim(request()->path(), '/') }}</code>.</p>
    @endif
    @if($contact && config('app.owner_support'))
        <div class="small mb-3">{!! config('app.owner_support') !!}</div>
    @elseif($contact)
        <p class="small">If this keeps happening, contact {{ config('app.owner') }} at <a href="mailto:{{ config('app.owner_contact') }}">{{ config('app.owner_contact') }}</a>.</p>
    @endif

    <div class="member-actions">
        @if($code === 401)
            <a href="{{ route('login') }}" class="btn btn-primary">Log in with VATSIM</a>
        @else
            <a href="{{ config('app.url') }}" class="btn btn-primary">Go to {{ $app }}</a>
        @endif
    </div>
@endsection
