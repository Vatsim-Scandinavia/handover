{{-- Group strips. Inherited groups nest under the group they come through.
     Expects $groups (this level) and $children (group id => its children). --}}
<ul @class(['strips', 'strips-nested' => $nested ?? false])>
    @foreach($groups as $group)
        <li>
            <div @class(['strip', 'strip-inherited' => $nested ?? false])>{{ $group->name }}</div>
            @if($children->has($group->id))
                @include('parts.group-strips', ['groups' => $children[$group->id], 'nested' => true])
            @endif
        </li>
    @endforeach
</ul>
