@extends('layouts.admin')
@section('content')
<div class="card">
    <x-card-header>
        <strong>Groups</strong>
        @if($isAdmin)
            <span class="badge bg-danger ms-2">System Administrator</span>
        @else
            <span class="badge bg-secondary ms-2">Group Manager</span>
        @endif
        <x-slot:actions>
            @if($isAdmin)
            <a href="{{ route('groups.rules.overview') }}" class="btn btn-sm btn-outline-secondary">Manager Rules</a>
            <a href="{{ route('groups.attributes.index') }}" class="btn btn-sm btn-outline-secondary">Attribute Definitions</a>
            <a href="{{ route('groups.create') }}" class="btn btn-sm btn-primary">New Group</a>
            @endif
        </x-slot:actions>
    </x-card-header>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Slug</th><th>Name</th><th>Tags</th><th>Members</th><th>Admin</th><th></th></tr></thead>
                <tbody>
                    @forelse($groups as $group)
                    <tr>
                        <td><code>{{ $group->slug }}</code></td>
                        <td>{{ $group->name }}</td>
                        <td>{{ $group->tags->pluck('tag')->join(', ') }}</td>
                        <td>{{ $group->members_count }}</td>
                        <td>{{ $group->is_admin_group ? '✓' : '' }}</td>
                        <td class="text-end">
                            <a href="{{ route('groups.show', $group) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-muted p-3">No groups yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($groups->hasPages())
    <div class="card-footer">{{ $groups->links() }}</div>
    @endif
</div>
@endsection
