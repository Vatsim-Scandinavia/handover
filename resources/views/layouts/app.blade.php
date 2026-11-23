@extends('layouts.base')

@push('meta')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('bar')
    @include('layouts.bar', ['account' => true, 'member' => true])
@endsection

@section('alerts')
    @if(Session::has('error') OR isset($error))
        <div class="alert alert-danger" role="alert">
            <i class="fa fa-lg fa-exclamation-circle"></i> {!! Session::has('error') ? Session::pull("error") : $error !!}
        </div>
    @endif
@endsection
