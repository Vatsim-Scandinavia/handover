<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\GroupManagerService;
use Closure;
use Illuminate\Http\Request;

class EnsureUserIsAdmin
{
    public function __construct(private readonly GroupManagerService $service) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $this->service->isAdmin($request->user())) {
            abort(403, 'You do not have administrator access.');
        }

        return $next($request);
    }
}
