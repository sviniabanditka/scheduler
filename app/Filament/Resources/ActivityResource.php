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

    protected static ?string $navigationLabel = 'Заняття';

    protected static ?string $modelLabel = 'Заняття';

    protected static ?string $pluralModelLabel = 'Заняття';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = 'Розклад';

    public static function canAccess(): bool
    {
        return auth()->user()->isPlanner();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('subject_id')
                    ->label('Предмет')
                    ->relationship('subject', 'name')
                    ->required(),

                Forms\Components\Select::make('calendar_id')
                    ->label('Календар')
                    ->relationship('calendar', 'name')
                    ->required(),

                Forms\Components\TextInput::make('title')
                    ->label('Назва')
                    ->maxLength(200),

                Forms\Components\Select::make('activity_type')
                    ->label('Тип заняття')
                    ->options([
                        'lecture' => 'Лекція',
                        'lab' => 'Лабораторна',
                        'seminar' => 'Семінар',
                        'practice' => 'Практика',
                        'pc' => 'Комп\'ютерний клас',
                    ])
                    ->default('lecture'),

                Forms\Components\TextInput::make('duration_slots')
                    ->label('Тривалість (пари)')
                    ->integer()
                    ->default(1),

                Forms\Components\TextInput::make('required_slots_per_period')
                    ->label('Потрібно пар/період')
                    ->integer()
                    ->default(1),

                Forms\Components\Select::make('groups')
                    ->label('Групи')
                    ->relationship('groups', 'name')
                    ->multiple()
                    ->preload(),

                Forms\Components\Select::make('teachers')
                    ->label('Викладачі')
                    ->relationship('teachers', 'name')
                    ->multiple()
                    ->preload(),

                Forms\Components\Textarea::make('notes')
                    ->label('Нотатки'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Назва')
                    ->searchable(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Предмет')
                    ->searchable(),

                Tables\Columns\TextColumn::make('activity_type')
                    ->label('Тип')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'lecture' => 'Лекція',
                        'lab' => 'Лабораторна',
                        'seminar' => 'Семінар',
                        'practice' => 'Практика',
                        'pc' => 'Комп\'ютерний клас',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('duration_slots')
                    ->label('Тривалість'),

                Tables\Columns\TextColumn::make('required_slots_per_period')
                    ->label('Пар/період'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('activity_type')
                    ->label('Тип заняття')
                    ->options([
                        'lecture' => 'Лекція',
                        'lab' => 'Лабораторна',
                        'seminar' => 'Семінар',
                        'practice' => 'Практика',
                        'pc' => 'Комп\'ютерний клас',
                    ]),
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
