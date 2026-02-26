<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RescheduleRequestResource\Pages;
use App\Models\RescheduleRequest;
use App\Models\TeacherPreferenceRule;
use App\Services\RescheduleService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RescheduleRequestResource extends Resource
{
    protected static ?string $model = RescheduleRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Заявки на перенос';

    protected static ?string $modelLabel = 'Заявка на перенос';

    protected static ?string $pluralModelLabel = 'Заявки на перенос';

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationGroup = 'Розклад';

    public static function canAccess(): bool
    {
        return auth()->user()->isPlanner();
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) RescheduleRequest::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Викладач')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('assignment.activity.title')
                    ->label('Предмет')
                    ->wrap(),

                Tables\Columns\TextColumn::make('assignment')
                    ->label('Поточний слот')
                    ->formatStateUsing(function ($record) {
                        $a = $record->assignment;
                        if (!$a) return '-';
                        $day = TeacherPreferenceRule::DAY_NAMES[$a->day_of_week] ?? $a->day_of_week;
                        return "{$day}, пара {$a->slot_index}";
                    }),

                Tables\Columns\TextColumn::make('proposed_day_of_week')
                    ->label('Запропонований слот')
                    ->formatStateUsing(function ($record) {
                        $day = TeacherPreferenceRule::DAY_NAMES[$record->proposed_day_of_week] ?? $record->proposed_day_of_week;
                        return "{$day}, пара {$record->proposed_slot_index}";
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending' => 'Очікує',
                        'approved' => 'Затверджено',
                        'rejected' => 'Відхилено',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('teacher_comment')
                    ->label('Коментар')
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Очікує',
                        'approved' => 'Затверджено',
                        'rejected' => 'Відхилено',
                    ])
                    ->default('pending'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Затвердити')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (RescheduleRequest $record) => $record->isPending())
                    ->form([
                        Forms\Components\Textarea::make('admin_comment')
                            ->label('Коментар')
                            ->rows(2),
                    ])
                    ->action(function (RescheduleRequest $record, array $data) {
                        $service = app(RescheduleService::class);
                        $conflicts = $service->validateProposal($record);

                        if (!empty($conflicts)) {
                            Notification::make()
                                ->title('Є конфлікти')
                                ->body(implode(', ', $conflicts))
                                ->danger()
                                ->send();
                            return;
                        }

                        $service->approve($record, auth()->user(), $data['admin_comment'] ?? null);

                        Notification::make()
                            ->title('Заявку затверджено')
                            ->body('Створено нову версію розкладу з переносом')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Затвердити перенос?'),

                Tables\Actions\Action::make('reject')
                    ->label('Відхилити')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (RescheduleRequest $record) => $record->isPending())
                    ->form([
                        Forms\Components\Textarea::make('admin_comment')
                            ->label('Причина відмови')
                            ->rows(2),
                    ])
                    ->action(function (RescheduleRequest $record, array $data) {
                        $service = app(RescheduleService::class);
                        $service->reject($record, auth()->user(), $data['admin_comment'] ?? null);

                        Notification::make()
                            ->title('Заявку відхилено')
                            ->warning()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Відхилити перенос?'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRescheduleRequests::route('/'),
        ];
    }
}
