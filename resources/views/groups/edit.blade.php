@extends('layouts.admin')
@section('content')
<div class="card">
    <x-card-header>
        <strong>Edit: {{ $group->name }}</strong>
        <x-slot:actions>
            <a href="{{ route('groups.show', $group) }}" class="btn btn-sm btn-outline-secondary">← Back</a>
        </x-slot:actions>
    </x-card-header>
    <div class="card-body">
        <form method="POST" action="{{ route('groups.update', $group) }}">
            @csrf @method('PATCH')
            @include('groups._form', ['group' => $group])
            <button class="btn btn-primary">Save Changes</button>
        </form>

        <hr>
        <form method="POST" action="{{ route('groups.destroy', $group) }}" onsubmit="return confirm('Delete this group? It must have no members.')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger btn-sm">Delete Group</button>
        </form>
    </div>
</div>
@endsection
