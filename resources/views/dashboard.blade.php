@extends('layouts.app')

@section('content')
    @include('layouts.header')
    <div class="container py-4" id="app">
        <passport-authorized-clients></passport-authorized-clients>
    </div>
    
    <hr>

    <div class="alert alert-info text-sm" style="font-size: 12px" role="alert">
        <i class="fas fa-info-circle"></i>&nbsp;To delete your personal data contact the <a href="mailto:{{ Config::get('app.dpo_mail') }}">Data Protection Officer</a>.
    </div>

    <a href="{{route('logout')}}" class="btn btn btn-primary">Logout</a>
@endsection

@section('js')
    <script>
        var apiUri = "{{ url('/api') }}";
        var csrf = "{{ csrf_token() }}";
    </script>
@endsection