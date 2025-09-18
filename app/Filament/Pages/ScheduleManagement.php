<?php

namespace App\Filament\Pages;

use App\Models\Course;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Teacher;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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

    protected static ?string $navigationLabel = 'Управление расписанием';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationGroup = 'Расписание';

    public ?int $selectedCourse = null;
    public ?int $selectedGroup = null;
    public ?int $selectedWeek = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('selectedCourse')
                    ->label('Курс')
                    ->options(Course::all()->pluck('name', 'id'))
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->selectedGroup = null)
                    ->placeholder('Выберите курс'),
                
                Select::make('selectedGroup')
                    ->label('Группа')
                    ->options(function () {
                        if (!$this->selectedCourse) {
                            return [];
                        }
                        return Group::where('course_id', $this->selectedCourse)->pluck('name', 'id');
                    })
                    ->reactive()
                    ->placeholder('Выберите группу'),
                
                Select::make('selectedWeek')
                    ->label('Неделя')
                    ->options(array_combine(range(1, 52), array_map(fn($i) => "Неделя {$i}", range(1, 52))))
                    ->reactive()
                    ->placeholder('Все недели'),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('day_of_week')
                    ->label('День недели')
                    ->formatStateUsing(fn (int $state): string => Schedule::DAYS_OF_WEEK[$state] ?? 'Неизвестно')
                    ->sortable(),
                
                TextColumn::make('time_slot')
                    ->label('Время')
                    ->sortable(),
                
                TextColumn::make('subject.name')
                    ->label('Предмет')
                    ->searchable(),
                
                TextColumn::make('teacher.name')
                    ->label('Преподаватель')
                    ->searchable(),
                
                TextColumn::make('classroom')
                    ->label('Аудитория')
                    ->placeholder('Не указана'),
                
                TextColumn::make('week_number')
                    ->label('Неделя')
                    ->formatStateUsing(fn (?int $state): string => $state ? "Неделя {$state}" : 'Все недели')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('edit')
                    ->label('Редактировать')
                    ->icon('heroicon-o-pencil')
                    ->form([
                        Select::make('group_id')
                            ->label('Группа')
                            ->options(Group::all()->pluck('name', 'id'))
                            ->required(),
                        
                        Select::make('subject_id')
                            ->label('Предмет')
                            ->options(Subject::all()->pluck('name', 'id'))
                            ->required(),
                        
                        Select::make('teacher_id')
                            ->label('Преподаватель')
                            ->options(Teacher::all()->pluck('name', 'id'))
                            ->required(),
                        
                        Select::make('day_of_week')
                            ->label('День недели')
                            ->options(Schedule::DAYS_OF_WEEK)
                            ->required(),
                        
                        Select::make('time_slot')
                            ->label('Время')
                            ->options(Schedule::TIME_SLOTS)
                            ->required(),
                        
                        Select::make('week_number')
                            ->label('Неделя')
                            ->options(array_combine(range(1, 52), array_map(fn($i) => "Неделя {$i}", range(1, 52))))
                            ->nullable(),
                        
                        TextInput::make('classroom')
                            ->label('Аудитория')
                            ->maxLength(50),
                    ])
                    ->fillForm(fn (Schedule $record): array => [
                        'group_id' => $record->group_id,
                        'subject_id' => $record->subject_id,
                        'teacher_id' => $record->teacher_id,
                        'day_of_week' => $record->day_of_week,
                        'time_slot' => $record->time_slot,
                        'week_number' => $record->week_number,
                        'classroom' => $record->classroom,
                    ])
                    ->action(function (Schedule $record, array $data): void {
                        $this->validateSchedule($data, $record->id);
                        $record->update($data);
                    }),
                
                Action::make('delete')
                    ->label('Удалить')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Schedule $record) => $record->delete()),
            ])
            ->bulkActions([
                BulkAction::make('delete')
                    ->label('Удалить выбранные')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Collection $records) => $records->each->delete()),
            ])
            ->defaultSort('day_of_week')
            ->defaultSort('time_slot');
    }

    protected function getTableQuery(): Builder
    {
        $query = Schedule::query()
            ->with(['group.course', 'subject', 'teacher']);

        if ($this->selectedGroup) {
            $query->where('group_id', $this->selectedGroup);
        } elseif ($this->selectedCourse) {
            $query->whereHas('group', function (Builder $q) {
                $q->where('course_id', $this->selectedCourse);
            });
        }

        if ($this->selectedWeek) {
            $query->where('week_number', $this->selectedWeek);
        }

        return $query;
    }

    protected function validateSchedule(array $data, ?int $excludeId = null): void
    {
        $query = Schedule::where('group_id', $data['group_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('time_slot', $data['time_slot']);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw new \Exception('Конфликт расписания: группа уже имеет занятие в это время');
        }

        $teacherQuery = Schedule::where('teacher_id', $data['teacher_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('time_slot', $data['time_slot']);

        if ($excludeId) {
            $teacherQuery->where('id', '!=', $excludeId);
        }

        if ($teacherQuery->exists()) {
            throw new \Exception('Конфликт расписания: преподаватель уже занят в это время');
        }
    }
}
