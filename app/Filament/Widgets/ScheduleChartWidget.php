<?php

namespace App\Filament\Widgets;

use App\Models\ScheduleAssignment;
use App\Models\ScheduleVersion;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ScheduleChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Розподіл занять по днях тижня';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';
    
    protected function getData(): array
    {
        $dayNames = [
            1 => 'Понеділок',
            2 => 'Вівторок', 
            3 => 'Середа',
            4 => 'Четвер',
            5 => "П'ятниця",
            6 => 'Субота',
            7 => 'Неділя'
        ];
        
        // Get the latest published version
        $latestVersion = ScheduleVersion::where('status', 'published')
            ->latest('published_at')
            ->first();
        
        $labels = [];
        $data = [];
        
        if ($latestVersion) {
            $scheduleData = ScheduleAssignment::where('schedule_version_id', $latestVersion->id)
                ->select('day_of_week', DB::raw('count(*) as count'))
                ->groupBy('day_of_week')
                ->orderBy('day_of_week')
                ->get();
            
            foreach ($dayNames as $dayNumber => $dayName) {
                $labels[] = $dayName;
                $count = $scheduleData->where('day_of_week', $dayNumber)->first()?->count ?? 0;
                $data[] = $count;
            }
        } else {
            foreach ($dayNames as $dayName) {
                $labels[] = $dayName;
                $data[] = 0;
            }
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Кількість занять',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                        'rgba(6, 182, 212, 0.8)',
                    ],
                    'borderColor' => [
                        'rgba(59, 130, 246, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(239, 68, 68, 1)',
                        'rgba(139, 92, 246, 1)',
                        'rgba(236, 72, 153, 1)',
                        'rgba(6, 182, 212, 1)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }
    
    protected function getType(): string
    {
        return 'bar';
    }
    
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
