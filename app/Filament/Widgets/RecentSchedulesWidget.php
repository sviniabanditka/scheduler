<?php

namespace App\Filament\Widgets;

use App\Models\ScheduleAssignment;
use App\Models\ScheduleVersion;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentSchedulesWidget extends BaseWidget
{
    protected static ?string $heading = 'Останні версії розкладу';
    
    protected static ?int $sort = 4;
    
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                ScheduleVersion::query()
                    ->with(['calendar', 'creator'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Назва')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('calendar.name')
                    ->label('Календар')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'warning',
                        'published' => 'success',
                        'archived' => 'gray',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Створив')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('assignments_count')
                    ->counts('assignments')
                    ->label('Занять')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false);
    }
}
