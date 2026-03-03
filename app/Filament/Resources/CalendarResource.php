<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CalendarResource\Pages;
use App\Models\Calendar;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
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
                Forms\Components\Section::make('Основні параметри')
                    ->schema([
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

                        Forms\Components\Toggle::make('parity_enabled')
                            ->label('Чергування тижнів')
                            ->default(false),

                        Forms\Components\Select::make('first_week_parity')
                            ->label('Перший тиждень')
                            ->options([
                                'num' => 'Чисельник',
                                'den' => 'Знаменник',
                            ])
                            ->default('num')
                            ->helperText('З якого тижня починається календар'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Налаштування пар')
                    ->description('Задайте кількість пар та тривалість для автоматичної генерації, потім відкоригуйте час кожної пари вручну')
                    ->schema([
                        Forms\Components\TextInput::make('slots_per_day')
                            ->label('Пар на день')
                            ->integer()
                            ->default(6)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                $slotsCount = (int) $state;
                                if ($slotsCount > 0 && $slotsCount <= 10) {
                                    $duration = (int) ($get('slot_duration_minutes') ?: 90);
                                    $break = (int) ($get('break_duration_minutes') ?: 10);
                                    $set('slot_times', Calendar::generateDefaultSlotTimes($slotsCount, $duration, $break));
                                }
                            }),

                        Forms\Components\TextInput::make('slot_duration_minutes')
                            ->label('Тривалість пари (хв)')
                            ->integer()
                            ->default(90)
                            ->helperText('Для початкової генерації часів')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                $duration = (int) $state;
                                $slots = (int) ($get('slots_per_day') ?: 6);
                                $break = (int) ($get('break_duration_minutes') ?: 10);
                                if ($duration > 0 && $slots > 0) {
                                    $set('slot_times', Calendar::generateDefaultSlotTimes($slots, $duration, $break));
                                }
                            }),

                        Forms\Components\TextInput::make('break_duration_minutes')
                            ->label('Тривалість перерви (хв)')
                            ->integer()
                            ->default(10)
                            ->helperText('Для початкової генерації часів')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                $break = (int) $state;
                                $slots = (int) ($get('slots_per_day') ?: 6);
                                $duration = (int) ($get('slot_duration_minutes') ?: 90);
                                if ($slots > 0 && $duration > 0) {
                                    $set('slot_times', Calendar::generateDefaultSlotTimes($slots, $duration, $break));
                                }
                            }),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Розклад пар')
                    ->description('Час початку та закінчення кожної пари. Можна відкоригувати вручну (наприклад, довша перерва після 3-ї пари)')
                    ->schema([
                        Forms\Components\Repeater::make('slot_times')
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('slot_number')
                                    ->label('Пара №')
                                    ->integer()
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('start_time')
                                    ->label('Початок')
                                    ->required()
                                    ->placeholder('08:30')
                                    ->mask('99:99'),

                                Forms\Components\TextInput::make('end_time')
                                    ->label('Кінець')
                                    ->required()
                                    ->placeholder('10:00')
                                    ->mask('99:99'),
                            ])
                            ->columns(3)
                            ->default(Calendar::generateDefaultSlotTimes(6, 90, 10))
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->itemLabel(fn (array $state): string => isset($state['slot_number']) ? "Пара {$state['slot_number']}" : 'Пара')
                            ->columnSpanFull(),
                    ]),
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

                Tables\Columns\TextColumn::make('slots_per_day')
                    ->label('Пар/день'),

                Tables\Columns\IconColumn::make('parity_enabled')
                    ->label('Чергування')
                    ->boolean(),
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
