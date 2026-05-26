<div class="mb-3">
    <label class="form-label">Slug</label>
    <input type="text" name="slug" class="form-control" value="{{ old('slug', $group?->slug) }}" required pattern="[a-z0-9-]+" maxlength="64">
    <div class="form-text">Lowercase letters, numbers, hyphens only. Not guaranteed stable.</div>
</div>
<div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $group?->name) }}" required maxlength="255">
</div>
<div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" maxlength="1000">{{ old('description', $group?->description) }}</textarea>
</div>
<div class="mb-3">
    <label class="form-label">Tags <small class="text-muted">(comma-separated, e.g. vacc,eur)</small></label>
    <input type="text" name="tags_input" class="form-control" value="{{ old('tags_input', $group?->tags->pluck('tag')->join(',')) }}" id="tagsInput">
    <div id="tagsHidden"></div>
</div>
@if($definitions->isNotEmpty())
<div class="mb-3">
    <label class="form-label">Attributes</label>
    @foreach($definitions as $def)
    <div class="input-group mb-1">
        <span class="input-group-text" style="min-width:120px"><code>{{ $def->key }}</code></span>
        <input type="text" name="attributes[{{ $def->id }}]" class="form-control"
               value="{{ old("attributes.{$def->id}", $group?->attributeValues->firstWhere('attribute_definition_id', $def->id)?->value) }}"
               placeholder="{{ $def->label }}" maxlength="255">
    </div>
    @endforeach
</div>
@endif
<div class="mb-3 form-check">
    <input type="hidden" name="is_admin_group" value="0">
    <input type="checkbox" name="is_admin_group" value="1" class="form-check-input" id="isAdminGroup"
           {{ old('is_admin_group', $group?->is_admin_group) ? 'checked' : '' }}>
    <label class="form-check-label" for="isAdminGroup">Admin group <small class="text-muted">(members get full system access)</small></label>
</div>
<script>
    document.getElementById('tagsInput').addEventListener('input', function() {
        const container = document.getElementById('tagsHidden');
        container.innerHTML = '';
        this.value.split(',').map(t => t.trim().toLowerCase()).filter(Boolean).forEach(tag => {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'tags[]'; input.value = tag;
            container.appendChild(input);
        });
    });
    document.getElementById('tagsInput').dispatchEvent(new Event('input'));
</script>
