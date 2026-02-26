<?php

namespace App\Providers;

use App\Services\TenantManager;
use Illuminate\Support\ServiceProvider;

class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantManager::class, function ($app) {
            return new TenantManager();
        });

        $this->app->instance('tenant', null);
    }

    public function boot(): void
    {
        //
    }
}
