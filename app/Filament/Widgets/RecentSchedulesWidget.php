<?php

namespace App\Filament\Widgets;

use App\Models\Schedule;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentSchedulesWidget extends BaseWidget
{
    protected static ?string $heading = 'Останні додані заняття';
    
    protected static ?int $sort = 4;
    
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Schedule::query()
                    ->with(['subject', 'teacher', 'group'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Предмет')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Викладач')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('group.name')
                    ->label('Група')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('День')
                    ->formatStateUsing(function (int $state): string {
                        $days = [
                            1 => 'Пн',
                            2 => 'Вт', 
                            3 => 'Ср',
                            4 => 'Чт',
                            5 => 'Пт',
                            6 => 'Сб',
                            7 => 'Нд'
                        ];
                        return $days[$state] ?? $state;
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('time_slot')
                    ->label('Час')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('classroom')
                    ->label('Аудиторія')
                    ->placeholder('Не вказано'),
                    
                Tables\Columns\TextColumn::make('week_number')
                    ->label('Тиждень')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Додано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false);
    }
}
