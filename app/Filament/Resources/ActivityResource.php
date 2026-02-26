<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use App\Models\Activity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Schedule';

    public static function canAccess(): bool
    {
        return auth()->user()->isPlanner();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('subject_id')
                    ->relationship('subject', 'name')
                    ->required(),
                Forms\Components\Select::make('calendar_id')
                    ->relationship('calendar', 'name')
                    ->required(),
                Forms\Components\TextInput::make('title')->maxLength(200),
                Forms\Components\Select::make('activity_type')
                    ->options([
                        'lecture' => 'Лекція',
                        'lab' => 'Лабораторна',
                        'seminar' => 'Семінар',
                        'practice' => 'Практика',
                        'pc' => 'Комп\'ютерний клас',
                    ])
                    ->default('lecture'),
                Forms\Components\TextInput::make('duration_slots')->integer()->default(1),
                Forms\Components\TextInput::make('required_slots_per_period')->integer()->default(1),
                Forms\Components\Select::make('groups')
                    ->relationship('groups', 'name')
                    ->multiple()
                    ->preload(),
                Forms\Components\Select::make('teachers')
                    ->relationship('teachers', 'name')
                    ->multiple()
                    ->preload(),
                Forms\Components\Textarea::make('notes'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('subject.name')->searchable(),
                Tables\Columns\TextColumn::make('activity_type'),
                Tables\Columns\TextColumn::make('duration_slots'),
                Tables\Columns\TextColumn::make('required_slots_per_period'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('activity_type'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
            'create' => Pages\CreateActivity::route('/create'),
            'edit' => Pages\EditActivity::route('/{record}/edit'),
        ];
    }
}
