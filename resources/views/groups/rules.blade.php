@extends('layouts.admin')
@section('content')
<div class="card">
    <x-card-header>
        <strong>{{ $group->name }} — Manager Rules</strong>
        <x-slot:actions>
            <a href="{{ route('groups.show', $group) }}" class="btn btn-sm btn-outline-secondary">← Group</a>
        </x-slot:actions>
    </x-card-header>
    <div class="card-body">

        <h6>Add Rule</h6>
        <form method="POST" action="{{ route('groups.rules.store', $group) }}" class="row g-2 mb-4">
            @csrf
            <div class="col-md-3">
                <select name="type" class="form-select" id="ruleType" onchange="updateRuleFields()">
                    <option value="group">Specific group</option>
                    <option value="tag">Tag match</option>
                    <option value="attribute">Attribute match</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="manager_group_id" class="form-select">
                    @foreach($allGroups as $g)
                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4" id="ruleFields">
                <input type="hidden" name="target_group_id" value="{{ $group->id }}" id="fieldGroupId">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Add Rule</button>
            </div>
        </form>

        <h6>Rules granting management of this group</h6>
        <div class="table-responsive">
        <table class="table table-sm">
            <thead><tr><th>Type</th><th>Manager Group</th><th>Matches via</th><th></th></tr></thead>
            <tbody>
                @foreach($group->targetedByGroupRules as $rule)
                <tr>
                    <td>Group</td>
                    <td>{{ $rule->managerGroup->name }}</td>
                    <td><em>direct rule</em></td>
                    <td class="text-end">
                        <form method="POST" action="{{ route('groups.rules.destroy', [$group, 'group', $rule->id]) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                        </form>
                    </td>
                </tr>
                @endforeach
                @foreach($tagRules as $rule)
                <tr>
                    <td>Tag</td>
                    <td>{{ $rule->managerGroup->name }}</td>
                    <td>tag = <code>{{ $rule->target_tag }}</code></td>
                    <td class="text-end">
                        <form method="POST" action="{{ route('groups.rules.destroy', [$group, 'tag', $rule->id]) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                        </form>
                    </td>
                </tr>
                @endforeach
                @foreach($attrRules as $rule)
                <tr>
                    <td>Attribute</td>
                    <td>{{ $rule->managerGroup->name }}</td>
                    <td><code>{{ $rule->target_attribute_key }}</code> = <code>{{ $rule->target_attribute_value }}</code></td>
                    <td class="text-end">
                        <form method="POST" action="{{ route('groups.rules.destroy', [$group, 'attribute', $rule->id]) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                        </form>
                    </td>
                </tr>
                @endforeach
                @if($group->targetedByGroupRules->isEmpty() && $tagRules->isEmpty() && $attrRules->isEmpty())
                <tr><td colspan="4" class="text-muted">No rules yet — this group is not managed by anyone.</td></tr>
                @endif
            </tbody>
        </table>
        </div>
    </div>
</div>
<script>
const attrDefs = @json($definitions->map(fn($d) => ['key' => $d->key]));
function updateRuleFields() {
    const type = document.getElementById('ruleType').value;
    const container = document.getElementById('ruleFields');
    if (type === 'group') {
        container.innerHTML = '<input type="hidden" name="target_group_id" value="{{ $group->id }}">';
    } else if (type === 'tag') {
        container.innerHTML = '<input type="text" name="target_tag" class="form-control" placeholder="tag (e.g. vacc)" pattern="[a-z0-9-]+" required>';
    } else {
        const keyOpts = attrDefs.map(d => `<option value="${d.key}">${d.key}</option>`).join('');
        container.innerHTML = `<div class="input-group"><select name="target_attribute_key" class="form-select">${keyOpts}</select><input type="text" name="target_attribute_value" class="form-control" placeholder="value" required></div>`;
    }
}
updateRuleFields();
</script>
@endsection
