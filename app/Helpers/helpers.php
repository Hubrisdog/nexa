<?php

if (!function_exists('tenant')) {
    /**
     * Resolves the active Tenant context.
     */
    function tenant()
    {
        $tenant = request()->attributes->get('tenant');
        
        if (!$tenant && auth()->check()) {
            $tenant = auth()->user()->tenant;
        }
        
        if (!$tenant && session()->has('tenant_id')) {
            $tenant = \App\Models\Tenant::find(session('tenant_id'));
        }
        
        return $tenant;
    }
}

if (!function_exists('is_demo_mode')) {
    /**
     * Checks if the application is running in Demo Mode.
     */
    function is_demo_mode(): bool
    {
        return \App\Helpers\Demo::active();
    }
}
