<?php

namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        // Automatically assign tenant_id on creations
        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $model->tenant_id = static::resolveTenantId();
            }
        });

        // Apply global tenant scoping
        static::addGlobalScope('tenant_scope', function (Builder $builder) {
            $tenantId = static::resolveTenantId();
            if ($tenantId !== null) {
                $builder->where($builder->getQuery()->from . '.tenant_id', $tenantId);
            }
        });
    }

    protected static $resolving = false;

    /**
     * Resolves the active Tenant ID.
     */
    protected static function resolveTenantId()
    {
        if (config('app.ignore_tenant_scope') === true) {
            return null;
        }

        if (static::$resolving) {
            return null;
        }

        static::$resolving = true;
        $tenantId = null;

        try {
            if (Auth::hasUser()) {
                $tenantId = Auth::user()->tenant_id;
            } elseif (Auth::check()) {
                $tenantId = Auth::user()->tenant_id;
            }
        } catch (\Throwable $e) {
            // Ignore auth lookup errors during boot
        }

        static::$resolving = false;

        if ($tenantId !== null) {
            return $tenantId;
        }

        // 2. Resolve from session if set
        try {
            if (session() && session()->has('tenant_id')) {
                return session('tenant_id');
            }
        } catch (\Throwable $e) {
            // Ignore session lookup errors
        }

        return null;
    }

    /**
     * Relationship to the Tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
