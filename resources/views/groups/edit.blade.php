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
        <h6>Parent groups</h6>
        <p class="text-muted" style="font-size:13px">Groups this group nests into. Members of this group become inherited members of its parents. Nesting under an admin group <strong>does not grant admin</strong> — admin access is assigned directly.</p>

        @if($group->parents->isNotEmpty())
        <ul class="list-unstyled">
            @foreach($group->parents as $parent)
            <li class="mb-1">
                <span class="badge bg-info text-dark">{{ $parent->name }}</span>
                <form method="POST" action="{{ route('groups.parents.destroy', ['group' => $group, 'parent' => $parent->id]) }}" class="d-inline">
                    @csrf @method('DELETE')
                    <button class="btn btn-link btn-sm text-danger p-0">remove</button>
                </form>
            </li>
            @endforeach
        </ul>
        @endif

        <form method="POST" action="{{ route('groups.parents.store', $group) }}" class="d-flex gap-2 align-items-start">
            @csrf
            <select name="parent_id" class="form-select form-select-sm w-auto" required>
                <option value="">Add a parent group…</option>
                @foreach($candidateParents as $candidate)
                <option value="{{ $candidate->id }}">{{ $candidate->name }}@if($candidate->is_admin_group) (admin group — does not grant admin)@endif</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-primary">Add parent</button>
        </form>
        @error('parent_id')<div class="text-danger mt-1" style="font-size:13px">{{ $message }}</div>@enderror

        <hr>
        <form method="POST" action="{{ route('groups.destroy', $group) }}" onsubmit="return confirm('Delete this group? It must have no members.')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger btn-sm">Delete Group</button>
        </form>
    </div>
</div>
@endsection
