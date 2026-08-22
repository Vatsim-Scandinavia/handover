{{-- Read-only rendering of a group's core fields. Shown to managers, and to
     admins while the edit toggle is off. --}}
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
