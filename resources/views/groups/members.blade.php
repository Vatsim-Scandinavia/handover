@extends('layouts.admin')
@section('title', 'Members of '.$group->name)
@section('content')
{{-- Everyone who counts as a member: enrolled here, or in a member group.
     Only direct members can be removed here; others are managed in the group
     they are enrolled in. Member groups and Member of are edited by admins. --}}
<div class="card"@if($isAdmin) x-data="{ parentIsAdmin: false }"@endif>
    <x-page-header :title="'Members ('.count($memberSources).')'" :crumbs="[['Groups', route('groups.index')], [$group->name, route('groups.show', $group)]]" />
    <div class="card-body">
        <section class="page-section">
        <h2 class="section-title">Add member</h2>
        {{-- Type a name or CID and pick a match. A bare CID can be added
             without picking, for users not yet in the list. --}}
        <form method="POST" action="{{ route('groups.members.store', $group) }}" class="row g-2 align-items-end"
              x-data="{
                  query: '', results: [], open: false, active: -1, picked: null, invalid: false,
                  get cid() { return this.picked?.id ?? (/^\d+$/.test(this.query.trim()) ? this.query.trim() : ''); },
                  async search() {
                      this.picked = null;
                      this.invalid = false;
                      const q = this.query.trim();
                      if (q.length < 2) { this.results = []; this.open = false; return; }
                      const res = await fetch(@js(route('groups.members.candidates', $group)) + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
                      if (q !== this.query.trim()) return;
                      this.results = res.ok ? await res.json() : [];
                      this.active = this.results.findIndex(u => !u.member);
                      this.open = true;
                  },
                  pick(user) {
                      if (!user || user.member) return;
                      this.picked = user;
                      this.query = user.name + ' (' + user.id + ')';
                      this.open = false;
                  },
                  move(step) {
                      // Skip rows for people already in the group; they cannot be picked.
                      const n = this.results.length;
                      for (let i = 1; this.open && i <= n; i++) {
                          const next = (this.active + step * i + n * n) % n;
                          if (!this.results[next].member) { this.active = next; return; }
                      }
                  },
              }"
              x-on:submit="if (!cid) { $event.preventDefault(); invalid = true }">
            @csrf
            <input type="hidden" name="cid" :value="cid">
            <div class="col-md-6 position-relative">
                <label class="form-label" for="member-query">Name or VATSIM CID</label>
                <input type="text" id="member-query" class="form-control" autocomplete="off" required
                       role="combobox" aria-autocomplete="list" aria-controls="member-options"
                       :aria-expanded="open" :aria-activedescendant="open && active >= 0 ? 'member-option-' + active : null"
                       :aria-invalid="invalid" aria-describedby="member-query-hint"
                       x-model="query" x-on:input.debounce.250ms="search()"
                       x-on:keydown.down.prevent="move(1)" x-on:keydown.up.prevent="move(-1)"
                       x-on:keydown.enter="if (open && active >= 0) { $event.preventDefault(); pick(results[active]) }"
                       x-on:keydown.escape="open = false" x-on:blur="open = false">
                <ul id="member-options" role="listbox" class="list-group member-options" x-show="open" x-cloak>
                    <template x-for="(user, i) in results" :key="user.id">
                        <li role="option" :id="'member-option-' + i" :aria-selected="i === active" :aria-disabled="user.member"
                            class="list-group-item list-group-item-action" :class="{ active: i === active, disabled: user.member }"
                            x-on:mousedown.prevent="pick(user)">
                            <span x-text="user.name"></span> <span class="member-option-cid" x-text="user.id"></span>
                            <span x-show="user.member" class="small">· already a member</span>
                        </li>
                    </template>
                    <li x-show="!results.length" class="list-group-item text-muted">No matches. Enter a CID to add someone not listed.</li>
                </ul>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100">Add member</button>
            </div>
            <div id="member-query-hint" class="form-text mt-1" :class="{ 'text-danger': invalid }">
                <span x-show="!invalid">Start typing a name, then pick from the list.</span>
                <span x-show="invalid" x-cloak>Pick a member from the list, or enter a CID.</span>
            </div>
        </form>
        </section>

        {{-- ── Nesting, stored as child/parent edges. Side by side on wide
             screens, stacked on narrow ones. --}}
        <section class="page-section">
        <div class="row">
            <div class="col-md-6 mb-4 mb-md-0">
                <h2 class="section-title">Member groups</h2>
                <p class="text-muted">Everyone in these groups also counts as a member of {{ $group->name }}.</p>

                @if($group->children->isNotEmpty())
                <ul class="list-group mb-2">
                    @foreach($group->children as $child)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="{{ route('groups.members.index', $child) }}" class="text-decoration-none">{{ $child->name }}</a>
                        @if($isAdmin)
                        <x-remove-button :action="route('groups.children.destroy', ['group' => $group, 'child' => $child->id])"
                            :label="'Remove '.$child->name.' from '.$group->name" />
                        @endif
                    </li>
                    @endforeach
                </ul>
                @else
                <p class="text-muted">None.</p>
                @endif

                @if($isAdmin)
                <form method="POST" action="{{ route('groups.children.store', $group) }}" class="d-flex gap-2 align-items-start">
                    @csrf
                    <select name="child_id" class="form-select form-select-sm" required aria-label="Group to add as a member">
                        <option value="">Add a member group…</option>
                        @foreach($candidates as $candidate)
                        <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-sm btn-outline-primary text-nowrap">Add group</button>
                </form>
                @error('child_id')<div class="text-danger mt-1" role="alert">{{ $message }}</div>@enderror
                @endif
            </div>

            <div class="col-md-6">
                <h2 class="section-title">Member of</h2>
                <p class="text-muted">Members of {{ $group->name }} also count as members of these groups.</p>

                @if($group->parents->isNotEmpty())
                <ul class="list-group mb-2">
                    @foreach($group->parents as $parent)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="{{ route('groups.members.index', $parent) }}" class="text-decoration-none">{{ $parent->name }}</a>
                        @if($isAdmin)
                        <x-remove-button :action="route('groups.parents.destroy', ['group' => $group, 'parent' => $parent->id])"
                            :label="'Remove '.$group->name.' from '.$parent->name" />
                        @endif
                    </li>
                    @endforeach
                </ul>
                @else
                <p class="text-muted">None.</p>
                @endif

                @if($isAdmin)
                <form method="POST" action="{{ route('groups.parents.store', $group) }}" class="d-flex gap-2 align-items-start">
                    @csrf
                    <select name="parent_id" class="form-select form-select-sm" required aria-label="Group to join"
                            x-on:change="parentIsAdmin = $event.target.selectedOptions[0]?.dataset.admin === '1'">
                        <option value="">Add to a group…</option>
                        @foreach($candidates as $candidate)
                        <option value="{{ $candidate->id }}" @if($candidate->is_admin_group) data-admin="1" @endif>{{ $candidate->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-sm btn-outline-primary text-nowrap">Add to group</button>
                </form>
                <div x-show="parentIsAdmin" x-cloak class="alert alert-warning py-1 px-2 mt-2 mb-0" role="status">
                    <i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true"></i>Membership of an admin group through another group <strong>does not grant admin</strong>. Admin access is assigned directly.
                </div>
                @error('parent_id')<div class="text-danger mt-1" role="alert">{{ $message }}</div>@enderror
                @endif
            </div>
        </div>
        </section>

        <section class="page-section">
        <h2 class="section-title">Find members</h2>
        <form method="GET" class="row g-2 align-items-end mb-3" role="search">
            <div class="col-md-6">
                <label class="form-label" for="member-search">Name or CID</label>
                <input type="search" id="member-search" name="search" class="form-control" value="{{ $search ?? '' }}">
            </div>
            <div class="col-md-3"><button class="btn btn-outline-secondary w-100">Search</button></div>
            @if($search)
            <div class="col-auto"><a href="{{ route('groups.members.index', $group) }}" class="btn btn-outline-secondary">Clear</a></div>
            @endif
        </form>

        <div class="table-scroll" tabindex="0" role="region" aria-label="Members">
            <table class="data-table">
                <thead><tr>
                    <th scope="col">CID</th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Source</th>
                    <th scope="col" class="actions"><span class="visually-hidden">Actions</span></th>
                </tr></thead>
                <tbody>
                    @forelse($members as $member)
                    @php $src = $memberSources[$member->id]; @endphp
                    <tr>
                        <td class="id">{{ $member->id }}</td>
                        <td>{{ $member->first_name }} {{ $member->last_name }}</td>
                        <td>{{ $member->email }}</td>
                        <td>
                            @if($src['direct'])
                            <span class="badge bg-success">Direct</span>
                            @endif
                            @foreach($src['via'] as $name)
                            <span class="badge bg-info">via {{ $name }}</span>
                            @endforeach
                        </td>
                        <td class="actions">
                            @if($src['direct'])
                            <x-remove-button :action="route('groups.members.destroy', [$group, $member])"
                                :label="'Remove '.$member->first_name.' '.$member->last_name"
                                confirm="Remove this member?" />
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="empty">No members{{ $search ? ' match your search' : ' yet' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $members->links('pagination::bootstrap-5') }}</div>
        </section>
    </div>
</div>
@endsection
