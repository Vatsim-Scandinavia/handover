@extends('layouts.admin')
@section('content')
<div class="card">
    <x-card-header>
        <div class="d-flex align-items-center gap-2">
            <div>
                <strong>{{ $group->name }}</strong>
                <div><code class="text-muted">{{ $group->slug }}</code></div>
            </div>
            @if($group->is_admin_group)
                <span class="badge bg-danger">Admin</span>
            @endif
        </div>
        <x-slot:actions>
            <a href="{{ route('groups.index') }}" class="btn btn-sm btn-outline-secondary">← Groups</a>
            <a href="{{ route('groups.members.index', $group) }}" class="btn btn-sm btn-outline-primary">Members</a>
            @if($isAdmin)
            <a href="{{ route('groups.rules.index', $group) }}" class="btn btn-sm btn-outline-secondary">Rules</a>
            <a href="{{ route('groups.edit', $group) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
            @endif
        </x-slot:actions>
    </x-card-header>
    <div class="card-body">
        @if($group->description)
        <p>{{ $group->description }}</p>
        @endif

        @if($group->tags->isNotEmpty())
        <p><strong>Tags:</strong>
            @foreach($group->tags as $tag)
            <span class="badge bg-secondary">{{ $tag->tag }}</span>
            @endforeach
        </p>
        @endif

        @if($group->attributeValues->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-sm w-auto">
                <thead><tr><th>Attribute</th><th>Value</th></tr></thead>
                <tbody>
                    @foreach($group->attributeValues as $av)
                    <tr>
                        <td><code>{{ $av->definition->key }}</code></td>
                        <td>{{ $av->value }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @if($isAdmin)
        <div class="mt-3 text-muted" style="font-size:13px">
            <span class="badge bg-danger">System Administrator</span> You have full administrative access.
        </div>
        @elseif(!empty($grantingRules))
        <div class="mt-3 text-muted" style="font-size:13px">
            <span class="badge bg-secondary">Group Manager</span> <strong>Your access is granted by:</strong>
            <ul class="mb-0">@foreach($grantingRules as $rule)<li>{{ $rule }}</li>@endforeach</ul>
        </div>
        @endif
    </div>
</div>
@endsection
