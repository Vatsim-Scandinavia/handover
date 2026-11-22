@extends('layouts.admin')
@section('title', 'New group')
@section('content')
<div class="card">
    <x-page-header title="New group" :crumbs="[['Groups', route('groups.index')]]" />
    <div class="card-body">
        <form method="POST" action="{{ route('groups.store') }}">
            @csrf
            @include('groups._form', ['group' => null])
            <div class="row">
                <div class="col-sm-9 offset-sm-3">
                    <button class="btn btn-primary">Create group</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
