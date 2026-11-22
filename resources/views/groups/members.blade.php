@extends('layouts.admin')
@php $transitive = $transitive ?? false; @endphp
@section('title', ($transitive ? 'All members' : 'Members').' of '.$group->name)
@section('content')
<div class="card">
    <x-page-header :title="$transitive ? 'All members' : 'Members'"
        :crumbs="[['Groups', route('groups.index')], [$group->name, route('groups.show', $group)]]">
        <x-slot:actions>
            {{-- Direct lists only members enrolled in this group; All also
                 includes members inherited from descendants. --}}
            <nav class="btn-group btn-group-sm" aria-label="Members to list">
                <a href="{{ route('groups.members.index', $group) }}" @class(['btn btn-outline-primary', 'active' => ! $transitive])
                   @if(! $transitive) aria-current="page" @endif>Direct members</a>
                <a href="{{ route('groups.members.all', $group) }}" @class(['btn btn-outline-primary', 'active' => $transitive])
                   @if($transitive) aria-current="page" @endif>All members</a>
            </nav>
        </x-slot:actions>
        @if($transitive)
        <x-slot:lead>
            Includes members inherited from child groups. Inherited members are managed
            in the group they are enrolled in. Use <strong>Direct members</strong> to add or remove here.
        </x-slot:lead>
        @endif
    </x-page-header>
    <div class="card-body">
        @unless($transitive)
        <h2 class="section-title">Add member</h2>
        <form method="POST" action="{{ route('groups.members.store', $group) }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-6">
                <label class="form-label" for="member-cid">VATSIM CID</label>
                <input type="number" id="member-cid" name="cid" class="form-control" inputmode="numeric" required>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100">Add member</button>
            </div>
        </form>
        @endunless

        <h2 class="section-title">Find members</h2>
        <form method="GET" class="row g-2 align-items-end mb-3" role="search">
            <div class="col-md-6">
                <label class="form-label" for="member-search">Name or CID</label>
                <input type="search" id="member-search" name="search" class="form-control" value="{{ $search ?? '' }}">
            </div>
            <div class="col-md-3"><button class="btn btn-outline-secondary w-100">Search</button></div>
            @if($search)
            <div class="col-auto"><a href="{{ $transitive ? route('groups.members.all', $group) : route('groups.members.index', $group) }}" class="btn btn-outline-secondary">Clear</a></div>
            @endif
        </form>

        <div class="table-scroll" tabindex="0" role="region" aria-label="Members">
            <table class="data-table">
                <thead><tr>
                    <th scope="col">CID</th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    @if($transitive)
                    <th scope="col">Source</th>
                    @else
                    <th scope="col" class="actions"><span class="visually-hidden">Actions</span></th>
                    @endif
                </tr></thead>
                <tbody>
                    @forelse($members as $member)
                    <tr>
                        <td class="id">{{ $member->id }}</td>
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
                        <td class="actions">
                            <form method="POST" action="{{ route('groups.members.destroy', [$group, $member]) }}" onsubmit="return confirm('Remove this member?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Remove<span class="visually-hidden"> {{ $member->first_name }} {{ $member->last_name }}</span></button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr><td colspan="4" class="empty">No members{{ $search ? ' match your search' : ' yet' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $members->links('pagination::bootstrap-5') }}</div>
    </div>
</div>
@endsection
