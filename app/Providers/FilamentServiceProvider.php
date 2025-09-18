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
                    ->label('Управление данными')
                    ->collapsed(false),
                
                NavigationGroup::make()
                    ->label('Расписание')
                    ->collapsed(false),
                
                NavigationGroup::make()
                    ->label('Система')
                    ->collapsed(true),
            ]);
        });
    }
}
