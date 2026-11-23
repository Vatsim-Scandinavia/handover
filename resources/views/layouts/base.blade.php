{{-- The shell for every page. It touches no session, auth or database, so
     error pages can use it when those fail. Defaults to the member sheet. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no">
    <meta name="robots" content="noindex">
    @stack('meta')

    <title>@hasSection('title')@yield('title') | @endif{{ config('app.name') }}</title>

    <link rel="icon" href="{{ URL::asset('/favicon.ico') }}" type="image/x-icon"/>
    @include('layouts.theme-head')
    @vite(['resources/js/theme.ts', 'resources/sass/app.scss', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">

    @stack('head')
</head>
<body>
    <a href="#main" class="visually-hidden-focusable position-absolute top-0 start-0 m-2 btn btn-light">Skip to content</a>
    <div class="page @yield('shell', 'member')">
        <div class="@yield('column', 'member-column')">
            @hasSection('bar')
                @yield('bar')
            @else
                @include('layouts.bar', ['account' => false, 'member' => false])
            @endif

            @yield('alerts')

            <main id="main" class="@yield('main-class', 'member-sheet')" tabindex="-1">
                @yield('content')
            </main>
        </div>
        @hasSection('show-version')
            <x-version-footer :version="true" />
        @else
            <x-version-footer />
        @endif
    </div>
</body>
</html>
