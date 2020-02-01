@extends('layouts.app')

@section('content')
    <h3>Handover</h3>
    <p>Centralised Authentication Service</p>
    <hr>
    <div class="container py-4" id="app">
        <app></app>
    </div>
    <passport-authorized-clients></passport-authorized-clients>
    <hr>
    <a href="{{route('logout')}}" class="btn btn btn-primary">Logout</a>
@endsection

@push('afterBody')
    <script>
        var apiUri = "{{ url('/api') }}";
        var csrf = "{{ csrf_token() }}";
    </script>
    <script src="{{ mix('js/app.js') }}"></script>
@endpush