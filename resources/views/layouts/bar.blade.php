{{-- The top bar. Always pass 'account' and 'member', or the page's own variables leak in. --}}
<header class="shell-bar">
    <a href="{{ config('app.url') }}" class="shell-brand">
        <img src="{{ asset('images/logos/'.config('app.logo')) }}" alt="{{ config('app.owner') }}">
        <span>{{ config('app.name') }}</span>
    </a>

    @if($account)
        @auth
            @php
                $canGroups = Auth::user()->can('viewAny', \App\Models\Group::class);
                $canClients = Auth::user()->can('manage-oauth-clients');
            @endphp
            @if($canGroups || $canClients)
                <nav class="shell-nav" aria-label="Management">
                    @if($canGroups)
                        <a href="{{ route('groups.index') }}" @if(request()->routeIs('groups.*')) aria-current="page" @endif>{{ $member ? 'Manage groups' : 'Groups' }}</a>
                    @endif
                    @if($canClients)
                        <a href="{{ route('admin.oauth-clients.index') }}" @if(request()->routeIs('admin.oauth-clients.*')) aria-current="page" @endif>{{ $member ? 'Manage OAuth clients' : 'OAuth clients' }}</a>
                    @endif
                </nav>
            @endif
        @endauth
    @endif

    <div class="shell-account">
        @if($account)
            @auth
                <span class="shell-who">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }} <span class="shell-cid">{{ Auth::user()->id }}</span></span>
            @endauth
        @endif
        @include('layouts.theme-toggle')
        @if($account)
            @auth
                <x-logout-button inline class="btn btn-link p-0 align-baseline text-white" style="font-size: inherit;" />
            @endauth
        @endif
    </div>
</header>
