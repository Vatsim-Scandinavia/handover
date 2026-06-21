@extends('layouts.admin')
@section('content')
<div class="card">
    <x-card-header>
        <strong>New Group</strong>
        <x-slot:actions>
            <a href="{{ route('groups.index') }}" class="btn btn-sm btn-outline-secondary">← Groups</a>
        </x-slot:actions>
    </x-card-header>
    <div class="card-body">
        <form method="POST" action="{{ route('groups.store') }}">
            @csrf
            @include('groups._form', ['group' => null])
            <button class="btn btn-primary">Create Group</button>
        </form>
    </div>
</div>
@endsection
