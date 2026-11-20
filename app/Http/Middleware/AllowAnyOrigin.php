<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets browser code on any origin read the response. Only for public metadata
 * that carries no credentials.
 */
class AllowAnyOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request)->header('Access-Control-Allow-Origin', '*');
    }
}
