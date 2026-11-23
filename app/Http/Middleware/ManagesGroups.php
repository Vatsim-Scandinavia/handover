<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\GroupManagerService;
use Closure;
use Illuminate\Http\Request;

class ManagesGroups
{
    public function __construct(private readonly GroupManagerService $service) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();
        $isAdmin = $this->service->isAdmin($user);
        $manageableIds = $this->service->manageableGroupIds($user);

        if (! $isAdmin && $manageableIds === []) {
            abort(403, 'You do not have access to group management.');
        }

        $request->attributes->set('is_group_admin', $isAdmin);
        $request->attributes->set('manageable_group_ids', $manageableIds);

        return $next($request);
    }
}
