@extends('layouts.admin')
@section('title', $group->name)
@section('content')
{{-- Admins always see the full set of controls; they are disabled until the
     Edit toggle is on, so the layout never changes; only editability does.
     EditMode decides when the toggle starts on. `@disabled(!$editing)` sets
     the initial disabled state server-side (no flash before Alpine), and
     `:disabled="!editing"` keeps it in sync. Managers get no x-data, so they
     are always read-only. --}}
<div class="card"@if($isAdmin) x-data="{ editing: {{ $editing ? 'true' : 'false' }}, parentIsAdmin: false }"@endif>
    <x-page-header :title="$group->name" :crumbs="[['Groups', route('groups.index')]]">
        @if($group->is_admin_group)
            <span class="badge bg-danger">Admin</span>
        @endif
        <x-slot:meta>
            <code>{{ $group->slug }}</code>
            <x-copy-button class="group-id" :value="$group->id" label="Group ID"
                hint="Sent to OIDC clients in the groups claim. Click to copy.">ID</x-copy-button>
        </x-slot:meta>
        <x-slot:actions>
            <a href="{{ route('groups.members.index', $group) }}" class="btn btn-sm btn-outline-primary">Members ({{ $group->members_count }})</a>
            @if($isAdmin)
            <a href="{{ route('groups.rules.index', $group) }}" class="btn btn-sm btn-outline-secondary">Manager rules</a>
            <button type="button" class="btn btn-sm btn-primary" x-on:click="editing = !editing">
                <span x-show="!editing"><span aria-hidden="true">✎ </span>Edit</span>
                <span x-show="editing" x-cloak><span aria-hidden="true">✓ </span>Done</span>
            </button>
            @endif
        </x-slot:actions>
    </x-page-header>
    <div class="card-body">

        <section class="page-section">
        {{-- ── Core fields ─────────────────────────────────────────────── --}}
        @if($isAdmin)
        <form method="POST" action="{{ route('groups.update', $group) }}">
            @csrf @method('PATCH')
            {{-- A disabled <fieldset> natively disables every control inside it,
                 so _form (shared with the create page) stays untouched. --}}
            <fieldset @disabled(!$editing) :disabled="!editing">
                @include('groups._form', ['group' => $group])
                <div class="row">
                    <div class="col-sm-9 offset-sm-3">
                        <button class="btn btn-primary">Save changes</button>
                    </div>
                </div>
            </fieldset>
        </form>
        @else
            @include('groups._overview')
        @endif

        {{-- ── Access context ──────────────────────────────────────────── --}}
        @if($isAdmin)
        <div class="mt-3 text-muted small">
            <span class="badge bg-danger">System Administrator</span> You have full administrative access.
        </div>
        @elseif(!empty($grantingRules))
        <div class="mt-3 text-muted small">
            <span class="badge bg-secondary">Group Manager</span> <strong>Your access is granted by:</strong>
            <ul class="mb-0">@foreach($grantingRules as $rule)<li>{{ $rule }}</li>@endforeach</ul>
        </div>
        @endif
        </section>

        {{-- ── Member of: direct parents, then the groups they lead to.
             Stored as parent edges; only direct ones can be removed. --}}
        <section class="page-section">
        <h2 class="section-title">Member of</h2>
        <p class="text-muted">Members of {{ $group->name }} also count as members of these groups.</p>

        @if($group->parents->isNotEmpty())
        <ul class="list-group mb-2">
            @foreach($group->parents->sortBy('name') as $parent)
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <a href="{{ route('groups.show', $parent) }}" class="text-decoration-none">{{ $parent->name }}</a>
                @if($isAdmin)
                <x-remove-button :action="route('groups.parents.destroy', ['group' => $group, 'parent' => $parent->id])"
                    :label="'Remove '.$group->name.' from '.$parent->name" x-show="editing" x-cloak />
                @endif
            </li>
            @endforeach
            @foreach($indirectMemberOf as $row)
            <li class="list-group-item indirect">
                <a href="{{ route('groups.show', $row['group']) }}" class="text-decoration-none">{{ $row['group']->name }}</a>
                <span class="through">through {{ implode(', ', $row['through']) }}</span>
            </li>
            @endforeach
        </ul>
        @else
        <p class="text-muted">None.</p>
        @endif

        @if($isAdmin)
        <form method="POST" action="{{ route('groups.parents.store', $group) }}"
              x-on:picked="parentIsAdmin = $event.detail?.admin === true">
            @csrf
            <fieldset @disabled(!$editing) :disabled="!editing">
                <x-picker id="member-of" name="parent_id" label="Group to join" placeholder="Find a group"
                    button="Add to group" :options="$memberOfOptions" invalid-hint="Pick a group from the list." />
            </fieldset>
        </form>
        <div x-show="editing && parentIsAdmin" x-cloak class="alert alert-warning py-1 px-2 mt-2 mb-0" role="status">
            <i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true"></i>Membership of an admin group through another group <strong>does not grant admin</strong>. Admin access is assigned directly.
        </div>
        @error('parent_id')<div class="text-danger mt-1" role="alert">{{ $message }}</div>@enderror
        @endif
        </section>

        {{-- ── Danger zone ─────────────────────────────────────────────── --}}
        @if($isAdmin)
        <hr>
        <form method="POST" action="{{ route('groups.destroy', $group) }}" onsubmit="return confirm('Delete this group? It must have no members.')">
            @csrf @method('DELETE')
            <button @disabled(!$editing) :disabled="!editing" class="btn btn-outline-danger btn-sm">Delete group</button>
        </form>
        @endif
    </div>
</div>
@endsection
