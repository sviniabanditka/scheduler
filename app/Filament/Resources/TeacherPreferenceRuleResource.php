<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherPreferenceRuleResource\Pages;
use App\Models\Teacher;
use App\Models\TeacherPreferenceRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeacherPreferenceRuleResource extends Resource
{
    protected static ?string $model = TeacherPreferenceRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Правила переваг';

    protected static ?string $modelLabel = 'Правило';

    protected static ?string $pluralModelLabel = 'Правила переваг';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        $user = auth()->user();
        return $user && $user->isTeacher() ? 'Мій кабінет' : 'Управління даними';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user->isAdmin() || $user->isTeacher();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->isTeacher() && $user->teacher_id) {
            $query->where('teacher_id', $user->teacher_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $isTeacher = $user->isTeacher();

        return $form
            ->schema([
                Forms\Components\Select::make('teacher_id')
                    ->label('Викладач')
                    ->options(fn () => Teacher::pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->visible(fn () => !$isTeacher)
                    ->default(fn () => $isTeacher ? $user->teacher_id : null),

                Forms\Components\Hidden::make('teacher_id')
                    ->default(fn () => $user->teacher_id)
                    ->visible(fn () => $isTeacher),

                Forms\Components\Select::make('rule_type')
                    ->label('Тип правила')
                    ->options(TeacherPreferenceRule::RULE_TYPES)
                    ->required()
                    ->live()
                    ->columnSpanFull(),

                // Day selector — shown for day-related rules
                Forms\Components\Select::make('params.day_of_week')
                    ->label('День тижня')
                    ->options(TeacherPreferenceRule::DAY_NAMES)
                    ->visible(fn (Get $get): bool => in_array($get('rule_type'), [
                        'unavailable_day', 'unavailable_slot', 'preferred_slot',
                        'min_start_slot', 'max_end_slot',
                    ]))
                    ->required(fn (Get $get): bool => $get('rule_type') === 'unavailable_day'),

                // Slot selector — shown for slot-related rules
                Forms\Components\TextInput::make('params.slot_index')
                    ->label('Номер пари')
                    ->integer()
                    ->minValue(1)
                    ->maxValue(8)
                    ->visible(fn (Get $get): bool => in_array($get('rule_type'), [
                        'unavailable_slot', 'preferred_slot',
                    ])),

                // Min slot
                Forms\Components\TextInput::make('params.min_slot')
                    ->label('Мінімальна пара')
                    ->integer()
                    ->minValue(1)
                    ->maxValue(8)
                    ->helperText('Не ставити пари раніше цього номера')
                    ->visible(fn (Get $get): bool => $get('rule_type') === 'min_start_slot'),

                // Max slot
                Forms\Components\TextInput::make('params.max_slot')
                    ->label('Максимальна пара')
                    ->integer()
                    ->minValue(1)
                    ->maxValue(8)
                    ->helperText('Не ставити пари пізніше цього номера')
                    ->visible(fn (Get $get): bool => $get('rule_type') === 'max_end_slot'),

                // Max hours per day
                Forms\Components\TextInput::make('params.max_hours')
                    ->label('Макс. пар на день')
                    ->integer()
                    ->minValue(1)
                    ->maxValue(8)
                    ->visible(fn (Get $get): bool => $get('rule_type') === 'max_hours_per_day'),

                Forms\Components\TextInput::make('weight')
                    ->label('Вага')
                    ->integer()
                    ->default(10)
                    ->minValue(1)
                    ->maxValue(100)
                    ->helperText('Чим більше — тим важливіше правило'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Активне')
                    ->default(true),

                Forms\Components\Textarea::make('comment')
                    ->label('Коментар')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Викладач')
                    ->searchable()
                    ->sortable()
                    ->visible(fn () => $user->isAdmin()),

                Tables\Columns\TextColumn::make('rule_type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => TeacherPreferenceRule::RULE_TYPES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'unavailable_day', 'unavailable_slot' => 'danger',
                        'preferred_slot' => 'success',
                        'min_start_slot', 'max_end_slot' => 'warning',
                        'max_hours_per_day' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->label('Опис')
                    ->wrap(),

                Tables\Columns\TextColumn::make('weight')
                    ->label('Вага')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активне')
                    ->boolean(),

                Tables\Columns\TextColumn::make('comment')
                    ->label('Коментар')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('Викладач')
                    ->options(fn () => Teacher::pluck('name', 'id'))
                    ->visible(fn () => $user->isAdmin()),

                Tables\Filters\SelectFilter::make('rule_type')
                    ->label('Тип правила')
                    ->options(TeacherPreferenceRule::RULE_TYPES),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активне'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeacherPreferenceRules::route('/'),
            'create' => Pages\CreateTeacherPreferenceRule::route('/create'),
            'edit' => Pages\EditTeacherPreferenceRule::route('/{record}/edit'),
        ];
    }
}
