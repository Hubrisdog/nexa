<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantResolutionMiddleware
{
    /**
     * Handles tenant resolution via hostname.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $tenant = null;

        // 1. Check custom domain mapping first
        $tenant = Tenant::where('custom_domain', $host)->first();

        // 2. Fall back to parsing subdomains
        if (!$tenant) {
            $parts = explode('.', $host);
            
            $isLocalhost = (str_ends_with($host, 'localhost') || str_contains($host, '127.0.0.1'));

            if ($isLocalhost) {
                // E.g. acme.localhost -> parts = ['acme', 'localhost']
                if (count($parts) > 1 && $parts[0] !== '127') {
                    $subdomain = $parts[0];
                    $tenant = Tenant::where('slug', $subdomain)->first();
                }
            } else {
                // E.g. acme.nexa.co -> parts = ['acme', 'nexa', 'co']
                if (count($parts) >= 3) {
                    $subdomain = $parts[0];
                    if (!in_array($subdomain, ['www', 'app', 'api'])) {
                        $tenant = Tenant::where('slug', $subdomain)->first();
                    }
                }
            }
        }

        // 3. Bind resolved tenant context to session and request attributes
        if ($tenant) {
            session(['tenant_id' => $tenant->id]);
            $request->attributes->set('tenant', $tenant);
        }

        return $next($request);
    }
}
