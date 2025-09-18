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
                
                Select::make('selectedWeek')
                    ->label('Тиждень')
                    ->options(array_combine(range(1, 52), array_map(fn($i) => "Тиждень {$i}", range(1, 52))))
                    ->reactive()
                    ->placeholder('Всі тижні'),
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
            throw new \Exception('Конфлікт розкладу: група вже має заняття в цей час');
        }

        $teacherQuery = Schedule::where('teacher_id', $data['teacher_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('time_slot', $data['time_slot']);

        if ($excludeId) {
            $teacherQuery->where('id', '!=', $excludeId);
        }

        if ($teacherQuery->exists()) {
            throw new \Exception('Конфлікт розкладу: викладач вже зайнятий в цей час');
        }
    }
}
