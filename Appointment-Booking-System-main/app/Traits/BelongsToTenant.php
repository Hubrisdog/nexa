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

    /**
     * Resolves the active Tenant ID.
     */
    protected static function resolveTenantId()
    {
        // 1. Resolve from authenticated user
        if (Auth::check() && Auth::user()->tenant_id) {
            return Auth::user()->tenant_id;
        }

        // 2. Resolve from session if set
        if (session()->has('tenant_id')) {
            return session('tenant_id');
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
