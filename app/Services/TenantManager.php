<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;

class TenantManager
{
    protected ?Tenant $tenant = null;

    public function getTenant(): ?Tenant
    {
        if ($this->tenant) {
            return $this->tenant;
        }

        $tenantId = $this->resolveTenantId();
        
        if (!$tenantId) {
            return null;
        }

        return $this->tenant = Cache::remember(
            "tenant:{$tenantId}",
            3600,
            fn() => Tenant::find($tenantId)
        );
    }

    public function setTenant(Tenant $tenant): void
    {
        $this->tenant = $tenant;
        app()->instance('tenant', $tenant);
    }

    public function setTenantById(string $tenantId): ?Tenant
    {
        $tenant = Tenant::find($tenantId);
        
        if ($tenant) {
            $this->setTenant($tenant);
        }
        
        return $tenant;
    }

    public function resolveTenantId(): ?string
    {
        if (auth()->check() && auth()->user()->tenant_id) {
            return auth()->user()->tenant_id;
        }

        $host = Request::getHost();
        
        $tenant = Tenant::where('domain', $host)
            ->orWhere('subdomain', $this->extractSubdomain($host))
            ->first();
        
        return $tenant?->id;
    }

    protected function extractSubdomain(string $host): ?string
    {
        $parts = explode('.', $host);
        
        if (count($parts) >= 3) {
            return $parts[0];
        }
        
        return null;
    }

    public function clearTenant(): void
    {
        $this->tenant = null;
        app()->forgetInstance('tenant');
    }

    public function getTenantId(): ?string
    {
        return $this->getTenant()?->id;
    }
}
