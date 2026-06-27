<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationContext
{
    public function handle(Request $request, Closure $next): Response
    {
        // Resolve using header X-Organization-Slug
        $slug = $request->header('X-Organization-Slug');

        if ($slug) {
            $org = Organization::where('slug', $slug)->first();
            if ($org) {
                app()->instance('organization_id', $org->id);
                $request->merge(['resolved_organization' => $org]);
                return $next($request);
            }
        }

        // Fallback to authenticated user
        if ($request->user()) {
            app()->instance('organization_id', $request->user()->organization_id);
            return $next($request);
        }

        app()->instance('organization_id', null);
        return $next($request);
    }
}
