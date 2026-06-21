<?php
namespace App\Http\Middleware;

use App\Services\GroupManagerService;
use Closure;
use Illuminate\Http\Request;

class ManagesGroups
{
    public function __construct(private GroupManagerService $service) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();
        $isAdmin = $this->service->isAdmin($user);
        $manageableIds = $this->service->manageableGroupIds($user);

        if (!$isAdmin && empty($manageableIds)) {
            abort(403, 'You do not have access to group management.');
        }

        $request->attributes->set('is_group_admin', $isAdmin);
        $request->attributes->set('manageable_group_ids', $manageableIds);

        return $next($request);
    }
}
