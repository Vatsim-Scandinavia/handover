<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupAttributeDefinition;
use App\Services\GroupManagerService;
use App\Services\GroupMembershipResolver;
use App\Support\EditMode;
use App\Support\GroupSearch\GroupQuery;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class GroupController extends Controller
{
    public function __construct(
        private readonly GroupManagerService $service,
        private readonly GroupMembershipResolver $resolver,
    ) {}

    /**
     * The overview lists every group the user may see, without pagination, so
     * results can be split into sections by an attribute. Handover holds tens
     * of groups, not thousands.
     */
    public function index(): Factory|View
    {
        $request = request();
        $isAdmin = $request->attributes->get('is_group_admin');

        $visible = Group::query();
        if (! $isAdmin) {
            $visible->whereIn('id', $request->attributes->get('manageable_group_ids', []));
        }
        $total = (clone $visible)->count();

        $q = $request->query('q');
        $query = GroupQuery::parse(is_string($q) ? $q : '');
        $sort = $request->query('sort') === 'members' ? 'members' : 'name';
        $groups = $query->apply($visible)
            ->with('tags', 'attributeValues.definition')
            ->orderBy('name')
            ->get();

        // Counts include everyone in member groups, so they come from the resolver, not SQL.
        $counts = $this->resolver->transitiveMemberCounts($groups);
        foreach ($groups as $g) {
            $g->setAttribute('members_count', $counts[$g->id]);
        }
        if ($sort === 'members') {
            // Stable sort: ties stay in name order.
            $groups = $groups->sortByDesc('members_count')->values();
        }

        $definitions = GroupAttributeDefinition::orderBy('key')->get();
        $groupBy = $definitions->firstWhere('key', $request->query('group'));
        $sections = $this->sections($groups, $groupBy);
        $suggestions = $query->suggestions($groups);

        return view('groups.index', ['groups' => $groups, 'total' => $total, 'query' => $query, 'sort' => $sort, 'definitions' => $definitions, 'groupBy' => $groupBy, 'sections' => $sections, 'suggestions' => $suggestions, 'isAdmin' => $isAdmin]);
    }

    /**
     * Groups in sections by an attribute value, with groups that lack the
     * attribute last. Without an attribute, one section holds them all.
     *
     * @param  Collection<int, Group>  $groups
     * @return Collection<string, Collection<int, Group>>
     */
    private function sections(Collection $groups, ?GroupAttributeDefinition $by): Collection
    {
        if (! $by instanceof GroupAttributeDefinition) {
            return collect(['' => $groups]);
        }

        return $groups->groupBy(fn (Group $g): string => $g->attributeValue($by->key) ?? '')
            ->sortKeysUsing(fn ($a, $b): int => [$a === '', $a] <=> [$b === '', $b]);
    }

    public function create(Request $request): Factory|View
    {
        $this->requireAdmin($request);
        $definitions = GroupAttributeDefinition::orderBy('key')->get();

        return view('groups.create', ['definitions' => $definitions]);
    }

    public function store(Request $request)
    {
        $this->requireAdmin($request);
        $data = $this->validateGroup($request);

        $group = DB::transaction(function () use ($data) {
            $g = Group::create([
                'slug' => $data['slug'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_admin_group' => (bool) ($data['is_admin_group'] ?? false),
            ]);
            $this->syncTags($g, $data['tags'] ?? []);
            $this->syncAttributeValues($g, $data['attributes'] ?? []);

            return $g;
        });

        $this->service->incrementCacheVersion();

        return to_route('groups.show', $group)->with('success', 'Group created.');
    }

    public function show(Request $request, Group $group): Factory|View
    {
        if (! $request->attributes->get('is_group_admin') && ! $this->service->canManage($request->user(), $group)) {
            abort(403);
        }
        $group->load('tags', 'attributeValues.definition');
        $group->setAttribute('members_count', count($this->resolver->transitiveMembers($group)));
        $isAdmin = $request->attributes->get('is_group_admin');
        $grantingRules = $isAdmin ? [] : $this->service->grantingRulesFor($request->user(), $group);
        $editing = EditMode::isOn($request);

        $definitions = collect();
        if ($isAdmin) {
            $definitions = GroupAttributeDefinition::orderBy('key')->get();
        }

        return view('groups.show', ['group' => $group, 'isAdmin' => $isAdmin, 'grantingRules' => $grantingRules, 'editing' => $editing, 'definitions' => $definitions]);
    }

    public function update(Request $request, Group $group)
    {
        $this->requireAdmin($request);
        $data = $this->validateGroup($request, $group->id);

        $oldIsAdmin = $group->is_admin_group;
        $newIsAdmin = (bool) ($data['is_admin_group'] ?? false);

        if ($oldIsAdmin && ! $newIsAdmin) {
            $hasOtherAdminGroup = Group::where('is_admin_group', true)->where('id', '!=', $group->id)->exists();
            if (! $hasOtherAdminGroup) {
                return back()->withErrors(['is_admin_group' => 'Cannot demote the only admin group.']);
            }
        }

        DB::transaction(function () use ($data, $group, $oldIsAdmin, $newIsAdmin, $request): void {
            $group->update([
                'slug' => $data['slug'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_admin_group' => $newIsAdmin,
            ]);

            if ($oldIsAdmin !== $newIsAdmin) {
                Log::warning('is_admin_group toggled', [
                    'group_id' => $group->id,
                    'group_slug' => $group->slug,
                    'changed_by' => $request->user()->id,
                    'old_value' => $oldIsAdmin,
                    'new_value' => $newIsAdmin,
                ]);
            }

            $this->syncTags($group, $data['tags'] ?? []);
            $this->syncAttributeValues($group, $data['attributes'] ?? []);
        });

        $this->service->incrementCacheVersion();

        return EditMode::keep(to_route('groups.show', $group)->with('success', 'Group updated.'));
    }

    public function destroy(Request $request, Group $group)
    {
        $this->requireAdmin($request);

        // The foreign key blocks this too. Checking first names the clients in the error.
        $clients = $group->requiringClients()->orderBy('name')->pluck('name');
        if ($clients->isNotEmpty()) {
            $subject = $clients->count() === 1 ? 'the OAuth client '.$clients->first().' requires' : 'the OAuth clients '.$clients->join(', ', ' and ').' require';

            return back()->withErrors(['group' => "Cannot delete: $subject this group."]);
        }

        try {
            $group->delete();
        } catch (QueryException) {
            return back()->withErrors(['group' => 'Cannot delete: group still has members.']);
        }
        $this->service->incrementCacheVersion();

        return to_route('groups.index')->with('success', 'Group deleted.');
    }

    private function validateGroup(Request $request, ?string $groupId = null): array
    {
        $reserved = ['create', 'attributes', 'me', 'search', 'admin', 'edit', 'rules'];
        $slugRule = $groupId
            ? Rule::unique('groups', 'slug')->ignore($groupId, 'id')
            : Rule::unique('groups', 'slug');

        return $request->validate([
            'slug' => ['required', 'regex:/^[a-z0-9-]+$/', 'max:64', $slugRule, Rule::notIn($reserved)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_admin_group' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'regex:/^[a-z0-9-]+$/', 'max:32'],
            'attributes' => ['nullable', 'array'],
            'attributes.*' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function syncTags(Group $group, array $tags): void
    {
        $group->tags()->delete();
        foreach (array_values(array_unique(array_filter($tags))) as $tag) {
            $group->tags()->create(['tag' => $tag]);
        }
    }

    private function syncAttributeValues(Group $group, array $attributes): void
    {
        foreach ($attributes as $definitionId => $value) {
            if ($value === null || $value === '') {
                $group->attributeValues()->where('attribute_definition_id', $definitionId)->delete();
            } else {
                $group->attributeValues()->updateOrCreate(
                    ['attribute_definition_id' => $definitionId],
                    ['value' => $value]
                );
            }
        }
    }

    private function requireAdmin(Request $request): void
    {
        if (! $request->attributes->get('is_group_admin')) {
            abort(403);
        }
    }
}
