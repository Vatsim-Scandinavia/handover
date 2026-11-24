@extends('layouts.admin')
@section('title', 'Members of '.$group->name)
@section('content')
{{-- Everyone who counts as a member: enrolled here, or in a member group.
     Only direct members can be removed here; others are managed in the group
     they are enrolled in. Member groups and Member of are edited by admins. --}}
<div class="card"@if($isAdmin) x-data="{ parentIsAdmin: false }"@endif>
    <x-page-header :title="'Members ('.count($memberSources).')'" :crumbs="[['Groups', route('groups.index')], [$group->name, route('groups.show', $group)]]" />
    <div class="card-body">
        <h2 class="section-title">Add member</h2>
        <form method="POST" action="{{ route('groups.members.store', $group) }}" class="row g-2 align-items-end mb-3">
            @csrf
            <div class="col-md-6">
                <label class="form-label" for="member-cid">VATSIM CID</label>
                <input type="number" id="member-cid" name="cid" class="form-control" inputmode="numeric" required>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100">Add member</button>
            </div>
        </form>

        {{-- ── Nesting, stored as child/parent edges. Side by side on wide
             screens, stacked on narrow ones. --}}
        <hr>
        <div class="row">
            <div class="col-md-6 mb-4 mb-md-0">
                <h2 class="section-title">Member groups</h2>
                <p class="text-muted">Everyone in these groups also counts as a member of {{ $group->name }}.</p>

                @if($group->children->isNotEmpty())
                <ul class="list-group mb-2">
                    @foreach($group->children as $child)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="{{ route('groups.members.index', $child) }}" class="text-decoration-none">{{ $child->name }}</a>
                        @if($isAdmin)
                        <x-remove-button :action="route('groups.children.destroy', ['group' => $group, 'child' => $child->id])"
                            :label="'Remove '.$child->name.' from '.$group->name" />
                        @endif
                    </li>
                    @endforeach
                </ul>
                @else
                <p class="text-muted">None.</p>
                @endif

                @if($isAdmin)
                <form method="POST" action="{{ route('groups.children.store', $group) }}" class="d-flex gap-2 align-items-start">
                    @csrf
                    <select name="child_id" class="form-select form-select-sm" required aria-label="Group to add as a member">
                        <option value="">Add a member group…</option>
                        @foreach($candidates as $candidate)
                        <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-sm btn-outline-primary text-nowrap">Add group</button>
                </form>
                @error('child_id')<div class="text-danger mt-1" role="alert">{{ $message }}</div>@enderror
                @endif
            </div>

            <div class="col-md-6">
                <h2 class="section-title">Member of</h2>
                <p class="text-muted">Members of {{ $group->name }} also count as members of these groups.</p>

                @if($group->parents->isNotEmpty())
                <ul class="list-group mb-2">
                    @foreach($group->parents as $parent)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="{{ route('groups.members.index', $parent) }}" class="text-decoration-none">{{ $parent->name }}</a>
                        @if($isAdmin)
                        <x-remove-button :action="route('groups.parents.destroy', ['group' => $group, 'parent' => $parent->id])"
                            :label="'Remove '.$group->name.' from '.$parent->name" />
                        @endif
                    </li>
                    @endforeach
                </ul>
                @else
                <p class="text-muted">None.</p>
                @endif

                @if($isAdmin)
                <form method="POST" action="{{ route('groups.parents.store', $group) }}" class="d-flex gap-2 align-items-start">
                    @csrf
                    <select name="parent_id" class="form-select form-select-sm" required aria-label="Group to join"
                            x-on:change="parentIsAdmin = $event.target.selectedOptions[0]?.dataset.admin === '1'">
                        <option value="">Add to a group…</option>
                        @foreach($candidates as $candidate)
                        <option value="{{ $candidate->id }}" @if($candidate->is_admin_group) data-admin="1" @endif>{{ $candidate->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-sm btn-outline-primary text-nowrap">Add to group</button>
                </form>
                <div x-show="parentIsAdmin" x-cloak class="alert alert-warning py-1 px-2 mt-2 mb-0" role="status">
                    <i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true"></i>Membership of an admin group through another group <strong>does not grant admin</strong>. Admin access is assigned directly.
                </div>
                @error('parent_id')<div class="text-danger mt-1" role="alert">{{ $message }}</div>@enderror
                @endif
            </div>
        </div>
        <hr>

        <h2 class="section-title">Find members</h2>
        <form method="GET" class="row g-2 align-items-end mb-3" role="search">
            <div class="col-md-6">
                <label class="form-label" for="member-search">Name or CID</label>
                <input type="search" id="member-search" name="search" class="form-control" value="{{ $search ?? '' }}">
            </div>
            <div class="col-md-3"><button class="btn btn-outline-secondary w-100">Search</button></div>
            @if($search)
            <div class="col-auto"><a href="{{ route('groups.members.index', $group) }}" class="btn btn-outline-secondary">Clear</a></div>
            @endif
        </form>

        <div class="table-scroll" tabindex="0" role="region" aria-label="Members">
            <table class="data-table">
                <thead><tr>
                    <th scope="col">CID</th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Source</th>
                    <th scope="col" class="actions"><span class="visually-hidden">Actions</span></th>
                </tr></thead>
                <tbody>
                    @forelse($members as $member)
                    @php $src = $memberSources[$member->id]; @endphp
                    <tr>
                        <td class="id">{{ $member->id }}</td>
                        <td>{{ $member->first_name }} {{ $member->last_name }}</td>
                        <td>{{ $member->email }}</td>
                        <td>
                            @if($src['direct'])
                            <span class="badge bg-success">Direct</span>
                            @endif
                            @foreach($src['via'] as $name)
                            <span class="badge bg-info">via {{ $name }}</span>
                            @endforeach
                        </td>
                        <td class="actions">
                            @if($src['direct'])
                            <x-remove-button :action="route('groups.members.destroy', [$group, $member])"
                                :label="'Remove '.$member->first_name.' '.$member->last_name"
                                confirm="Remove this member?" />
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="empty">No members{{ $search ? ' match your search' : ' yet' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $members->links('pagination::bootstrap-5') }}</div>
    </div>
</div>
@endsection
