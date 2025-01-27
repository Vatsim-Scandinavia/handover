<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">

    <title>{{ config('app.name', 'Handover') }}</title>

    <!-- Styles -->
    <link rel="icon" href="{{ URL::asset('/favicon.ico') }}" type="image/x-icon"/>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">

    @stack('head')
</head>
<body>
    <div class="bg-primary min-vh-100 pb-2">
        <div class="container">
            <div class="row justify-content-center pt-4 pb-4">
                <div class="col-md-6 text-center">

                    <a href="{{ env('APP_URL') }}"><img src="{{ asset('images/logos/'.Config::get('app.logo')) }}" alt="{{ Config::get('app.owner_contact') }} logo" class="w-100 pb-4" style="max-width: 300px"/></a>

                    @if(Session::has('error') OR isset($error))
                        <div class="alert alert-danger" role="alert">
                            <i class="fa fa-lg fa-exclamation-circle"></i> {!! Session::has('error') ? Session::pull("error") : $error !!}
                        </div>
                    @endif

                    <div class="card">
                        <div class="card-body">
                            @yield('content')
                        </div>
                    </div>
                    @auth <p class="text-white" style="font-size: 10px; padding-top: 5px;">Logged in as {{ Auth::user()->first_name }} {{ Auth::user()->last_name}}, {{ Auth::user()->id }}</p> @endauth
                </div>
            </div>
        </div>
    </div>
    @yield('js')
</body>
</html>