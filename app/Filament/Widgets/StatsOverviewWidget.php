<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use App\Models\Group;
use App\Models\Room;
use App\Models\ScheduleAssignment;
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
        $totalTeachers = Teacher::count();
        $totalActivities = Activity::count();
        $totalRooms = Room::where('active', true)->count();
        
        return [
            Stat::make('Групи', $totalGroups)
                ->description('Активних груп')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
                
            Stat::make('Викладачі', $totalTeachers)
                ->description('Викладачів у системі')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
                
            Stat::make('Активності', $totalActivities)
                ->description('Занять для розкладу')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('warning'),
                
            Stat::make('Аудиторії', $totalRooms)
                ->description('Активних аудиторій')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success'),
        ];
    }
}
