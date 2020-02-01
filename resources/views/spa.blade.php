@extends('layouts.app')

@section('content')
    <div class="container py-4" id="app">
        <app></app>
    </div>
@endsection

@push('afterBody')
    <script>
        var apiUri = "{{ url('/api') }}";
        var csrf = "{{ csrf_token() }}";
    </script>
    <script src="{{ mix('js/app.js') }}"></script>
@endpush