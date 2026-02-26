<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Панель керування';

    protected static ?string $title = 'Панель керування';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = 1;

    public function mount(): void
    {
        $user = auth()->user();

        // Redirect teachers to their personal schedule page
        if ($user && $user->isTeacher()) {
            $this->redirect(TeacherSchedule::getUrl());
        }
    }
}
