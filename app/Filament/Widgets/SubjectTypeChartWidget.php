<?php

namespace App\Filament\Widgets;

use App\Models\Schedule;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SubjectTypeChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Розподіл занять по типах';
    
    protected static ?int $sort = 5;
    
    protected function getData(): array
    {
        $typeData = Schedule::join('subjects', 'schedules.subject_id', '=', 'subjects.id')
            ->select('subjects.type', DB::raw('count(*) as count'))
            ->groupBy('subjects.type')
            ->get();
        
        $labels = [];
        $data = [];
        $colors = [];
        
        $typeNames = [
            'lecture' => 'Лекції',
            'practice' => 'Практичні',
            'lab' => 'Лабораторні',
            'seminar' => 'Семінари',
        ];
        
        $typeColors = [
            'lecture' => ['rgba(59, 130, 246, 0.8)', 'rgba(59, 130, 246, 1)'],
            'practice' => ['rgba(16, 185, 129, 0.8)', 'rgba(16, 185, 129, 1)'],
            'lab' => ['rgba(245, 158, 11, 0.8)', 'rgba(245, 158, 11, 1)'],
            'seminar' => ['rgba(239, 68, 68, 0.8)', 'rgba(239, 68, 68, 1)'],
        ];
        
        foreach ($typeData as $item) {
            $typeName = $typeNames[$item->type] ?? ucfirst($item->type);
            $labels[] = $typeName;
            $data[] = $item->count;
            
            $colorSet = $typeColors[$item->type] ?? ['rgba(156, 163, 175, 0.8)', 'rgba(156, 163, 175, 1)'];
            $colors['background'][] = $colorSet[0];
            $colors['border'][] = $colorSet[1];
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Кількість занять',
                    'data' => $data,
                    'backgroundColor' => $colors['background'],
                    'borderColor' => $colors['border'],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }
    
    protected function getType(): string
    {
        return 'doughnut';
    }
    
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
