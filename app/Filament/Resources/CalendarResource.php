<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CalendarResource\Pages;
use App\Models\Calendar;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CalendarResource extends Resource
{
    protected static ?string $model = Calendar::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Schedule';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Forms\Components\TextInput::make('name')->required()->maxLength(120),
                Forms\Components\DatePicker::make('start_date')->required(),
                Forms\Components\DatePicker::make('end_date')->required(),
                Forms\Components\TextInput::make('weeks')->integer()->default(16),
                Forms\Components\TextInput::make('days_per_week')->integer()->default(6),
                Forms\Components\TextInput::make('slots_per_day')->integer()->default(6),
                Forms\Components\TextInput::make('slot_duration_minutes')->integer()->default(90),
                Forms\Components\TextInput::make('break_duration_minutes')->integer()->default(10),
                Forms\Components\Toggle::make('parity_enabled')->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('start_date')->date(),
                Tables\Columns\TextColumn::make('end_date')->date(),
                Tables\Columns\TextColumn::make('weeks'),
                Tables\Columns\BooleanColumn::make('parity_enabled'),
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
            'index' => Pages\ListCalendars::route('/'),
            'create' => Pages\CreateCalendar::route('/create'),
            'edit' => Pages\EditCalendar::route('/{record}/edit'),
        ];
    }
}
