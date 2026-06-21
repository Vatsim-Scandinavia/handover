@extends('layouts.admin')
@section('content')
<div class="card">
    <x-card-header>
        <strong>{{ $group->name }} — Members</strong>
        <x-slot:actions>
            <a href="{{ route('groups.show', $group) }}" class="btn btn-sm btn-outline-secondary">← Group</a>
        </x-slot:actions>
    </x-card-header>
    <div class="card-body">
        <h6>Add Member</h6>
        <form method="POST" action="{{ route('groups.members.store', $group) }}" class="row g-2 mb-3">
            @csrf
            <div class="col-md-6">
                <input type="number" name="cid" class="form-control" placeholder="VATSIM CID" required>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100">Add Member</button>
            </div>
        </form>

        <h6 class="mt-4">Find Members</h6>
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-6">
                <input type="text" name="search" class="form-control" placeholder="Search by name or CID" value="{{ $search ?? '' }}">
            </div>
            <div class="col-md-3"><button class="btn btn-outline-secondary w-100">Search</button></div>
            @if($search) <div class="col-auto"><a href="{{ route('groups.members.index', $group) }}" class="btn btn-outline-secondary">Clear</a></div> @endif
        </form>

        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>CID</th><th>Name</th><th>Email</th><th></th></tr></thead>
                <tbody>
                    @forelse($members as $member)
                    <tr>
                        <td>{{ $member->id }}</td>
                        <td>{{ $member->first_name }} {{ $member->last_name }}</td>
                        <td>{{ $member->email }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('groups.members.destroy', [$group, $member]) }}" onsubmit="return confirm('Remove this member?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-muted">No members{{ $search ? ' matching your search' : '' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $members->links() }}
    </div>
</div>
@endsection
