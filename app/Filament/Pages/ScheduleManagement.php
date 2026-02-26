<?php

namespace App\Filament\Pages;

use App\Models\Calendar;
use App\Models\Course;
use App\Models\Group;
use App\Models\Room;
use App\Models\ScheduleAssignment;
use App\Models\ScheduleVersion;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimeSlot;
use App\Models\Activity;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ScheduleManagement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.pages.schedule-management';

    protected static ?string $navigationLabel = 'Управління розкладом';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationGroup = 'Розклад';

    protected static ?string $title = 'Управління розкладом';

    // Filters
    public ?int $selectedVersion = null;
    public ?int $selectedGroup = null;
    public ?string $startDate = null;
    public ?string $endDate = null;

    // Modal
    public bool $showEditModal = false;
    public ?int $editingAssignmentId = null;
    public ?int $modalRoomId = null;
    public ?int $modalDayOfWeek = null;
    public ?int $modalSlotIndex = null;
    public ?string $modalParity = 'both';

    public function mount(): void
    {
        // Default to latest version
        $latestVersion = ScheduleVersion::latest('created_at')->first();
        if ($latestVersion) {
            $this->selectedVersion = $latestVersion->id;
            $this->setDefaultDates($latestVersion);
        }
    }

    protected function setDefaultDates(?ScheduleVersion $version): void
    {
        if (!$version) return;

        $calendar = Calendar::find($version->calendar_id);
        if (!$calendar) return;

        $today = Carbon::today();
        $calStart = $calendar->start_date;
        $calEnd = $calendar->end_date;

        // If today is within calendar, use current week
        if ($today->gte($calStart) && $today->lte($calEnd)) {
            $dayOfWeek = $today->dayOfWeek === 0 ? 7 : $today->dayOfWeek;
            $monday = $today->copy()->subDays($dayOfWeek - 1);
            $sunday = $monday->copy()->addDays(6);

            $this->startDate = max($monday, $calStart)->format('Y-m-d');
            $this->endDate = min($sunday, $calEnd)->format('Y-m-d');
        } else {
            // Use first week of calendar
            $this->startDate = $calStart->format('Y-m-d');
            $endOfFirstWeek = $calStart->copy()->addDays(6);
            $this->endDate = min($endOfFirstWeek, $calEnd)->format('Y-m-d');
        }
    }

    public function updatedSelectedVersion(): void
    {
        if ($this->selectedVersion) {
            $version = ScheduleVersion::find($this->selectedVersion);
            $this->setDefaultDates($version);
        }
    }

    // Computed properties
    public function getVersionsProperty()
    {
        return ScheduleVersion::with('calendar')
            ->withCount('assignments')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getGroupsProperty()
    {
        return Group::where('active', true)->orderBy('name')->get();
    }

    public function getCalendarProperty()
    {
        if (!$this->selectedVersion) return null;
        $version = ScheduleVersion::find($this->selectedVersion);
        return $version ? Calendar::find($version->calendar_id) : null;
    }

    public function getTimeSlotsProperty()
    {
        $calendar = $this->calendar;
        if (!$calendar) return collect();

        // Time slots are stored per day_of_week, so we need distinct slot_indexes
        return TimeSlot::where('calendar_id', $calendar->id)
            ->where('enabled', true)
            ->selectRaw('slot_index, MIN(start_time) as start_time, MIN(end_time) as end_time')
            ->groupBy('slot_index')
            ->orderBy('slot_index')
            ->get();
    }

    public function getScheduleDataProperty(): array
    {
        if (!$this->selectedVersion || !$this->startDate || !$this->endDate) {
            return ['matrix' => [], 'dateRange' => []];
        }

        $version = ScheduleVersion::find($this->selectedVersion);
        if (!$version) {
            return ['matrix' => [], 'dateRange' => []];
        }

        // Get calendar for date validation
        $calendar = Calendar::find($version->calendar_id);
        if (!$calendar) {
            return ['matrix' => [], 'dateRange' => []];
        }

        $requestStart = Carbon::parse($this->startDate);
        $requestEnd = Carbon::parse($this->endDate);
        $calStart = $calendar->start_date;
        $calEnd = $calendar->end_date;

        // Clamp to calendar range
        $effectiveStart = $requestStart->lt($calStart) ? $calStart->copy() : $requestStart;
        $effectiveEnd = $requestEnd->gt($calEnd) ? $calEnd->copy() : $requestEnd;

        if ($effectiveStart->gt($effectiveEnd)) {
            return ['matrix' => [], 'dateRange' => []];
        }

        // Get assignments
        $query = ScheduleAssignment::where('schedule_version_id', $version->id)
            ->with(['activity.subject', 'activity.teachers', 'activity.groups', 'room']);

        if ($this->selectedGroup) {
            $query->whereHas('activity', function ($q) {
                $q->whereHas('groups', fn ($gq) => $gq->where('groups.id', $this->selectedGroup));
            });
        }

        $assignments = $query->get();

        $timeSlots = $this->timeSlots;

        $dayNames = [
            1 => 'Пн', 2 => 'Вт', 3 => 'Ср',
            4 => 'Чт', 5 => "Пт", 6 => 'Сб', 7 => 'Нд',
        ];

        $matrix = [];
        $dateRange = [];

        for ($date = $effectiveStart->copy(); $date->lte($effectiveEnd); $date->addDay()) {
            $dayOfWeek = $date->dayOfWeek === 0 ? 7 : $date->dayOfWeek;
            $dateStr = $date->format('Y-m-d');

            $dateRange[] = [
                'date' => $dateStr,
                'formatted' => $date->format('d.m'),
                'day_name' => $dayNames[$dayOfWeek] ?? '?',
                'day_of_week' => $dayOfWeek,
            ];

            foreach ($timeSlots as $slot) {
                $matrix[$dateStr][$slot->slot_index] = null;
            }

            foreach ($assignments as $assignment) {
                if ($assignment->day_of_week === $dayOfWeek) {
                    $activity = $assignment->activity;
                    if (!$activity) continue;

                    $teachers = $activity->teachers->pluck('name')->join(', ');
                    $groups = $activity->groups->pluck('name')->join(', ');

                    $matrix[$dateStr][$assignment->slot_index] = [
                        'id' => $assignment->id,
                        'subject' => $activity->subject->name ?? '—',
                        'type' => $activity->activity_type,
                        'teacher' => $teachers,
                        'groups' => $groups,
                        'room' => $assignment->room->code ?? '',
                        'room_title' => $assignment->room->title ?? '',
                        'parity' => $assignment->parity,
                        'locked' => $assignment->locked,
                        'source' => $assignment->source,
                    ];
                }
            }
        }

        return ['matrix' => $matrix, 'dateRange' => $dateRange];
    }

    public function getRoomsProperty()
    {
        return Room::where('active', true)->orderBy('code')->get();
    }

    public function getActivitiesProperty()
    {
        if (!$this->selectedVersion) return collect();

        $version = ScheduleVersion::find($this->selectedVersion);
        if (!$version) return collect();

        return Activity::where('calendar_id', $version->calendar_id)
            ->with(['subject', 'teachers', 'groups'])
            ->get();
    }

    // Actions
    public function openEditModal(int $assignmentId): void
    {
        $assignment = ScheduleAssignment::find($assignmentId);
        if (!$assignment) return;

        $this->editingAssignmentId = $assignment->id;
        $this->modalRoomId = $assignment->room_id;
        $this->modalDayOfWeek = $assignment->day_of_week;
        $this->modalSlotIndex = $assignment->slot_index;
        $this->modalParity = $assignment->parity;
        $this->showEditModal = true;
    }

    public function saveAssignment(): void
    {
        $assignment = ScheduleAssignment::find($this->editingAssignmentId);
        if (!$assignment) {
            Notification::make()->title('Запис не знайдено')->danger()->send();
            return;
        }

        // Validate: check for conflicts
        $conflict = ScheduleAssignment::where('schedule_version_id', $assignment->schedule_version_id)
            ->where('id', '!=', $assignment->id)
            ->where('day_of_week', $this->modalDayOfWeek)
            ->where('slot_index', $this->modalSlotIndex)
            ->where(function ($q) {
                $q->where('parity', 'both')
                    ->orWhere('parity', $this->modalParity)
                    ->orWhere(fn ($q2) => $q2->whereRaw("? = 'both'", [$this->modalParity]));
            })
            ->where(function ($q) use ($assignment) {
                // Same room conflict
                if ($this->modalRoomId) {
                    $q->where('room_id', $this->modalRoomId);
                }
            })
            ->first();

        if ($conflict && $this->modalRoomId) {
            // Check room conflict
            $roomConflict = ScheduleAssignment::where('schedule_version_id', $assignment->schedule_version_id)
                ->where('id', '!=', $assignment->id)
                ->where('day_of_week', $this->modalDayOfWeek)
                ->where('slot_index', $this->modalSlotIndex)
                ->where('room_id', $this->modalRoomId)
                ->where(function ($q) {
                    $q->where('parity', 'both')
                        ->orWhere('parity', $this->modalParity)
                        ->orWhere(fn ($q2) => $q2->whereRaw("? = 'both'", [$this->modalParity]));
                })
                ->with('activity.subject')
                ->first();

            if ($roomConflict) {
                $conflictSubject = $roomConflict->activity?->subject?->name ?? '—';
                Notification::make()
                    ->title('Конфлікт аудиторії!')
                    ->body("Аудиторія вже зайнята: {$conflictSubject}")
                    ->danger()
                    ->send();
                return;
            }
        }

        // Check teacher conflict
        $activityTeacherIds = $assignment->activity?->teachers?->pluck('id')->toArray() ?? [];
        if (!empty($activityTeacherIds)) {
            $teacherConflict = ScheduleAssignment::where('schedule_version_id', $assignment->schedule_version_id)
                ->where('id', '!=', $assignment->id)
                ->where('day_of_week', $this->modalDayOfWeek)
                ->where('slot_index', $this->modalSlotIndex)
                ->where(function ($q) {
                    $q->where('parity', 'both')
                        ->orWhere('parity', $this->modalParity)
                        ->orWhere(fn ($q2) => $q2->whereRaw("? = 'both'", [$this->modalParity]));
                })
                ->whereHas('activity.teachers', function ($q) use ($activityTeacherIds) {
                    $q->whereIn('teachers.id', $activityTeacherIds);
                })
                ->with('activity.subject')
                ->first();

            if ($teacherConflict) {
                $conflictSubject = $teacherConflict->activity?->subject?->name ?? '—';
                Notification::make()
                    ->title('Конфлікт викладача!')
                    ->body("Викладач вже має заняття: {$conflictSubject}")
                    ->danger()
                    ->send();
                return;
            }
        }

        // Check group conflict
        $activityGroupIds = $assignment->activity?->groups?->pluck('id')->toArray() ?? [];
        if (!empty($activityGroupIds)) {
            $groupConflict = ScheduleAssignment::where('schedule_version_id', $assignment->schedule_version_id)
                ->where('id', '!=', $assignment->id)
                ->where('day_of_week', $this->modalDayOfWeek)
                ->where('slot_index', $this->modalSlotIndex)
                ->where(function ($q) {
                    $q->where('parity', 'both')
                        ->orWhere('parity', $this->modalParity)
                        ->orWhere(fn ($q2) => $q2->whereRaw("? = 'both'", [$this->modalParity]));
                })
                ->whereHas('activity.groups', function ($q) use ($activityGroupIds) {
                    $q->whereIn('groups.id', $activityGroupIds);
                })
                ->with('activity.subject')
                ->first();

            if ($groupConflict) {
                $conflictSubject = $groupConflict->activity?->subject?->name ?? '—';
                Notification::make()
                    ->title('Конфлікт групи!')
                    ->body("Група вже має заняття: {$conflictSubject}")
                    ->danger()
                    ->send();
                return;
            }
        }

        $assignment->update([
            'room_id' => $this->modalRoomId,
            'day_of_week' => $this->modalDayOfWeek,
            'slot_index' => $this->modalSlotIndex,
            'parity' => $this->modalParity,
            'source' => 'manual',
        ]);

        $this->showEditModal = false;
        $this->editingAssignmentId = null;

        Notification::make()
            ->title('Збережено!')
            ->body('Заняття успішно оновлено')
            ->success()
            ->send();
    }

    public function deleteAssignment(int $assignmentId): void
    {
        $assignment = ScheduleAssignment::find($assignmentId);
        if (!$assignment) return;

        if ($assignment->locked) {
            Notification::make()
                ->title('Заблоковано')
                ->body('Цей запис заблоковано і не може бути видалено')
                ->warning()
                ->send();
            return;
        }

        $assignment->delete();

        Notification::make()
            ->title('Видалено')
            ->body('Заняття видалено з розкладу')
            ->success()
            ->send();
    }

    public function toggleLock(int $assignmentId): void
    {
        $assignment = ScheduleAssignment::find($assignmentId);
        if (!$assignment) return;

        $assignment->update(['locked' => !$assignment->locked]);

        $label = $assignment->locked ? 'заблоковано' : 'розблоковано';
        Notification::make()
            ->title("Заняття {$label}")
            ->success()
            ->send();
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingAssignmentId = null;
    }
}
