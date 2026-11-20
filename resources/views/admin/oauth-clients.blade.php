@extends('layouts.admin')

@section('content')
    <div id="app">
        <oauth-clients
            :grant-types="@js($grantTypes)"
            :default-grant-types="@js($defaultGrantTypes)"
            :scopes="@js($scopes)"
            :groups="@js($groups)"
        ></oauth-clients>
    </div>
@endsection
