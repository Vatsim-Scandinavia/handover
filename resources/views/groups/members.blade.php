@extends('layouts.admin')
@section('title', 'Members of '.$group->name)
@section('content')
{{-- Everyone who counts as a member: enrolled here, or in a member group.
     Only direct members can be removed here; others are managed in the group
     they are enrolled in. Member groups are edited by admins; Member of is
     on the group page. --}}
<div class="card">
    <x-page-header :title="'Members ('.count($memberSources).')'" :crumbs="[['Groups', route('groups.index')], [$group->name, route('groups.show', $group)]]" />
    <div class="card-body">
        <section class="page-section">
        <h2 class="section-title">Add member</h2>
        <form method="POST" action="{{ route('groups.members.store', $group) }}">
            @csrf
            <x-picker id="member-query" name="cid" label="Name or CID" placeholder="Name or CID"
                button="Add member" button-class="btn-primary"
                :url="route('groups.members.candidates', $group)" :min-chars="2" accept-digits
                disabled-note="already a member" invalid-hint="Pick someone from the list, or enter a CID." />
        </form>
        </section>

        <section class="page-section">
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
        <form method="POST" action="{{ route('groups.children.store', $group) }}">
            @csrf
            <x-picker id="member-group" name="child_id" label="Group to add as a member" placeholder="Find a group"
                button="Add group" :options="$memberGroupOptions" invalid-hint="Pick a group from the list." />
        </form>
        @error('child_id')<div class="text-danger mt-1" role="alert">{{ $message }}</div>@enderror
        @endif
        </section>

        <section class="page-section">
        <h2 class="section-title">Find members</h2>
        <form method="GET" class="picker d-flex gap-2 mb-3" role="search">
            <label class="visually-hidden" for="member-search">Name or CID</label>
            <input type="search" id="member-search" name="search" class="form-control" placeholder="Name or CID" value="{{ $search ?? '' }}">
            <button class="btn btn-outline-secondary">Search</button>
            @if($search)
            <a href="{{ route('groups.members.index', $group) }}" class="btn btn-outline-secondary">Clear</a>
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
        </section>
    </div>
</div>
@endsection
