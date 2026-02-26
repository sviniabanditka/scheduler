<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantGlobalScope implements Scope
{
    protected static bool $applying = false;

    public function apply(Builder $builder, Model $model): void
    {
        // Prevent infinite recursion: auth()->user() queries User model which triggers this scope again
        if (static::$applying) {
            return;
        }

        $tenantId = null;

        try {
            if (app()->bound('tenant') && $tenant = app('tenant')) {
                $tenantId = $tenant->id;
            }
        } catch (\Exception $e) {
            // ignore
        }

        if (!$tenantId) {
            try {
                static::$applying = true;
                if (auth()->check() && auth()->user()->tenant_id) {
                    $tenantId = auth()->user()->tenant_id;
                }
            } catch (\Exception $e) {
                // ignore
            } finally {
                static::$applying = false;
            }
        }

        if ($tenantId) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }
}

trait TenantScope
{
    public function scopeTenant(Builder $query, ?string $tenantId = null): Builder
    {
        $tenantId = $tenantId ?? $this->getTenantId();
        
        if (!$tenantId) {
            return $query;
        }
        
        return $query->where('tenant_id', $tenantId);
    }

    protected function getTenantId(): ?string
    {
        try {
            if (app()->bound('tenant') && $tenant = app('tenant')) {
                return $tenant->id;
            }
        } catch (\Exception $e) {
            // ignore
        }
        
        if (auth()->check() && auth()->user()->tenant_id) {
            return auth()->user()->tenant_id;
        }
        
        return null;
    }

    protected static function bootTenantScope(): void
    {
        // Auto-apply global scope for tenant isolation
        static::addGlobalScope(new TenantGlobalScope());

        // Auto-set tenant_id on creation
        static::creating(function ($model) {
            try {
                if (!$model->tenant_id && app()->bound('tenant') && $tenant = app('tenant')) {
                    $model->tenant_id = $tenant->id;
                }
                
                if (!$model->tenant_id && auth()->check() && auth()->user()->tenant_id) {
                    $model->tenant_id = auth()->user()->tenant_id;
                }
            } catch (\Exception $e) {
                // ignore if tenant service not available
            }
        });
    }
}
