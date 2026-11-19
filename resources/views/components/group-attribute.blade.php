{{-- A group attribute as a two-part chip: the key, then the value. With an
     href it is a link; "removable" adds a × for chips in a search. --}}
@props(['name', 'value', 'href' => null, 'removable' => false])
@if($href)
<a href="{{ $href }}" {{ $attributes->class('group-attr') }}><span class="k">{{ $name }}</span><span class="v">{{ $value }}{{ $slot }}@if($removable)<span class="group-chip-x" aria-hidden="true"></span>@endif</span></a>
@else
<span {{ $attributes->class('group-attr') }}><span class="k">{{ $name }}</span><span class="v">{{ $value }}{{ $slot }}</span></span>
@endif
