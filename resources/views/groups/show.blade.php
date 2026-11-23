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

        {{-- ── Nesting: parents and children, side by side on wide screens,
             stacked on narrow ones (col-md-6 collapses below the md breakpoint). --}}
        <hr>
        <div class="row">
            {{-- Parent groups --}}
            <div class="col-md-6 mb-4 mb-md-0">
                <h2 class="section-title">Parent groups</h2>
                <p class="text-muted">This group and its members are inherited members of its parents.</p>

                @if($group->parents->isNotEmpty())
                <ul class="list-group mb-2">
                    @foreach($group->parents as $parent)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="{{ route('groups.show', $parent) }}" class="text-decoration-none">{{ $parent->name }}</a>
                        @if($isAdmin)
                        <form method="POST" action="{{ route('groups.parents.destroy', ['group' => $group, 'parent' => $parent->id]) }}" class="d-inline ms-2">
                            @csrf @method('DELETE')
                            <button x-show="editing" x-cloak class="btn btn-link btn-sm text-danger p-0">remove<span class="visually-hidden"> {{ $parent->name }}</span></button>
                        </form>
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
                    <select @disabled(!$editing) :disabled="!editing" name="parent_id" class="form-select form-select-sm" required aria-label="Parent group to add"
                            x-on:change="parentIsAdmin = $event.target.selectedOptions[0]?.dataset.admin === '1'">
                        <option value="">Add a parent group…</option>
                        @foreach($candidateParents as $candidate)
                        <option value="{{ $candidate->id }}" @if($candidate->is_admin_group) data-admin="1" @endif>{{ $candidate->name }}</option>
                        @endforeach
                    </select>
                    <button @disabled(!$editing) :disabled="!editing" class="btn btn-sm btn-outline-primary text-nowrap">Add parent</button>
                </form>
                <div x-show="parentIsAdmin" x-cloak class="alert alert-warning py-1 px-2 mt-2 mb-0" role="status">
                    <i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true"></i>Nesting under an admin group <strong>does not grant admin</strong>. Admin access is assigned directly.
                </div>
                @error('parent_id')<div class="text-danger mt-1" role="alert">{{ $message }}</div>@enderror
                @endif
            </div>

            {{-- Child groups --}}
            <div class="col-md-6">
                <h2 class="section-title">Child groups</h2>
                <p class="text-muted">These groups and their members are inherited members of this group.</p>

                @if($group->children->isNotEmpty())
                <ul class="list-group mb-2">
                    @foreach($group->children as $child)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="{{ route('groups.show', $child) }}" class="text-decoration-none">{{ $child->name }}</a>
                        @if($isAdmin)
                        <form method="POST" action="{{ route('groups.children.destroy', ['group' => $group, 'child' => $child->id]) }}" class="d-inline ms-2">
                            @csrf @method('DELETE')
                            <button x-show="editing" x-cloak class="btn btn-link btn-sm text-danger p-0">remove<span class="visually-hidden"> {{ $child->name }}</span></button>
                        </form>
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
                    <select @disabled(!$editing) :disabled="!editing" name="child_id" class="form-select form-select-sm" required aria-label="Child group to add">
                        <option value="">Add a child group…</option>
                        @foreach($candidateChildren as $candidate)
                        <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                        @endforeach
                    </select>
                    <button @disabled(!$editing) :disabled="!editing" class="btn btn-sm btn-outline-primary text-nowrap">Add child</button>
                </form>
                @error('child_id')<div class="text-danger mt-1" role="alert">{{ $message }}</div>@enderror
                @endif
            </div>
        </div>

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
