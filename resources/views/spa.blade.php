@extends('layouts.app')

@section('content')
    <h3>Handover</h3>
    <p>Centralised Authentication Service</p>
    <hr>
    <div class="container py-4" id="app">
        <passport-authorized-clients></passport-authorized-clients>
    </div>
    
    <hr>
    <a href="{{route('logout')}}" class="btn btn btn-primary">Logout</a>
@endsection

@section('js')
    <script>
        var apiUri = "{{ url('/api') }}";
        var csrf = "{{ csrf_token() }}";
    </script>
@endsection