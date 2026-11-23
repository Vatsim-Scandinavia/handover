<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GroupAttributeDefinition;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class GroupAttributeDefinitionController extends Controller
{
    public function index(): Factory|View
    {
        $this->requireAdmin(request());
        $definitions = GroupAttributeDefinition::orderBy('key')->get();

        return view('groups.attributes', ['definitions' => $definitions]);
    }

    public function store(Request $request)
    {
        $this->requireAdmin($request);
        $data = $request->validate([
            'key' => ['required', 'regex:/^[a-z0-9_-]+$/', 'max:32', 'unique:group_attribute_definitions,key'],
            'label' => ['required', 'string', 'max:255'],
        ]);
        GroupAttributeDefinition::create($data);

        return to_route('groups.attributes.index')->with('success', 'Attribute definition created.');
    }

    public function update(Request $request, GroupAttributeDefinition $definition)
    {
        $this->requireAdmin($request);
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
        ]);
        $definition->update($data);

        return to_route('groups.attributes.index')->with('success', 'Attribute definition updated.');
    }

    public function destroy(Request $request, GroupAttributeDefinition $definition)
    {
        $this->requireAdmin($request);
        try {
            $definition->delete();
        } catch (QueryException) {
            return back()->withErrors(['definition' => 'Cannot delete: referenced by existing values or rules.']);
        }

        return to_route('groups.attributes.index')->with('success', 'Attribute definition deleted.');
    }

    private function requireAdmin(Request $request): void
    {
        if (! $request->attributes->get('is_group_admin')) {
            abort(403);
        }
    }
}
