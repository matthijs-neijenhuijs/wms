<?php

namespace App\Http\Middleware;

use App\Models\Subdomain;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifySubdomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $centralDomain = config('app.central_domain', 'wms.test');

        // Extract subdomain from host
        $subdomain = str_replace('.'.$centralDomain, '', $host);

        // If accessing central domain, allow only specific routes
        if ($subdomain === $centralDomain || empty($subdomain)) {
            // Block all Filament routes on central domain
            if ($request->is('login') || $request->is('logout') || str_starts_with($request->path(), 'filament')) {
                abort(404);
            }

            return $next($request);
        }

        // Find subdomain in database
        $subdomainModel = Subdomain::where('subdomain', $subdomain)->first();

        if (! $subdomainModel) {
            abort(404, 'Subdomain not found');
        }

        // Store in container as instance
        app()->instance('current_subdomain', $subdomainModel);

        return $next($request);
    }
}
