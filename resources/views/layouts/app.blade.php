<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'VATSCA Handover') }}</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">

    @stack('head')
</head>
<body>
    <div class="bg-primary min-vh-100 pb-2">
        <div class="container">
            <div class="row justify-content-center" style="padding-top: 10%">
                <div class="col-md-6 text-center">

                    <a href="{{ env('APP_URL') }}"><img src="{{asset('img/vatsca-logo-negative.svg')}}" alt="VATSIM Scandinavia logo" class="w-100 pb-4" style="max-width: 300px"/></a>

                    @if(Session::has('error') OR isset($error))
                        <div class="alert alert-danger" role="alert">
                            <strong>Error!</strong> {!! Session::has('error') ? Session::pull("error") : $error !!}
                        </div>
                    @endif

                    <div class="card">
                        <div class="card-body">
                            @yield('content')
                        </div>
                    </div>
                    @auth <p class="text-white" style="font-size: 10px; padding-top: 5px;">Logged in as {{ Auth::user()->full_name }}, {{ Auth::user()->id }}</p> @endauth
                </div>
            </div>
        </div>
    </div>
    @yield('js')
</body>
</html>