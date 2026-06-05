<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Resolve active Tenant
        $tenant = null;
        if (auth()->check()) {
            $tenant = auth()->user()->tenant;
        } elseif ($request->has('provider_id')) {
            $provider = User::find($request->input('provider_id'));
            if ($provider) {
                $tenant = $provider->tenant;
            }
        }

        // If no tenant is resolved, allow the request to proceed (e.g., system admin or public pages)
        if (!$tenant) {
            return $next($request);
        }

        $plan = strtolower($tenant->plan ?? 'free');

        // 2. Check Staff Limits (POST /api/users)
        if ($request->is('api/users') && $request->isMethod('POST')) {
            $role = $request->input('role');
            if (in_array($role, ['staff', 'admin'])) {
                $staffCount = User::where('tenant_id', $tenant->id)
                    ->whereIn('role', ['staff', 'admin'])
                    ->count();

                $staffLimit = app()->environment('local') ? 100 : 1;
                if ($plan === 'free' && $staffCount >= $staffLimit) {
                    return response()->json([
                        'message' => "Free tier workspace is limited to {$staffLimit} staff member(s). Please upgrade to Pro to add more team members."
                    ], 403);
                }

                if ($plan === 'pro' && $staffCount >= 5) {
                    return response()->json([
                        'message' => 'Pro tier workspace is limited to 5 staff members. Please upgrade to Enterprise to support unlimited team members.'
                    ], 403);
                }
            }
        }

        // 3. Check Monthly Appointment Limits (POST /api/appointments or POST /api/public/book)
        if (($request->is('api/appointments') || $request->is('api/public/book')) && $request->isMethod('POST')) {
            $monthlyCount = Appointment::where('tenant_id', $tenant->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            $appointmentLimit = app()->environment('local') ? 1000 : 20;
            if ($plan === 'free' && $monthlyCount >= $appointmentLimit) {
                return response()->json([
                    'message' => "This workspace has reached its limit of {$appointmentLimit} appointments for the month on the Free plan. Please upgrade to Pro to unlock unlimited scheduling."
                ], 403);
            }
        }

        // 4. Check Enterprise AI features (POST /api/appointments/*/summarize)
        if ($request->is('api/appointments/*/summarize') && $request->isMethod('POST')) {
            if ($plan !== 'enterprise' && !app()->environment('local')) {
                return response()->json([
                    'message' => 'AI Automated Summarization is an Enterprise feature. Please upgrade your subscription.'
                ], 403);
            }
        }

        return $next($request);
    }
}
