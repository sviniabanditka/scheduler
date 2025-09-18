<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Filament::serving(function () {
            Filament::registerNavigationGroups([
                NavigationGroup::make()
                    ->label('Управління даними')
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
