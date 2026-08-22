<div x-data="{
    slug: @js(old('slug', $group?->slug) ?? ''),
    autoSync: @js(old('slug', $group?->slug) ?? '') === '',
    tagsInput: @js(old('tags_input', $group?->tags->pluck('tag')->join(',')) ?? ''),
    slugify(value) {
        return value.toLowerCase().normalize('NFKD')
            .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 64);
    },
    get tags() {
        return [...new Set(this.tagsInput.split(',').map(t => t.trim().toLowerCase()).filter(Boolean))];
    },
}">
    <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="groupName">Name</label>
        <div class="col-sm-9">
            <input type="text" id="groupName" name="name" class="form-control" value="{{ old('name', $group?->name) }}"
                   @input="autoSync && (slug = slugify($event.target.value))" required maxlength="255">
        </div>
    </div>
    <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="groupSlug">Slug</label>
        <div class="col-sm-9">
            <input type="text" id="groupSlug" name="slug" class="form-control" x-model="slug"
                   @input="autoSync = false" required pattern="[a-z0-9-]+" maxlength="64">
            <div class="form-text">Lowercase letters, numbers, hyphens only. Not guaranteed stable.</div>
        </div>
    </div>
    <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="groupDescription">Description</label>
        <div class="col-sm-9">
            <textarea id="groupDescription" name="description" class="form-control" maxlength="1000">{{ old('description', $group?->description) }}</textarea>
        </div>
    </div>
    <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="tagsInput">Tags <small class="text-muted">(comma-separated, e.g. vacc,eur)</small></label>
        <div class="col-sm-9">
            <input type="text" id="tagsInput" name="tags_input" class="form-control" x-model="tagsInput">
            <template x-for="tag in tags" :key="tag">
                <input type="hidden" name="tags[]" :value="tag">
            </template>
        </div>
    </div>
    @if($definitions->isNotEmpty())
    <div class="row mb-3">
        <label class="col-sm-3 col-form-label">Attributes</label>
        <div class="col-sm-9">
            @foreach($definitions as $def)
            <div class="input-group mb-1">
                <span class="input-group-text" style="min-width:120px"><code>{{ $def->key }}</code></span>
                <input type="text" name="attributes[{{ $def->id }}]" class="form-control"
                       value="{{ old("attributes.{$def->id}", $group?->attributeValues->firstWhere('attribute_definition_id', $def->id)?->value) }}"
                       placeholder="{{ $def->label }}" maxlength="255">
            </div>
            @endforeach
        </div>
    </div>
    @endif
    <div class="row mb-3 align-items-center">
        <span class="col-sm-3 col-form-label">Admin group</span>
        <div class="col-sm-9">
            <div class="form-check">
                <input type="hidden" name="is_admin_group" value="0">
                <input type="checkbox" name="is_admin_group" value="1" class="form-check-input" id="isAdminGroup"
                       {{ old('is_admin_group', $group?->is_admin_group) ? 'checked' : '' }}>
                <label class="form-check-label" for="isAdminGroup">Members get full system access</label>
            </div>
        </div>
    </div>
</div>
