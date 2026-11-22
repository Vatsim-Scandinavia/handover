<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>@hasSection('title')@yield('title') | @endif{{ config('app.name') }}</title>
    <link rel="icon" href="{{ URL::asset('/favicon.ico') }}" type="image/x-icon"/>
    @include('layouts.theme-head')
    @vite(['resources/js/theme.ts', 'resources/sass/app.scss', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>
    <a href="#main" class="visually-hidden-focusable position-absolute top-0 start-0 m-2 btn btn-light">Skip to content</a>
    <div class="bg-primary page">
        <div class="container-md">
            <div class="row justify-content-center pt-4 pb-2">
                <div class="col-lg-10 col-md-12">
                    <header class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
                        <nav class="admin-nav d-flex align-items-center gap-3" aria-label="Main">
                            <a href="{{ env('APP_URL') }}" class="fw-medium" style="font-size: 1rem;">{{ config('app.name') }}</a>
                            <a href="{{ route('groups.index') }}" @if(request()->routeIs('groups.*')) aria-current="page" @endif>Groups</a>
                            @can('manage-oauth-clients')
                                <a href="{{ route('admin.oauth-clients.index') }}" @if(request()->routeIs('admin.oauth-clients.*')) aria-current="page" @endif>OAuth clients</a>
                            @endcan
                        </nav>
                        <div class="text-white" style="font-size: 12px;">
                            {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
                            <span class="d-none d-sm-inline">({{ Auth::user()->id }})</span>
                            &mdash;
                            <x-logout-button inline class="btn btn-link p-0 align-baseline text-white" style="font-size: inherit;" />
                            <span class="ms-2">@include('layouts.theme-toggle')</span>
                        </div>
                    </header>

                    @if(Session::has('success'))
                        <div class="alert alert-success" role="status">{{ Session::pull('success') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <main id="main" class="admin-main" tabindex="-1">
                        @yield('content')
                    </main>
                </div>
            </div>
        </div>
        <x-version-footer :version="true" />
    </div>
</body>
</html>
