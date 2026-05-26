@extends('layouts.admin')

@section('content')
<div class="card mb-4">
    <x-card-header>
        <strong>Attribute Definitions</strong>
        <x-slot:actions>
            <a href="{{ route('groups.index') }}" class="btn btn-sm btn-outline-secondary">← Groups</a>
        </x-slot:actions>
    </x-card-header>
    <div class="card-body">
        <form method="POST" action="{{ route('groups.attributes.store') }}" class="row g-2 mb-4">
            @csrf
            <div class="col-md-4">
                <input type="text" name="key" class="form-control" placeholder="key (e.g. region)" value="{{ old('key') }}" required pattern="[a-z0-9_-]+">
            </div>
            <div class="col-md-6">
                <input type="text" name="label" class="form-control" placeholder="Label (e.g. Region)" value="{{ old('label') }}" required>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Add</button>
            </div>
        </form>

        <div class="table-responsive">
        <table class="table table-sm">
            <thead><tr><th>Key</th><th>Label</th><th></th></tr></thead>
            <tbody>
                @forelse($definitions as $def)
                <tr id="row-{{ $def->id }}">
                    <td><code>{{ $def->key }}</code></td>
                    <td>{{ $def->label }}</td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary me-1"
                            onclick="toggleEdit({{ $def->id }})">Edit</button>
                        <form method="POST" action="{{ route('groups.attributes.destroy', $def) }}"
                            onsubmit="return confirm('Delete this definition?')" class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <tr id="edit-{{ $def->id }}" style="display:none">
                    <td><code>{{ $def->key }}</code></td>
                    <td colspan="2">
                        <form method="POST" action="{{ route('groups.attributes.update', $def) }}"
                            class="d-flex gap-2">
                            @csrf @method('PATCH')
                            <input type="text" name="label" class="form-control form-control-sm"
                                value="{{ $def->label }}" required maxlength="255">
                            <button class="btn btn-sm btn-primary">Save</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                onclick="toggleEdit({{ $def->id }})">Cancel</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-muted">No attribute definitions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
<script>
function toggleEdit(id) {
    document.getElementById('row-' + id).style.display =
        document.getElementById('row-' + id).style.display === 'none' ? '' : 'none';
    document.getElementById('edit-' + id).style.display =
        document.getElementById('edit-' + id).style.display === 'none' ? '' : 'none';
}
</script>
@endsection
