<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class QuickAccessWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-access-widget';
    
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';
}
