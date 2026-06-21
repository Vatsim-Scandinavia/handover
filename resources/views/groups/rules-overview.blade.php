@extends('layouts.admin')
@section('content')
<div class="card">
    <x-card-header>
        <strong>Manager Rules</strong>
        <x-slot:actions>
            <a href="{{ route('groups.index') }}" class="btn btn-sm btn-outline-secondary">← Groups</a>
        </x-slot:actions>
    </x-card-header>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Manager Group</th>
                    <th>Type</th>
                    <th>Grants management of</th>
                    <th></th>
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
                    <td class="text-end">
                        @if($entry['type'] === 'group')
                            <a href="{{ route('groups.rules.index', $rule->targetGroup) }}" class="btn btn-sm btn-outline-secondary me-1">Rules page</a>
                        @endif
                        <form method="POST"
                            action="{{ route('groups.rules.overview.destroy', [$entry['type'], $rule->id]) }}"
                            onsubmit="return confirm('Remove this rule?')"
                            class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-muted p-3">No manager rules defined yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
