@extends('layouts.app')

@section('content')

        @include('layouts.header')

        <h5>Privacy Policy</h5>
        <p>Accept our privacy policy to login into our services.</p>
        <p>Last update: {{ Config::get('app.dpp_date') }}, <a class="text-info" target="_blank" href="{{ Config::get('app.dpp_url') }}">read the full privacy policy.</a></p>

        <div class="card mb-4 border-left-danger">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Simplified Data Protection Policy</h6>
            </div>
            <div class="card-body text-left">
                @include('parts.dpp-bullets')
                <div class="pp-bullet">
                    <i class="fas fa-envelope"></i>
                    
                For questions or inquires, contact our Data Protection Officer at <a href="mailto:{{ Config::get('app.dpo_mail') }}">{{ Config::get('app.dpo_mail') }}</a>
                </div>
            </div>
        </div>

        <form class="form-horizontal" method="POST" action="{{ route('dpp.accept') }}">
                @csrf
                <button type="submit" class="btn btn btn-primary">Yes, I accept</button>
        </form>

@endsection