{{-- A group tag. With an href it is a link; "removable" adds a × for chips
     that take the tag out of a search. --}}
@props(['tag', 'href' => null, 'removable' => false])
@if($href)
<a href="{{ $href }}" {{ $attributes->class('group-tag') }}>{{ $slot->isEmpty() ? $tag : $slot }}@if($removable)<span class="group-chip-x" aria-hidden="true"></span>@endif</a>
@else
<span {{ $attributes->class('group-tag') }}>{{ $slot->isEmpty() ? $tag : $slot }}</span>
@endif
