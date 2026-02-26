<?php

namespace App\Filament\Pages;

use App\Models\Calendar;
use App\Models\Group;
use App\Models\Room;
use App\Models\ScheduleAssignment;
use App\Models\ScheduleVersion;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ScheduleManagement extends Page implements HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.pages.schedule-management';

    protected static ?string $navigationLabel = 'Управління розкладом';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationGroup = 'Розклад';

    public ?int $selectedVersion = null;
    public ?int $selectedGroup = null;

    public function mount(): void
    {
        // Find latest published version for current tenant
        $latestVersion = ScheduleVersion::where('status', 'published')
            ->latest('published_at')
            ->first();

        if ($latestVersion) {
            $this->selectedVersion = $latestVersion->id;
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('selectedVersion')
                    ->label('Версія розкладу')
                    ->options(
                        ScheduleVersion::with('calendar')
                            ->orderByDesc('created_at')
                            ->get()
                            ->mapWithKeys(fn ($v) => [
                                $v->id => "{$v->name} ({$v->status}) — {$v->calendar->name}",
                            ])
                    )
                    ->reactive()
                    ->placeholder('Оберіть версію'),
                
                Select::make('selectedGroup')
                    ->label('Група')
                    ->options(Group::where('active', true)->pluck('name', 'id'))
                    ->reactive()
                    ->placeholder('Всі групи'),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('day_of_week')
                    ->label('День тижня')
                    ->formatStateUsing(function (int $state): string {
                        $days = [
                            1 => 'Понеділок', 2 => 'Вівторок', 3 => 'Середа',
                            4 => 'Четвер', 5 => "П'ятниця", 6 => 'Субота', 7 => 'Неділя',
                        ];
                        return $days[$state] ?? 'Невідомо';
                    })
                    ->sortable(),
                
                TextColumn::make('slot_index')
                    ->label('Пара')
                    ->sortable(),
                
                TextColumn::make('activity.subject.name')
                    ->label('Предмет')
                    ->searchable(),
                
                TextColumn::make('activity.activity_type')
                    ->label('Тип')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'lecture' => 'info',
                        'lab' => 'warning',
                        'seminar' => 'success',
                        'practice' => 'primary',
                        'pc' => 'gray',
                        default => 'gray',
                    }),
                
                TextColumn::make('activity.teachers.name')
                    ->label('Викладач')
                    ->searchable(),
                
                TextColumn::make('room.code')
                    ->label('Аудиторія')
                    ->sortable(),
                
                TextColumn::make('parity')
                    ->label('Парність')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'both' => 'Обидва',
                        'num' => 'Чисельник',
                        'den' => 'Знаменник',
                        default => $state,
                    }),
                
                TextColumn::make('source')
                    ->label('Джерело')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'solver' => 'success',
                        'manual' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([])
            ->actions([
                Action::make('delete')
                    ->label('Видалити')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (ScheduleAssignment $record) => $record->delete()),
            ])
            ->bulkActions([
                BulkAction::make('delete')
                    ->label('Видалити обрані')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Collection $records) => $records->each->delete()),
            ])
            ->defaultSort('day_of_week')
            ->defaultSort('slot_index');
    }

    protected function getTableQuery(): Builder
    {
        $query = ScheduleAssignment::query()
            ->with(['activity.subject', 'activity.teachers', 'room']);

        if ($this->selectedVersion) {
            $query->where('schedule_version_id', $this->selectedVersion);
        } else {
            $query->whereRaw('1 = 0'); // No version selected = no results
        }

        if ($this->selectedGroup) {
            $query->whereHas('activity', function (Builder $q) {
                $q->whereHas('groups', function (Builder $gq) {
                    $gq->where('group_id', $this->selectedGroup);
                });
            });
        }

        return $query;
    }
}
