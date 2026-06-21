<?php
namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupAttributeDefinition;
use App\Models\GroupManagerRuleByAttribute;
use App\Models\GroupManagerRuleByGroup;
use App\Models\GroupManagerRuleByTag;
use App\Services\GroupManagerService;
use Illuminate\Http\Request;

class GroupManagerRuleController extends Controller
{
    public function __construct(private GroupManagerService $service) {}

    public function destroyFromOverview(Request $request, string $type, int $rule)
    {
        $this->requireAdmin($request);

        match ($type) {
            'group'     => GroupManagerRuleByGroup::findOrFail($rule)->delete(),
            'tag'       => GroupManagerRuleByTag::findOrFail($rule)->delete(),
            'attribute' => GroupManagerRuleByAttribute::findOrFail($rule)->delete(),
            default     => abort(404),
        };

        $this->service->incrementCacheVersion();
        return back()->with('success', 'Rule removed.');
    }

    public function overview()
    {
        $request = request();
        $this->requireAdmin($request);

        $groupRules = GroupManagerRuleByGroup::with(['managerGroup', 'targetGroup'])->get();
        $tagRules   = GroupManagerRuleByTag::with('managerGroup')->get();
        $attrRules  = GroupManagerRuleByAttribute::with('managerGroup')->get();

        $rules = collect()
            ->merge($groupRules->map(fn($r) => ['manager' => $r->managerGroup, 'type' => 'group',     'rule' => $r]))
            ->merge($tagRules->map(fn($r)   => ['manager' => $r->managerGroup, 'type' => 'tag',       'rule' => $r]))
            ->merge($attrRules->map(fn($r)  => ['manager' => $r->managerGroup, 'type' => 'attribute', 'rule' => $r]))
            ->filter(fn($e) => $e['manager'] !== null)
            ->sortBy('manager.name')
            ->values();

        return view('groups.rules-overview', compact('rules'));
    }

    public function index()
    {
        $request = request();
        $this->requireAdmin($request);

        $slug = $request->route('group');
        $group = $slug instanceof Group ? $slug : Group::where('slug', $slug)->firstOrFail();
        $group->load('tags', 'attributeValues.definition', 'targetedByGroupRules.managerGroup');

        $allGroups = Group::orderBy('name')->get();
        $definitions = GroupAttributeDefinition::orderBy('key')->get();

        $tagRules = $group->tags->isEmpty()
            ? collect()
            : GroupManagerRuleByTag::with('managerGroup')
                ->whereIn('target_tag', $group->tags->pluck('tag')->toArray())
                ->get();

        $attrRules = GroupManagerRuleByAttribute::with('managerGroup')
            ->get()
            ->filter(fn ($r) => $group->attributeValues()
                ->where('value', $r->target_attribute_value)
                ->whereHas('definition', fn ($q) => $q->where('key', $r->target_attribute_key))
                ->exists()
            );

        return view('groups.rules', compact('group', 'allGroups', 'definitions', 'tagRules', 'attrRules'));
    }

    public function store(Request $request, Group $group)
    {
        $this->requireAdmin($request);
        $type = $request->validate(['type' => 'required|in:group,tag,attribute'])['type'];

        match ($type) {
            'group'     => $this->storeGroupRule($request, $group),
            'tag'       => $this->storeTagRule($request, $group),
            'attribute' => $this->storeAttributeRule($request, $group),
        };

        $this->service->incrementCacheVersion();
        return back()->with('success', 'Rule added.');
    }

    public function destroy(Request $request, Group $group, string $type, int $rule)
    {
        $this->requireAdmin($request);

        match ($type) {
            'group' => GroupManagerRuleByGroup::where('id', $rule)
                ->where('target_group_id', $group->id)
                ->firstOrFail()
                ->delete(),
            'tag' => tap(GroupManagerRuleByTag::findOrFail($rule), function ($r) use ($group) {
                abort_unless($group->tags()->where('tag', $r->target_tag)->exists(), 404);
            })->delete(),
            'attribute' => tap(GroupManagerRuleByAttribute::findOrFail($rule), function ($r) use ($group) {
                abort_unless(
                    $group->attributeValues()
                        ->whereHas('definition', fn ($q) => $q->where('key', $r->target_attribute_key))
                        ->where('value', $r->target_attribute_value)
                        ->exists(),
                    404
                );
            })->delete(),
            default => abort(404),
        };

        $this->service->incrementCacheVersion();
        return back()->with('success', 'Rule removed.');
    }

    private function storeGroupRule(Request $request, Group $group): void
    {
        $data = $request->validate([
            'manager_group_id' => ['required', 'uuid', 'exists:groups,id'],
        ]);
        GroupManagerRuleByGroup::firstOrCreate([
            'manager_group_id' => $data['manager_group_id'],
            'target_group_id'  => $group->id,
        ]);
    }

    private function storeTagRule(Request $request, Group $group): void
    {
        $data = $request->validate([
            'manager_group_id' => ['required', 'uuid', 'exists:groups,id'],
            'target_tag'       => ['required', 'regex:/^[a-z0-9-]+$/', 'max:32'],
        ]);
        GroupManagerRuleByTag::firstOrCreate([
            'manager_group_id' => $data['manager_group_id'],
            'target_tag'       => $data['target_tag'],
        ]);
    }

    private function storeAttributeRule(Request $request, Group $group): void
    {
        $data = $request->validate([
            'manager_group_id'       => ['required', 'uuid', 'exists:groups,id'],
            'target_attribute_key'   => ['required', 'exists:group_attribute_definitions,key'],
            'target_attribute_value' => ['required', 'string', 'max:255'],
        ]);
        GroupManagerRuleByAttribute::firstOrCreate([
            'manager_group_id'       => $data['manager_group_id'],
            'target_attribute_key'   => $data['target_attribute_key'],
            'target_attribute_value' => $data['target_attribute_value'],
        ]);
    }

    private function requireAdmin(Request $request): void
    {
        if (!$request->attributes->get('is_group_admin')) {
            abort(403);
        }
    }
}
