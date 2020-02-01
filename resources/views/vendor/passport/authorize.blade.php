@extends('layouts.box')

@section('slickNav')
    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <a class="nav-link" href="{{route('logout')}}">Logout</a>
        </li>
    </ul>
@endsection

@push('head')
    <style>
        .passport-authorize .scopes {
            margin-top: 20px;
        }
        .passport-authorize .buttons {
            margin-top: 25px;
            text-align: center;
        }
        .passport-authorize .btn {
            width: 125px;
        }
        .passport-authorize .btn-approve {
            margin-right: 15px;
        }
        .passport-authorize form {
            display: inline;
        }
    </style>
@endpush

@section('content')
    <div class="passport-authorize">
        <h3>Handover Authorisation</h3>
        <!-- Introduction -->
        <p><strong>{{ $client->name }}</strong> is requesting permission to access your VATSIM Scandinavia account data.</p>

        <!-- Scope List -->
        @if (count($scopes) > 0)
            <div class="scopes">
                <p><strong>This application will be able to:</strong></p>

                <ul>
                    @foreach ($scopes as $scope)
                        <li>{{ $scope->description }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="buttons">
            <!-- Authorize Button -->
            <form method="post" action="{{ route('passport.authorizations.approve') }}">
                {{ csrf_field() }}

                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <button type="submit" class="btn btn-success btn-approve">Authorise</button>
            </form>

            <!-- Cancel Button -->
            <form method="post" action="{{ route('passport.authorizations.deny') }}">
                {{ csrf_field() }}
                {{ method_field('DELETE') }}

                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <button class="btn btn-danger">Cancel</button>
            </form>
        </div>
    </div>
@endsection