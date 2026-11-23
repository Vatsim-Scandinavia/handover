@extends('layouts.base')

@section('shell', 'admin')
@section('column', 'admin-column')
@section('main-class', 'admin-main')
@section('show-version', 'true')

@push('meta')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('bar')
    @include('layouts.bar', ['account' => true, 'member' => false])
@endsection

@section('alerts')
    @if(Session::has('success'))
        <div class="alert alert-success" role="status">{{ Session::pull('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection
