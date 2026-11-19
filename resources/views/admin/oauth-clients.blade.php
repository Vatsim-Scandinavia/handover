@extends('layouts.admin')

@section('content')
    <div id="app">
        <oauth-clients
            :grant-types="@js($grantTypes)"
            :default-grant-types="@js($defaultGrantTypes)"
        ></oauth-clients>
    </div>
@endsection
