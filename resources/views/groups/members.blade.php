@extends('layouts.admin')
@section('content')
@php $transitive = $transitive ?? false; @endphp
<div class="card">
    <x-card-header>
        <strong>{{ $group->name }} — {{ $transitive ? 'All Members' : 'Members' }}</strong>
        <x-slot:actions>
            <a href="{{ route('groups.show', $group) }}" class="btn btn-sm btn-outline-secondary">← Group</a>
        </x-slot:actions>
    </x-card-header>
    <div class="card-body">
        {{-- Direct vs transitive toggle. Direct lists only members enrolled in
             this group; All also includes members inherited from descendants. --}}
        <div class="btn-group btn-group-sm mb-3" role="group">
            <a href="{{ route('groups.members.index', $group) }}"
               class="btn btn-outline-primary {{ $transitive ? '' : 'active' }}">Direct members</a>
            <a href="{{ route('groups.members.all', $group) }}"
               class="btn btn-outline-primary {{ $transitive ? 'active' : '' }}">All members</a>
        </div>

        @unless($transitive)
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
        @else
        <p class="text-muted">
            Includes members inherited from child groups. Inherited members are managed
            in the group they are enrolled in. Use <strong>Direct members</strong> to add or remove here.
        </p>
        @endunless

        <h6 class="mt-4">Find Members</h6>
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-6">
                <input type="text" name="search" class="form-control" placeholder="Search by name or CID" value="{{ $search ?? '' }}">
            </div>
            <div class="col-md-3"><button class="btn btn-outline-secondary w-100">Search</button></div>
            @if($search) <div class="col-auto"><a href="{{ $transitive ? route('groups.members.all', $group) : route('groups.members.index', $group) }}" class="btn btn-outline-secondary">Clear</a></div> @endif
        </form>

        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr>
                    <th>CID</th><th>Name</th><th>Email</th>
                    <th class="{{ $transitive ? '' : 'text-end' }}">{{ $transitive ? 'Source' : '' }}</th>
                </tr></thead>
                <tbody>
                    @forelse($members as $member)
                    <tr>
                        <td>{{ $member->id }}</td>
                        <td>{{ $member->first_name }} {{ $member->last_name }}</td>
                        <td>{{ $member->email }}</td>
                        @if($transitive)
                        @php $src = $memberSources[$member->id] ?? ['direct' => false, 'via' => []]; @endphp
                        <td>
                            @if($src['direct'])
                            <span class="badge bg-success">Direct</span>
                            @endif
                            @foreach($src['via'] as $name)
                            <span class="badge bg-info">via {{ $name }}</span>
                            @endforeach
                        </td>
                        @else
                        <td class="text-end">
                            <form method="POST" action="{{ route('groups.members.destroy', [$group, $member]) }}" onsubmit="return confirm('Remove this member?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Remove</button>
                            </form>
                        </td>
                        @endif
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
