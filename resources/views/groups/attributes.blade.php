@extends('layouts.admin')
@section('title', 'Attribute definitions')
@section('content')
<div class="card">
    <x-page-header title="Attribute definitions" :crumbs="[['Groups', route('groups.index')]]">
        <x-slot:lead>The attributes a group can have. Searches, grouping and manager rules all use them.</x-slot:lead>
    </x-page-header>
    <div class="card-body">
        <h2 class="section-title">Add definition</h2>
        <form method="POST" action="{{ route('groups.attributes.store') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label" for="definition-key">Key</label>
                <input type="text" id="definition-key" name="key" class="form-control" placeholder="region" value="{{ old('key') }}"
                    required pattern="[a-z0-9_-]+" aria-describedby="definition-key-help">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="definition-label">Label</label>
                <input type="text" id="definition-label" name="label" class="form-control" placeholder="Region" value="{{ old('label') }}" required>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Add</button>
            </div>
            <div class="col-12 form-text mt-1" id="definition-key-help">Keys use lowercase letters, numbers, hyphens and underscores.</div>
        </form>

        <h2 class="section-title">Definitions</h2>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr>
                <th scope="col">Key</th>
                <th scope="col">Label</th>
                <th scope="col" class="actions"><span class="visually-hidden">Actions</span></th>
            </tr></thead>
            {{-- One tbody per definition, so its row and its edit row share state. --}}
            @forelse($definitions as $def)
            <tbody x-data="{ editing: false }">
                <tr x-show="!editing">
                    <td><code>{{ $def->key }}</code></td>
                    <td>{{ $def->label }}</td>
                    <td class="actions">
                        <button type="button" class="btn btn-sm btn-outline-secondary" x-ref="edit"
                            x-on:click="editing = true; $nextTick(() => $refs.label.focus())">Edit<span class="visually-hidden"> {{ $def->key }}</span></button>
                        <form method="POST" action="{{ route('groups.attributes.destroy', $def) }}"
                            onsubmit="return confirm('Delete this definition?')" class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete<span class="visually-hidden"> {{ $def->key }}</span></button>
                        </form>
                    </td>
                </tr>
                <tr x-show="editing" x-cloak>
                    <td><code>{{ $def->key }}</code></td>
                    <td colspan="2">
                        <form method="POST" action="{{ route('groups.attributes.update', $def) }}" class="d-flex gap-2"
                            x-on:keydown.escape="editing = false; $nextTick(() => $refs.edit.focus())">
                            @csrf @method('PATCH')
                            <label class="visually-hidden" for="definition-label-{{ $def->id }}">Label for {{ $def->key }}</label>
                            <input type="text" id="definition-label-{{ $def->id }}" name="label" class="form-control form-control-sm" x-ref="label"
                                value="{{ $def->label }}" required maxlength="255">
                            <button class="btn btn-sm btn-primary">Save</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                x-on:click="editing = false; $nextTick(() => $refs.edit.focus())">Cancel</button>
                        </form>
                    </td>
                </tr>
            </tbody>
            @empty
            <tbody>
                <tr><td colspan="3" class="empty">No attribute definitions yet.</td></tr>
            </tbody>
            @endforelse
        </table>
        </div>
    </div>
</div>
@endsection
