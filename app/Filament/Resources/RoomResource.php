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

    protected static ?string $navigationGroup = 'Schedule';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Forms\Components\TextInput::make('code')->required()->maxLength(20),
                Forms\Components\TextInput::make('title')->required()->maxLength(100),
                Forms\Components\TextInput::make('capacity')->integer()->default(0),
                Forms\Components\Select::make('room_type')
                    ->options([
                        'lecture' => 'Lecture',
                        'lab' => 'Lab',
                        'seminar' => 'Seminar',
                        'pc' => 'PC',
                        'gym' => 'Gym',
                        'other' => 'Other',
                    ])
                    ->default('lecture'),
                Forms\Components\Toggle::make('active')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->searchable(),
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('capacity'),
                Tables\Columns\TextColumn::make('room_type'),
                Tables\Columns\BooleanColumn::make('active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('room_type'),
                Tables\Filters\TernaryFilter::make('active'),
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
