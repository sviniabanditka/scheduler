<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SubjectTypeChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Розподіл активностей по типах';
    
    protected static ?int $sort = 5;
    
    protected int | string | array $columnSpan = 'full';
    
    protected function getData(): array
    {
        $typeData = Activity::select('activity_type', DB::raw('count(*) as count'))
            ->groupBy('activity_type')
            ->get();
        
        $labels = [];
        $data = [];
        $colors = [];
        
        $typeNames = [
            'lecture' => 'Лекції',
            'practice' => 'Практичні',
            'lab' => 'Лабораторні',
            'seminar' => 'Семінари',
            'pc' => 'Комп\'ютерні',
        ];
        
        $typeColors = [
            'lecture' => ['rgba(59, 130, 246, 0.8)', 'rgba(59, 130, 246, 1)'],
            'practice' => ['rgba(16, 185, 129, 0.8)', 'rgba(16, 185, 129, 1)'],
            'lab' => ['rgba(245, 158, 11, 0.8)', 'rgba(245, 158, 11, 1)'],
            'seminar' => ['rgba(239, 68, 68, 0.8)', 'rgba(239, 68, 68, 1)'],
            'pc' => ['rgba(139, 92, 246, 0.8)', 'rgba(139, 92, 246, 1)'],
        ];
        
        foreach ($typeData as $item) {
            $typeName = $typeNames[$item->activity_type] ?? ucfirst($item->activity_type);
            $labels[] = $typeName;
            $data[] = $item->count;
            
            $colorSet = $typeColors[$item->activity_type] ?? ['rgba(156, 163, 175, 0.8)', 'rgba(156, 163, 175, 1)'];
            $colors['background'][] = $colorSet[0];
            $colors['border'][] = $colorSet[1];
        }
        
        if (empty($data)) {
            return [
                'datasets' => [['label' => 'Кількість', 'data' => [0], 'backgroundColor' => ['rgba(156, 163, 175, 0.5)']]],
                'labels' => ['Немає даних'],
            ];
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Кількість активностей',
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
