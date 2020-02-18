@extends('layouts.app')

@section('content')
    <h3>Handover</h3>
    <p>Centralised Authentication Service</p>
    <hr>
    <div class="container py-4" id="app">
        <passport-authorized-clients></passport-authorized-clients>
    </div>
    
    <hr>

    <div class="alert alert-info text-sm" style="font-size: 12px" role="alert">
        <i class="fas fa-info-circle"></i>&nbsp;Revoking access does not delete the data, but revokes the possiblity of the service to pull updated data from Handover. To delete your data contact the <a href="mailto:{{ env('APP_DPO_MAIL') }}">Data Protection Officer</a>.
    </div>

    <a href="{{route('logout')}}" class="btn btn btn-primary">Logout</a>
@endsection

@section('js')
    <script>
        var apiUri = "{{ url('/api') }}";
        var csrf = "{{ csrf_token() }}";
    </script>
@endsection