@extends('layouts.admin')
@section('title', 'Manager rules for '.$group->name)
@section('content')
<div class="card">
    <x-page-header title="Manager rules" :crumbs="[['Groups', route('groups.index')], [$group->name, route('groups.show', $group)]]">
        <x-slot:lead>Members of a manager group can manage {{ $group->name }}.</x-slot:lead>
    </x-page-header>
    <div class="card-body">
        <h2 class="section-title">Add rule</h2>
        <form method="POST" action="{{ route('groups.rules.store', $group) }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-3">
                <label class="form-label" for="ruleType">Type</label>
                <select name="type" class="form-select" id="ruleType" onchange="updateRuleFields()">
                    <option value="group">Specific group</option>
                    <option value="tag">Tag match</option>
                    <option value="attribute">Attribute match</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="ruleManager">Manager group</label>
                <select name="manager_group_id" class="form-select" id="ruleManager">
                    @foreach($allGroups as $g)
                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4" id="ruleFields"></div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Add rule</button>
            </div>
        </form>
        {{-- The target field for each rule type; updateRuleFields swaps them in. --}}
        <template id="ruleTarget-group">
            <label class="form-label" for="ruleTarget">Manages</label>
            <input type="text" id="ruleTarget" class="form-control bg-body-tertiary" value="{{ $group->name }}" readonly>
            <input type="hidden" name="target_group_id" value="{{ $group->id }}">
        </template>
        <template id="ruleTarget-tag">
            <label class="form-label" for="ruleTarget">Tag</label>
            <input type="text" id="ruleTarget" name="target_tag" class="form-control" placeholder="vacc" pattern="[a-z0-9-]+" required>
        </template>
        <template id="ruleTarget-attribute">
            <span class="form-label d-block" id="ruleTargetLabel">Attribute and value</span>
            <div class="input-group" role="group" aria-labelledby="ruleTargetLabel">
                <select name="target_attribute_key" class="form-select" aria-label="Attribute">
                    @foreach($definitions as $definition)
                    <option value="{{ $definition->key }}">{{ $definition->key }}</option>
                    @endforeach
                </select>
                <input type="text" name="target_attribute_value" class="form-control" placeholder="value" aria-label="Value" required>
            </div>
        </template>

        <h2 class="section-title">Rules for this group</h2>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr>
                <th scope="col">Manager group</th>
                <th scope="col">Type</th>
                <th scope="col">Matches via</th>
                <th scope="col" class="actions"><span class="visually-hidden">Actions</span></th>
            </tr></thead>
            <tbody>
                @foreach($group->targetedByGroupRules as $rule)
                <tr>
                    <td>{{ $rule->managerGroup->name }}</td>
                    <td>Specific group</td>
                    <td>Direct rule</td>
                    <td class="actions">
                        <form method="POST" action="{{ route('groups.rules.destroy', [$group, 'group', $rule->id]) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Remove<span class="visually-hidden"> rule for {{ $rule->managerGroup->name }}</span></button>
                        </form>
                    </td>
                </tr>
                @endforeach
                @foreach($tagRules as $rule)
                <tr>
                    <td>{{ $rule->managerGroup->name }}</td>
                    <td>Tag match</td>
                    <td>Tagged <code>{{ $rule->target_tag }}</code></td>
                    <td class="actions">
                        <form method="POST" action="{{ route('groups.rules.destroy', [$group, 'tag', $rule->id]) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Remove<span class="visually-hidden"> rule for {{ $rule->managerGroup->name }}</span></button>
                        </form>
                    </td>
                </tr>
                @endforeach
                @foreach($attrRules as $rule)
                <tr>
                    <td>{{ $rule->managerGroup->name }}</td>
                    <td>Attribute match</td>
                    <td><code>{{ $rule->target_attribute_key }}</code> = <code>{{ $rule->target_attribute_value }}</code></td>
                    <td class="actions">
                        <form method="POST" action="{{ route('groups.rules.destroy', [$group, 'attribute', $rule->id]) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Remove<span class="visually-hidden"> rule for {{ $rule->managerGroup->name }}</span></button>
                        </form>
                    </td>
                </tr>
                @endforeach
                @if($group->targetedByGroupRules->isEmpty() && $tagRules->isEmpty() && $attrRules->isEmpty())
                <tr><td colspan="4" class="empty">No rules yet, so only system administrators can manage this group.</td></tr>
                @endif
            </tbody>
        </table>
        </div>
    </div>
</div>
<script>
function updateRuleFields() {
    const type = document.getElementById('ruleType').value;
    const target = document.getElementById('ruleTarget-' + type).content.cloneNode(true);
    document.getElementById('ruleFields').replaceChildren(target);
}
updateRuleFields();
</script>
@endsection
