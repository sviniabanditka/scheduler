<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Teacher;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected function getStats(): array
    {
        $totalGroups = Group::count();
        $totalSubjects = Subject::count();
        $totalTeachers = Teacher::count();
        $totalSchedules = Schedule::count();
        
        return [
            Stat::make('Всього груп', $totalGroups)
                ->description('Груп, що навчаються')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
                
            Stat::make('Всього предметів', $totalSubjects)
                ->description('Дисциплін у навчальному плані')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('warning'),
                
            Stat::make('Всього викладачів', $totalTeachers)
                ->description('Викладачів у системі')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
                
            Stat::make('Всього занять', $totalSchedules)
                ->description('Запланованих занять')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),
        ];
    }
}
