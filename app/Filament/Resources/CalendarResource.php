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

    protected static ?string $navigationLabel = 'Календарі';

    protected static ?string $modelLabel = 'Календар';

    protected static ?string $pluralModelLabel = 'Календарі';

    protected static ?string $navigationGroup = 'Розклад';

    protected static ?int $navigationSort = 6;

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

                Forms\Components\TextInput::make('name')
                    ->label('Назва')
                    ->required()
                    ->maxLength(120),

                Forms\Components\DatePicker::make('start_date')
                    ->label('Дата початку')
                    ->required(),

                Forms\Components\DatePicker::make('end_date')
                    ->label('Дата закінчення')
                    ->required(),

                Forms\Components\TextInput::make('weeks')
                    ->label('Тижнів')
                    ->integer()
                    ->default(16),

                Forms\Components\TextInput::make('days_per_week')
                    ->label('Днів на тиждень')
                    ->integer()
                    ->default(6),

                Forms\Components\TextInput::make('slots_per_day')
                    ->label('Пар на день')
                    ->integer()
                    ->default(6),

                Forms\Components\TextInput::make('slot_duration_minutes')
                    ->label('Тривалість пари (хв)')
                    ->integer()
                    ->default(90),

                Forms\Components\TextInput::make('break_duration_minutes')
                    ->label('Тривалість перерви (хв)')
                    ->integer()
                    ->default(10),

                Forms\Components\Toggle::make('parity_enabled')
                    ->label('Чергування тижнів')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Назва')
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Дата початку')
                    ->date(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Дата закінчення')
                    ->date(),

                Tables\Columns\TextColumn::make('weeks')
                    ->label('Тижнів'),

                Tables\Columns\BooleanColumn::make('parity_enabled')
                    ->label('Чергування'),
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
