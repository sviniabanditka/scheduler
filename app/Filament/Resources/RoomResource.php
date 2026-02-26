<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomResource\Pages;
use App\Models\Room;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Аудиторії';

    protected static ?string $modelLabel = 'Аудиторія';

    protected static ?string $pluralModelLabel = 'Аудиторії';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationGroup = 'Розклад';

    public static function canAccess(): bool
    {
        return auth()->user()->isPlanner();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tenant_id')
                    ->label('Університет')
                    ->relationship('tenant', 'name')
                    ->required(),

                Forms\Components\TextInput::make('code')
                    ->label('Код аудиторії')
                    ->required()
                    ->maxLength(20),

                Forms\Components\TextInput::make('title')
                    ->label('Назва')
                    ->required()
                    ->maxLength(100),

                Forms\Components\TextInput::make('capacity')
                    ->label('Місткість')
                    ->integer()
                    ->default(0),

                Forms\Components\Select::make('room_type')
                    ->label('Тип аудиторії')
                    ->options([
                        'lecture' => 'Лекційна',
                        'lab' => 'Лабораторна',
                        'seminar' => 'Семінарська',
                        'pc' => 'Комп\'ютерний клас',
                        'gym' => 'Спортивна',
                        'other' => 'Інша',
                    ])
                    ->default('lecture'),

                Forms\Components\Toggle::make('active')
                    ->label('Активна')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Код')
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Назва')
                    ->searchable(),

                Tables\Columns\TextColumn::make('capacity')
                    ->label('Місткість'),

                Tables\Columns\TextColumn::make('room_type')
                    ->label('Тип')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'lecture' => 'Лекційна',
                        'lab' => 'Лабораторна',
                        'seminar' => 'Семінарська',
                        'pc' => 'Комп\'ютерний клас',
                        'gym' => 'Спортивна',
                        'other' => 'Інша',
                        default => $state,
                    }),

                Tables\Columns\BooleanColumn::make('active')
                    ->label('Активна'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('room_type')
                    ->label('Тип аудиторії')
                    ->options([
                        'lecture' => 'Лекційна',
                        'lab' => 'Лабораторна',
                        'seminar' => 'Семінарська',
                        'pc' => 'Комп\'ютерний клас',
                        'gym' => 'Спортивна',
                        'other' => 'Інша',
                    ]),

                Tables\Filters\TernaryFilter::make('active')
                    ->label('Активна'),
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
            'index' => Pages\ListRooms::route('/'),
            'create' => Pages\CreateRoom::route('/create'),
            'edit' => Pages\EditRoom::route('/{record}/edit'),
        ];
    }
}
