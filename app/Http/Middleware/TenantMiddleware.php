<?php

namespace App\Http\Middleware;

use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    protected TenantManager $tenantManager;

    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Try to resolve tenant from manager (host/session/etc.)
        $tenant = $this->tenantManager->getTenant();

        // If no tenant yet, try from authenticated user
        if (!$tenant && auth()->check() && auth()->user()->tenant_id) {
            $tenant = \App\Models\Tenant::find(auth()->user()->tenant_id);
        }

        // Set tenant if found — otherwise just pass through
        // (public pages and auth pages don't require a tenant)
        if ($tenant) {
            $this->tenantManager->setTenant($tenant);
        }

        return $next($request);
    }
}
