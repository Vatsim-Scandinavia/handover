{{-- Read-only rendering of a group's core fields. Shown to managers, and to
     admins while the edit toggle is off. --}}
@if($group->description)
<p>{{ $group->description }}</p>
@endif

@if($group->tags->isNotEmpty())
<h2 class="section-title">Tags</h2>
<div class="group-chips mb-3">
    @foreach($group->tags as $tag)
    <x-group-tag :tag="$tag->tag" />
    @endforeach
</div>
@endif

@if($group->attributeValues->isNotEmpty())
<h2 class="section-title">Attributes</h2>
<div class="table-scroll mb-3">
    <table class="data-table w-auto">
        <thead><tr><th scope="col">Key</th><th scope="col">Value</th></tr></thead>
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
