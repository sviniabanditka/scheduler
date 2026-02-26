<?php

namespace App\Providers;

use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\CalendarResource;
use App\Filament\Resources\RoomResource;
use App\Filament\Resources\TenantResource;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Filament::serving(function () {
            Filament::registerResources([
                TenantResource::class,
                RoomResource::class,
                CalendarResource::class,
                ActivityResource::class,
            ]);

            Filament::registerNavigationGroups([
                NavigationGroup::make()
                    ->label('SaaS')
                    ->collapsed(false),
                
                NavigationGroup::make()
                    ->label('Розклад')
                    ->collapsed(false),
                
                NavigationGroup::make()
                    ->label('Система')
                    ->collapsed(true),
            ]);
        });
    }
}
