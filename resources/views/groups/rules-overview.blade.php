@extends('layouts.admin')
@section('title', 'Manager rules')
@section('content')
<div class="card">
    <x-page-header title="Manager rules" :crumbs="[['Groups', route('groups.index')]]">
        <x-slot:lead>Each rule lets the members of one group manage other groups.</x-slot:lead>
    </x-page-header>
    <div class="card-body">
        <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Manager group</th>
                    <th scope="col">Type</th>
                    <th scope="col">Grants management of</th>
                    <th scope="col" class="actions"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rules as $entry)
                @php $rule = $entry['rule']; @endphp
                <tr>
                    <td>
                        <a href="{{ route('groups.show', $entry['manager']) }}">{{ $entry['manager']->name }}</a>
                    </td>
                    <td>
                        @if($entry['type'] === 'group') Specific group
                        @elseif($entry['type'] === 'tag') Tag match
                        @else Attribute match
                        @endif
                    </td>
                    <td>
                        @if($entry['type'] === 'group')
                            <a href="{{ route('groups.show', $rule->targetGroup) }}">{{ $rule->targetGroup->name }}</a>
                        @elseif($entry['type'] === 'tag')
                            Groups tagged <code>{{ $rule->target_tag }}</code>
                        @else
                            Groups where <code>{{ $rule->target_attribute_key }}</code> = <code>{{ $rule->target_attribute_value }}</code>
                        @endif
                    </td>
                    <td class="actions">
                        @if($entry['type'] === 'group')
                            <a href="{{ route('groups.rules.index', $rule->targetGroup) }}" class="btn btn-sm btn-outline-secondary">Group rules<span class="visually-hidden"> for {{ $rule->targetGroup->name }}</span></a>
                        @endif
                        <form method="POST"
                            action="{{ route('groups.rules.overview.destroy', [$entry['type'], $rule->id]) }}"
                            onsubmit="return confirm('Remove this rule?')"
                            class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Remove<span class="visually-hidden"> rule for {{ $entry['manager']->name }}</span></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="empty">No manager rules yet. Add one from a group's manager rules page.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
