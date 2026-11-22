@extends('layouts.admin')
@section('title', 'Groups')
@use('App\Support\GroupSearch\Term')
@use('App\Support\GroupSearch\TermKind')
@section('content')
@php
    // Links keep the current search, grouping and sort, with the given changes.
    $link = fn (array $changes) => route('groups.index', array_filter(
        array_merge(['q' => $query->toString() ?: null, 'group' => $groupBy?->key, 'sort' => $sort === 'name' ? null : $sort], $changes),
        fn ($v) => $v !== null && $v !== '',
    ));
    $withTerm = fn (Term $term) => $link(['q' => $query->with($term)]);
    $n = $groups->count();
@endphp
<div class="card groups-overview">
    <x-page-header title="Groups">
        @if($isAdmin)
            <span class="badge bg-danger">System administrator</span>
        @else
            <span class="badge bg-secondary">Group manager</span>
        @endif
        <x-slot:actions>
            @if($isAdmin)
            <a href="{{ route('groups.rules.overview') }}" class="btn btn-sm btn-outline-secondary">Manager rules</a>
            <a href="{{ route('groups.attributes.index') }}" class="btn btn-sm btn-outline-secondary">Attribute definitions</a>
            <a href="{{ route('groups.create') }}" class="btn btn-sm btn-primary">New group</a>
            @endif
        </x-slot:actions>
    </x-page-header>
    <div class="card-body">
        @if($total === 0)
            <p class="empty-state">No groups yet.@if($isAdmin) <a href="{{ route('groups.create') }}">Create the first group</a>.@endif</p>
        @else
        <form method="GET" action="{{ route('groups.index') }}" role="search">
            <div class="search d-flex gap-2">
                <div class="input-group">
                    <label class="visually-hidden" for="groups-q">Find groups</label>
                    <input id="groups-q" type="search" name="q" value="{{ $query->toString() }}" class="form-control"
                        placeholder="tag:training area=norway mentors" autocomplete="off" spellcheck="false">
                    {{-- A dropdown rather than a hover tooltip, so a tap opens it on phones. --}}
                    <button type="button" class="btn search-help-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                        aria-expanded="false" aria-label="How to search" title="How to search">
                        <i class="fa-regular fa-circle-question" aria-hidden="true"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end search-help">
                        <p class="fw-medium mb-2">How to search</p>
                        <dl>
                            <dt>tag:training</dt><dd>Groups tagged training. Add more tags to require all of them.</dd>
                            <dt>area=norway</dt><dd>Groups whose attribute area is norway.</dd>
                            <dt>is:admin</dt><dd>Admin groups only.</dd>
                            <dt>mentors</dt><dd>Any other word matches the name, slug or description.</dd>
                        </dl>
                    </div>
                </div>
                <button class="btn btn-primary">Find</button>
            </div>

            <div class="terms" aria-live="polite">
                <span>{{ $n }} of {{ $total }} {{ Str::plural('group', $total) }}{{ $query->isEmpty() ? '' : ',' }}</span>
                @foreach($query->terms as $term)
                    @php $href = $link(['q' => $query->without($term)]); @endphp
                    @if($term->kind === TermKind::Tag)
                        <x-group-tag :tag="$term->value" :href="$href" removable aria-label="Remove tag {{ $term->value }}">tagged {{ $term->value }}</x-group-tag>
                    @elseif($term->kind === TermKind::Attribute)
                        <x-group-attribute :name="$term->key" :value="$term->value" :href="$href" removable aria-label="Remove {{ $term->key }} = {{ $term->value }}" />
                    @elseif($term->kind === TermKind::Admin)
                        <x-group-tag tag="admin" :href="$href" removable aria-label="Remove admin filter">admin groups only</x-group-tag>
                    @else
                        <x-group-tag :tag="$term->value" :href="$href" removable class="word" aria-label="Remove {{ $term->value }}">“{{ $term->value }}”</x-group-tag>
                    @endif
                @endforeach
            </div>

            @if($suggestions->isNotEmpty())
            <div class="group-chips suggestions" role="group" aria-label="Narrow down">
                @foreach($suggestions as ['term' => $term, 'count' => $count])
                    @if($term->kind === TermKind::Tag)
                        <x-group-tag :tag="$term->value" :href="$withTerm($term)">{{ $term->value }}<span class="count">{{ $count }}</span></x-group-tag>
                    @else
                        <x-group-attribute :name="$term->key" :value="$term->value" :href="$withTerm($term)"><span class="count">{{ $count }}</span></x-group-attribute>
                    @endif
                @endforeach
            </div>
            @endif

            <div class="tools">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    @if($definitions->isNotEmpty())
                    <label for="groups-group">Group by</label>
                    <select id="groups-group" name="group" class="form-select" onchange="this.form.submit()">
                        <option value="">nothing</option>
                        @foreach($definitions as $definition)
                            <option value="{{ $definition->key }}" @selected($groupBy?->is($definition))>{{ $definition->key }}</option>
                        @endforeach
                    </select>
                    @endif
                    <label class="d-md-none" for="groups-sort">Sort by</label>
                    <select id="groups-sort" name="sort" class="form-select d-md-none" onchange="this.form.submit()">
                        <option value="name" @selected($sort === 'name')>name</option>
                        <option value="members" @selected($sort === 'members')>members</option>
                    </select>
                </div>
            </div>
        </form>

        @if($n === 0)
            <p class="empty-state">No groups match <strong>{{ $query->toString() }}</strong>. Remove a term above, or <a href="{{ $link(['q' => null]) }}">clear the search</a>.</p>
        @else
        <table class="groups-table">
            <thead>
                <tr>
                    <th scope="col" class="name-col" @if($sort === 'name') aria-sort="ascending" @endif><a href="{{ $link(['sort' => null]) }}">Name<span aria-hidden="true">{{ $sort === 'name' ? ' ↑' : '' }}</span></a></th>
                    <th scope="col">Tags</th>
                    <th scope="col">Attributes</th>
                    <th scope="col" class="members" @if($sort === 'members') aria-sort="descending" @endif><a href="{{ $link(['sort' => 'members']) }}">Members<span aria-hidden="true">{{ $sort === 'members' ? ' ↓' : '' }}</span></a></th>
                </tr>
            </thead>
            @foreach($sections as $value => $sectionGroups)
            <tbody>
                @if($groupBy)
                <tr class="section">
                    <th colspan="4" scope="rowgroup">
                        @if($value === '')
                            No {{ $groupBy->key }}
                        @else
                            <x-group-attribute :name="$groupBy->key" :value="$value" />
                        @endif
                        <span class="count">{{ $sectionGroups->count() }}</span>
                    </th>
                </tr>
                @endif
                @foreach($sectionGroups as $group)
                @php
                    $attributes = $group->attributeValues
                        ->reject(fn ($av) => $av->definition->key === $groupBy?->key)
                        ->sortBy('definition.key');
                @endphp
                <tr class="group">
                    <td>
                        <a class="name stretched-link" href="{{ route('groups.show', $group) }}">{{ $group->name }}</a>
                        @if($group->is_admin_group) <span class="admin">Admin</span> @endif
                        <div class="slug">{{ $group->slug }}</div>
                    </td>
                    <td @class(['tags', 'is-empty' => $group->tags->isEmpty()])>
                        <div class="group-chips">
                            @foreach($group->tags as $tag)
                                @php $term = Term::tag($tag->tag); @endphp
                                <x-group-tag :tag="$tag->tag" :href="$query->has($term) ? null : $withTerm($term)" />
                            @endforeach
                        </div>
                    </td>
                    <td @class(['attributes', 'is-empty' => $attributes->isEmpty()])>
                        <div class="group-chips">
                            @foreach($attributes as $av)
                                @php $term = Term::attribute($av->definition->key, $av->value); @endphp
                                <x-group-attribute :name="$av->definition->key" :value="$av->value" :href="$query->has($term) ? null : $withTerm($term)" />
                            @endforeach
                        </div>
                    </td>
                    <td class="members">{{ $group->members_count }}<span class="unit"> {{ Str::plural('member', $group->members_count) }}</span></td>
                </tr>
                @endforeach
            </tbody>
            @endforeach
        </table>
        @endif
        @endif
    </div>
</div>
@endsection
