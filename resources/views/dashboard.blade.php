@extends('layouts.app')

@section('title', "Your account")

@section('content')
    <h1>Your account</h1>
    {{-- The bar hides the name and CID on narrow screens. --}}
    <p class="shell-who-narrow">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }} <span class="shell-cid">{{ Auth::user()->id }}</span></p>

    <h2>Services you've signed in to</h2>
    @if($clients->isEmpty())
        <p class="text-muted">You haven't signed in to any service through {{ config('app.name') }} yet.</p>
    @else
        <ul class="strips">
            @foreach($clients as $client)
                <li>
                    @if($client['url'])
                        <a href="{{ $client['url'] }}" class="strip strip-link" target="_blank" rel="noopener noreferrer">
                            {{ $client['name'] }}
                            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                            <span class="visually-hidden">(opens in a new tab)</span>
                        </a>
                    @else
                        <div class="strip">{{ $client['name'] }}</div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <h2>Your groups</h2>
    @if($directGroups->isEmpty())
        <p class="text-muted">You aren't in any groups.</p>
    @else
        @if($children->isNotEmpty())
            <p class="small text-muted">Indented groups come with the group above them.</p>
        @endif
        @include('parts.group-strips', ['groups' => $directGroups])
    @endif


    <p class="member-note">
        To delete your personal data, contact the <a href="mailto:{{ config('app.dpo_mail') }}">Data Protection Officer</a>.
    </p>
@endsection
