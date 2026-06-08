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
                // Handle Vercel deployments (e.g. app-name.vercel.app is main, acme.app-name.vercel.app is workspace)
                if (str_ends_with($host, 'vercel.app')) {
                    if (count($parts) >= 4) {
                        $subdomain = $parts[0];
                        if (!in_array($subdomain, ['www', 'app', 'api'])) {
                            $tenant = Tenant::where('slug', $subdomain)->first();
                        }
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
        }

        // 3. Fall back to query parameter (useful for Vercel preview iframes)
        if (!$tenant && $request->has('tenant')) {
            $tenant = Tenant::where('slug', $request->query('tenant'))->first();
        }

        // 4. Fall back to session
        if (!$tenant && session() && session()->has('tenant_id')) {
            $tenant = Tenant::find(session('tenant_id'));
        }

        // 5. Bind resolved tenant context to session and request attributes
        if ($tenant) {
            session(['tenant_id' => $tenant->id]);
            $request->attributes->set('tenant', $tenant);
        }

        return $next($request);
    }
}
