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

    protected static ?string $navigationLabel = 'Управління розкладом';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationGroup = 'Розклад';

    public ?int $selectedCourse = null;
    public ?int $selectedGroup = null;
    public ?string $startDate = null;
    public ?string $endDate = null;

    public function mount(): void
    {
        // Set default date range to current week
        $today = new \DateTime();
        $dayOfWeek = $today->format('N'); // 1 = Monday, 7 = Sunday
        
        // Calculate start of week (Monday)
        $startOfWeek = clone $today;
        $startOfWeek->modify('-' . ($dayOfWeek - 1) . ' days');
        
        // Calculate end of week (Sunday)
        $endOfWeek = clone $startOfWeek;
        $endOfWeek->modify('+6 days');
        
        $this->startDate = $startOfWeek->format('Y-m-d');
        $this->endDate = $endOfWeek->format('Y-m-d');
        
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
                    ->placeholder('Оберіть курс'),
                
                Select::make('selectedGroup')
                    ->label('Група')
                    ->options(function () {
                        if (!$this->selectedCourse) {
                            return [];
                        }
                        return Group::where('course_id', $this->selectedCourse)->pluck('name', 'id');
                    })
                    ->reactive()
                    ->placeholder('Оберіть групу'),
                
                \Filament\Forms\Components\DatePicker::make('startDate')
                    ->label('Дата початку')
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->validateDateRange()),
                
                \Filament\Forms\Components\DatePicker::make('endDate')
                    ->label('Дата закінчення')
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->validateDateRange()),
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
                    ->formatStateUsing(fn (int $state): string => Schedule::DAYS_OF_WEEK[$state] ?? 'Невідомо')
                    ->sortable(),
                
                TextColumn::make('time_slot')
                    ->label('Час')
                    ->sortable(),
                
                TextColumn::make('subject.name')
                    ->label('Предмет')
                    ->searchable(),
                
                TextColumn::make('teacher.name')
                    ->label('Викладач')
                    ->searchable(),
                
                TextColumn::make('classroom')
                    ->label('Аудиторія')
                    ->placeholder('Не вказана'),
                
                TextColumn::make('week_number')
                    ->label('Тиждень')
                    ->formatStateUsing(fn (?int $state): string => $state ? "Тиждень {$state}" : 'Всі тижні')
                    ->sortable(),
                
                TextColumn::make('date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->placeholder('Не вказана (повторюване)')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('edit')
                    ->label('Редагувати')
                    ->icon('heroicon-o-pencil')
                    ->form([
                        Select::make('group_id')
                            ->label('Група')
                            ->options(Group::all()->pluck('name', 'id'))
                            ->required(),
                        
                        Select::make('subject_id')
                            ->label('Предмет')
                            ->options(Subject::all()->pluck('name', 'id'))
                            ->required(),
                        
                        Select::make('teacher_id')
                            ->label('Викладач')
                            ->options(Teacher::all()->pluck('name', 'id'))
                            ->required(),
                        
                        Select::make('day_of_week')
                            ->label('День тижня')
                            ->options(Schedule::DAYS_OF_WEEK)
                            ->required(),
                        
                        Select::make('time_slot')
                            ->label('Час')
                            ->options(Schedule::TIME_SLOTS)
                            ->required(),
                        
                        Select::make('week_number')
                            ->label('Тиждень')
                            ->options(array_combine(range(1, 52), array_map(fn($i) => "Тиждень {$i}", range(1, 52))))
                            ->nullable(),
                        
                        \Filament\Forms\Components\DatePicker::make('date')
                            ->label('Дата')
                            ->nullable()
                            ->helperText('Залиште пустим для повторюваного заняття'),
                        
                        TextInput::make('classroom')
                            ->label('Аудиторія')
                            ->maxLength(50),
                    ])
                    ->fillForm(fn (Schedule $record): array => [
                        'group_id' => $record->group_id,
                        'subject_id' => $record->subject_id,
                        'teacher_id' => $record->teacher_id,
                        'day_of_week' => $record->day_of_week,
                        'time_slot' => $record->time_slot,
                        'week_number' => $record->week_number,
                        'date' => $record->date,
                        'classroom' => $record->classroom,
                    ])
                    ->action(function (Schedule $record, array $data): void {
                        $this->validateSchedule($data, $record->id);
                        $record->update($data);
                    }),
                
                Action::make('delete')
                    ->label('Видалити')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Schedule $record) => $record->delete()),
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

        if ($this->startDate && $this->endDate) {
            $query->where(function ($q) {
                $q->where(function ($subQ) {
                    // Schedules with specific date within range
                    $subQ->whereNotNull('date')
                         ->whereBetween('date', [$this->startDate, $this->endDate]);
                })->orWhere(function ($subQ) {
                    // Schedules without date restrictions (recurring)
                    $subQ->whereNull('date');
                });
            });
        }

        return $query;
    }

    public function validateDateRange(): void
    {
        if ($this->startDate && $this->endDate) {
            $start = new \DateTime($this->startDate);
            $end = new \DateTime($this->endDate);
            $diff = $start->diff($end);
            
            if ($diff->days > 14) {
                $this->addError('endDate', 'Діапазон дат не може перевищувати 2 тижні (14 днів)');
            }
            
            if ($start > $end) {
                $this->addError('endDate', 'Дата закінчення не може бути раніше дати початку');
            }
        }
    }

    protected function validateSchedule(array $data, ?int $excludeId = null): void
    {
        $query = Schedule::where('group_id', $data['group_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('time_slot', $data['time_slot']);

        if (isset($data['week_number']) && $data['week_number'] !== null && $data['week_number'] !== '') {
            $query->where('week_number', $data['week_number']);
        } else {
            $query->whereNull('week_number');
        }

        if (isset($data['date']) && $data['date'] !== null && $data['date'] !== '') {
            $query->where('date', $data['date']);
        } else {
            $query->whereNull('date');
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw new \Exception('Конфлікт розкладу: група вже має заняття в цей час');
        }

        $teacherQuery = Schedule::where('teacher_id', $data['teacher_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('time_slot', $data['time_slot']);

        if (isset($data['week_number']) && $data['week_number'] !== null && $data['week_number'] !== '') {
            $teacherQuery->where('week_number', $data['week_number']);
        } else {
            $teacherQuery->whereNull('week_number');
        }

        if (isset($data['date']) && $data['date'] !== null && $data['date'] !== '') {
            $teacherQuery->where('date', $data['date']);
        } else {
            $teacherQuery->whereNull('date');
        }

        if ($excludeId) {
            $teacherQuery->where('id', '!=', $excludeId);
        }

        if ($teacherQuery->exists()) {
            throw new \Exception('Конфлікт розкладу: викладач вже зайнятий в цей час');
        }
    }
}
