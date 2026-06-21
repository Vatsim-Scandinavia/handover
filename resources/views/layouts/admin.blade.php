<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>{{ config('app.name', 'Handover') }} — Groups</title>
    <link rel="icon" href="{{ URL::asset('/favicon.ico') }}" type="image/x-icon"/>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
</head>
<body>
    <div class="bg-primary min-vh-100 pb-4">
        <div class="container-md">
            <div class="row justify-content-center pt-4 pb-2">
                <div class="col-lg-10 col-md-12">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <a href="{{ env('APP_URL') }}" class="text-white text-decoration-none">
                                <strong>{{ config('app.name', 'Handover') }}</strong>
                            </a>
                            <a href="{{ route('groups.index') }}" class="text-white text-decoration-none" style="font-size: 14px;">Groups</a>
                        </div>
                        <div class="text-white" style="font-size: 12px;">
                            {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
                            <span class="d-none d-sm-inline">({{ Auth::user()->id }})</span>
                            &mdash; <a href="{{ route('logout') }}" class="text-white">Logout</a>
                        </div>
                    </div>

                    @if(Session::has('success'))
                        <div class="alert alert-success">{{ Session::pull('success') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')

                    <x-version-footer class="text-end" />
                </div>
            </div>
        </div>
    </div>
</body>
</html>
