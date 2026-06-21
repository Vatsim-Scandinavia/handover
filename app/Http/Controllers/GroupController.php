<?php
namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupAttributeDefinition;
use App\Services\GroupManagerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class GroupController extends Controller
{
    public function __construct(private GroupManagerService $service) {}

    public function index()
    {
        $request = request();
        $isAdmin = $request->attributes->get('is_group_admin');
        if ($isAdmin) {
            $groups = Group::withCount('members')->with('tags')->orderBy('name')->paginate(25);
        } else {
            $ids = $request->attributes->get('manageable_group_ids', []);
            $groups = Group::withCount('members')->with('tags')->whereIn('id', $ids)->orderBy('name')->paginate(25);
        }
        return view('groups.index', compact('groups', 'isAdmin'));
    }

    public function create(Request $request)
    {
        $this->requireAdmin($request);
        $definitions = GroupAttributeDefinition::orderBy('key')->get();
        return view('groups.create', compact('definitions'));
    }

    public function store(Request $request)
    {
        $this->requireAdmin($request);
        $data = $this->validateGroup($request);

        $group = DB::transaction(function () use ($data) {
            $g = Group::create([
                'slug'           => $data['slug'],
                'name'           => $data['name'],
                'description'    => $data['description'] ?? null,
                'is_admin_group' => (bool) ($data['is_admin_group'] ?? false),
            ]);
            $this->syncTags($g, $data['tags'] ?? []);
            $this->syncAttributeValues($g, $data['attributes'] ?? []);
            return $g;
        });

        $this->service->incrementCacheVersion();

        return redirect()->route('groups.show', $group)->with('success', 'Group created.');
    }

    public function show(Request $request, Group $group)
    {
        if (!$request->attributes->get('is_group_admin') && !$this->service->canManage($request->user(), $group)) {
            abort(403);
        }
        $group->load('tags', 'attributeValues.definition');
        $definitions = GroupAttributeDefinition::orderBy('key')->get();
        $isAdmin = $request->attributes->get('is_group_admin');
        $grantingRules = $isAdmin ? [] : $this->service->grantingRulesFor($request->user(), $group);
        return view('groups.show', compact('group', 'definitions', 'isAdmin', 'grantingRules'));
    }

    public function edit(Request $request, Group $group)
    {
        $this->requireAdmin($request);
        $group->load('tags', 'attributeValues.definition');
        $definitions = GroupAttributeDefinition::orderBy('key')->get();
        return view('groups.edit', compact('group', 'definitions'));
    }

    public function update(Request $request, Group $group)
    {
        $this->requireAdmin($request);
        $data = $this->validateGroup($request, $group->id);

        $oldIsAdmin = $group->is_admin_group;
        $newIsAdmin = (bool) ($data['is_admin_group'] ?? false);

        if ($oldIsAdmin && !$newIsAdmin) {
            $hasOtherAdminGroup = Group::where('is_admin_group', true)->where('id', '!=', $group->id)->exists();
            if (!$hasOtherAdminGroup) {
                return back()->withErrors(['is_admin_group' => 'Cannot demote the only admin group.']);
            }
        }

        DB::transaction(function () use ($data, $group, $oldIsAdmin, $newIsAdmin, $request) {
            $group->update([
                'slug'           => $data['slug'],
                'name'           => $data['name'],
                'description'    => $data['description'] ?? null,
                'is_admin_group' => $newIsAdmin,
            ]);

            if ($oldIsAdmin !== $newIsAdmin) {
                Log::warning('is_admin_group toggled', [
                    'group_id'   => $group->id,
                    'group_slug' => $group->slug,
                    'changed_by' => $request->user()->id,
                    'old_value'  => $oldIsAdmin,
                    'new_value'  => $newIsAdmin,
                ]);
            }

            $this->syncTags($group, $data['tags'] ?? []);
            $this->syncAttributeValues($group, $data['attributes'] ?? []);
        });

        $this->service->incrementCacheVersion();

        return redirect()->route('groups.show', $group)->with('success', 'Group updated.');
    }

    public function destroy(Request $request, Group $group)
    {
        $this->requireAdmin($request);
        try {
            $group->delete();
        } catch (\Illuminate\Database\QueryException) {
            return back()->withErrors(['group' => 'Cannot delete: group still has members.']);
        }
        $this->service->incrementCacheVersion();
        return redirect()->route('groups.index')->with('success', 'Group deleted.');
    }

    private function validateGroup(Request $request, ?string $groupId = null): array
    {
        $reserved = ['create', 'attributes', 'me', 'search', 'admin', 'edit', 'rules'];
        $slugRule = $groupId
            ? Rule::unique('groups', 'slug')->ignore($groupId, 'id')
            : Rule::unique('groups', 'slug');

        return $request->validate([
            'slug'           => ['required', 'regex:/^[a-z0-9-]+$/', 'max:64', $slugRule, Rule::notIn($reserved)],
            'name'           => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string', 'max:1000'],
            'is_admin_group' => ['nullable', 'boolean'],
            'tags'           => ['nullable', 'array'],
            'tags.*'         => ['string', 'regex:/^[a-z0-9-]+$/', 'max:32'],
            'attributes'     => ['nullable', 'array'],
            'attributes.*'   => ['nullable', 'string', 'max:255'],
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
        if (!$request->attributes->get('is_group_admin')) {
            abort(403);
        }
    }
}
