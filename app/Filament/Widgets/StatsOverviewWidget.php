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
    protected static ?int $sort = 2;
    
    protected function getStats(): array
    {
        $totalCourses = Course::count();
        $totalGroups = Group::count();
        $totalSubjects = Subject::count();
        $totalTeachers = Teacher::count();
        $totalSchedules = Schedule::count();
        
        // Находим самую активную группу (с наибольшим количеством занятий)
        $mostActiveGroup = Group::withCount('schedules')
            ->orderBy('schedules_count', 'desc')
            ->first();
        
        // Находим самого загруженного преподавателя
        $busiestTeacher = Teacher::withCount('schedules')
            ->orderBy('schedules_count', 'desc')
            ->first();
        
        return [
            Stat::make('Всього курсів', $totalCourses)
                ->description('Активних курсів у системі')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),
                
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
                
            Stat::make('Найактивніша група', $mostActiveGroup ? $mostActiveGroup->name : 'Немає даних')
                ->description($mostActiveGroup ? $mostActiveGroup->schedules_count . ' занять' : 'Немає занять')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('warning'),
                
            Stat::make('Найзавантаженіший викладач', $busiestTeacher ? $busiestTeacher->name : 'Немає даних')
                ->description($busiestTeacher ? $busiestTeacher->schedules_count . ' занять' : 'Немає занять')
                ->descriptionIcon('heroicon-m-star')
                ->color('danger'),
        ];
    }
}
