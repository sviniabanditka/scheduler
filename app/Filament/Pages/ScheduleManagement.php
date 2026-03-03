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

    public static function canAccess(): bool
    {
        return auth()->user()->isPlanner();
    }

    protected static ?string $title = 'Управління розкладом';

    // Filters
    public ?int $selectedVersion = null;
    public ?int $selectedGroup = null;
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?string $weekLabel = null;

    // Stats
    public bool $showStats = false;

    // Edit Modal
    public bool $showEditModal = false;
    public ?int $editingAssignmentId = null;
    public ?int $modalActivityId = null;
    public ?int $modalRoomId = null;
    public ?int $modalDayOfWeek = null;
    public ?int $modalSlotIndex = null;
    public ?string $modalParity = 'both';

    // Create Modal
    public bool $showCreateModal = false;
    public ?int $createActivityId = null;
    public ?int $createRoomId = null;
    public ?int $createDayOfWeek = null;
    public ?int $createSlotIndex = null;
    public ?string $createParity = 'both';

    // Stats Add Modal (for missing activities)
    public bool $showStatsAddModal = false;
    public ?int $statsActivityId = null;
    public ?int $statsAddRoomId = null;
    public ?int $statsAddDayOfWeek = null;
    public ?int $statsAddSlotIndex = null;
    public ?string $statsAddParity = 'both';

    // Stats Delete Modal (for excess activities)
    public bool $showStatsDeleteModal = false;
    public ?int $statsDeleteActivityId = null;
    public array $statsAssignmentsList = [];

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

        if ($today->gte($calStart) && $today->lte($calEnd)) {
            $monday = $today->copy()->startOfWeek(Carbon::MONDAY);
            $sunday = $monday->copy()->addDays(6);

            $this->startDate = max($monday, $calStart)->format('Y-m-d');
            $this->endDate = min($sunday, $calEnd)->format('Y-m-d');
        } else {
            $monday = $calStart->copy()->startOfWeek(Carbon::MONDAY);
            if ($monday->lt($calStart)) $monday = $calStart->copy();
            $sunday = $monday->copy()->startOfWeek(Carbon::MONDAY)->addDays(6);

            $this->startDate = $monday->format('Y-m-d');
            $this->endDate = min($sunday, $calEnd)->format('Y-m-d');
        }

        $this->updateWeekLabel();
    }

    protected function updateWeekLabel(): void
    {
        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate);
            $end = Carbon::parse($this->endDate);
            $this->weekLabel = $start->format('d.m') . ' — ' . $end->format('d.m');
        }
    }

    public function updatedSelectedVersion(): void
    {
        if ($this->selectedVersion) {
            $version = ScheduleVersion::find($this->selectedVersion);
            $this->setDefaultDates($version);
        }
    }

    public function prevWeek(): void
    {
        if (!$this->startDate || !$this->calendar) return;

        $monday = Carbon::parse($this->startDate)->startOfWeek(Carbon::MONDAY)->subWeek();
        $sunday = $monday->copy()->addDays(6);
        $calStart = $this->calendar->start_date;
        $calEnd = $this->calendar->end_date;

        if ($sunday->lt($calStart)) return;

        $this->startDate = max($monday, $calStart)->format('Y-m-d');
        $this->endDate = min($sunday, $calEnd)->format('Y-m-d');
        $this->updateWeekLabel();
    }

    public function nextWeek(): void
    {
        if (!$this->startDate || !$this->calendar) return;

        $monday = Carbon::parse($this->startDate)->startOfWeek(Carbon::MONDAY)->addWeek();
        $sunday = $monday->copy()->addDays(6);
        $calStart = $this->calendar->start_date;
        $calEnd = $this->calendar->end_date;

        if ($monday->gt($calEnd)) return;

        $this->startDate = max($monday, $calStart)->format('Y-m-d');
        $this->endDate = min($sunday, $calEnd)->format('Y-m-d');
        $this->updateWeekLabel();
    }

    public function currentWeek(): void
    {
        if (!$this->calendar) return;

        $today = Carbon::today();
        $calStart = $this->calendar->start_date;
        $calEnd = $this->calendar->end_date;

        if ($today->gte($calStart) && $today->lte($calEnd)) {
            $monday = $today->copy()->startOfWeek(Carbon::MONDAY);
        } elseif ($today->lt($calStart)) {
            $monday = $calStart->copy()->startOfWeek(Carbon::MONDAY);
            if ($monday->lt($calStart)) $monday = $calStart->copy();
        } else {
            $monday = $calEnd->copy()->startOfWeek(Carbon::MONDAY);
        }

        $sunday = $monday->copy()->startOfWeek(Carbon::MONDAY)->addDays(6);
        $this->startDate = max($monday, $calStart)->format('Y-m-d');
        $this->endDate = min($sunday, $calEnd)->format('Y-m-d');
        $this->updateWeekLabel();
    }

    public function getCanGoPrevProperty(): bool
    {
        if (!$this->startDate || !$this->calendar) return false;
        $monday = Carbon::parse($this->startDate)->startOfWeek(Carbon::MONDAY)->subWeek();
        $sunday = $monday->copy()->addDays(6);
        return $sunday->gte($this->calendar->start_date);
    }

    public function getCanGoNextProperty(): bool
    {
        if (!$this->startDate || !$this->calendar) return false;
        $monday = Carbon::parse($this->startDate)->startOfWeek(Carbon::MONDAY)->addWeek();
        return $monday->lte($this->calendar->end_date);
    }

    public function getWeekParityLabelProperty(): ?string
    {
        if (!$this->calendar || !$this->calendar->parity_enabled || !$this->startDate) return null;
        $parity = $this->calendar->getParityForDate(Carbon::parse($this->startDate));
        return $parity === 'num' ? 'Чисельник' : 'Знаменник';
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

            $dateParity = $calendar->getParityForDate($date);

            $dateRange[] = [
                'date' => $dateStr,
                'formatted' => $date->format('d.m'),
                'day_name' => $dayNames[$dayOfWeek] ?? '?',
                'day_of_week' => $dayOfWeek,
                'parity' => $dateParity,
            ];

            foreach ($timeSlots as $slot) {
                $matrix[$dateStr][$slot->slot_index] = [];
            }

            foreach ($assignments as $assignment) {
                if ($assignment->day_of_week !== $dayOfWeek) {
                    continue;
                }

                // Filter by parity: show only assignments matching this date's parity
                $ap = $assignment->parity;
                if ($ap !== 'both' && $dateParity !== 'both' && $ap !== $dateParity) {
                    continue;
                }

                $activity = $assignment->activity;
                if (!$activity) continue;

                $teachers = $activity->teachers->pluck('name')->join(', ');
                $groups = $activity->groups->pluck('name')->join(', ');

                $matrix[$dateStr][$assignment->slot_index][] = [
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
        $this->modalActivityId = $assignment->activity_id;
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

        // Load teachers/groups from the selected activity (may differ from original)
        $activity = Activity::with(['teachers', 'groups'])->find($this->modalActivityId);
        if (!$activity) {
            Notification::make()->title('Оберіть заняття')->warning()->send();
            return;
        }

        $teacherIds = $activity->teachers->pluck('id')->toArray();
        $groupIds = $activity->groups->pluck('id')->toArray();

        $conflict = $this->checkConflicts(
            $assignment->schedule_version_id,
            $assignment->id,
            $this->modalDayOfWeek,
            $this->modalSlotIndex,
            $this->modalParity,
            $this->modalRoomId,
            $teacherIds,
            $groupIds,
        );

        if ($conflict) {
            Notification::make()->title($conflict['title'])->body($conflict['body'])->danger()->send();
            return;
        }

        $assignment->update([
            'activity_id' => $this->modalActivityId,
            'room_id' => $this->modalRoomId,
            'day_of_week' => $this->modalDayOfWeek,
            'slot_index' => $this->modalSlotIndex,
            'parity' => $this->modalParity,
            'source' => 'manual',
        ]);

        $this->showEditModal = false;
        $this->editingAssignmentId = null;
        $this->modalActivityId = null;

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

        // Close edit modal if it was open for this assignment
        $this->showEditModal = false;
        $this->editingAssignmentId = null;
        $this->modalActivityId = null;

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
        $this->modalActivityId = null;
    }

    // --- Create Modal ---

    public function openCreateModal(int $day, int $slot): void
    {
        $this->createDayOfWeek = $day;
        $this->createSlotIndex = $slot;
        $this->createActivityId = null;
        $this->createRoomId = null;
        $this->createParity = 'both';
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->createActivityId = null;
    }

    public function createAssignment(): void
    {
        if (!$this->selectedVersion || !$this->createActivityId) {
            Notification::make()->title('Оберіть заняття')->warning()->send();
            return;
        }

        $version = ScheduleVersion::find($this->selectedVersion);
        if (!$version) return;

        $activity = Activity::with(['teachers', 'groups'])->find($this->createActivityId);
        if (!$activity) return;

        $teacherIds = $activity->teachers->pluck('id')->toArray();
        $groupIds = $activity->groups->pluck('id')->toArray();

        $conflict = $this->checkConflicts(
            $version->id,
            null,
            $this->createDayOfWeek,
            $this->createSlotIndex,
            $this->createParity,
            $this->createRoomId,
            $teacherIds,
            $groupIds,
        );

        if ($conflict) {
            Notification::make()->title($conflict['title'])->body($conflict['body'])->danger()->send();
            return;
        }

        ScheduleAssignment::create([
            'tenant_id' => $version->tenant_id,
            'schedule_version_id' => $version->id,
            'activity_id' => $this->createActivityId,
            'day_of_week' => $this->createDayOfWeek,
            'slot_index' => $this->createSlotIndex,
            'parity' => $this->createParity,
            'room_id' => $this->createRoomId,
            'locked' => false,
            'source' => 'manual',
        ]);

        $this->showCreateModal = false;
        $this->createActivityId = null;

        Notification::make()
            ->title('Додано!')
            ->body('Заняття додано до розкладу')
            ->success()
            ->send();
    }

    public function getAvailableActivitiesProperty()
    {
        if (!$this->selectedVersion) return collect();

        $version = ScheduleVersion::find($this->selectedVersion);
        if (!$version) return collect();

        return Activity::where('calendar_id', $version->calendar_id)
            ->with(['subject', 'teachers', 'groups'])
            ->orderBy('subject_id')
            ->get();
    }

    // --- Stats ---

    public function toggleStats(): void
    {
        $this->showStats = !$this->showStats;
    }

    public function getSubjectStatsProperty(): array
    {
        if (!$this->selectedVersion) return [];

        $version = ScheduleVersion::find($this->selectedVersion);
        if (!$version) return [];

        $activities = Activity::where('calendar_id', $version->calendar_id)
            ->with(['subject', 'teachers', 'groups'])
            ->get();

        $assignmentCounts = ScheduleAssignment::where('schedule_version_id', $version->id)
            ->selectRaw('activity_id, count(*) as cnt')
            ->groupBy('activity_id')
            ->pluck('cnt', 'activity_id');

        $stats = [];
        foreach ($activities as $activity) {
            $required = $activity->required_slots_per_period;
            $assigned = $assignmentCounts[$activity->id] ?? 0;
            $diff = $assigned - $required;

            $status = 'ok';
            if ($diff < 0) $status = 'missing';
            elseif ($diff > 0) $status = 'excess';

            $stats[] = [
                'activity_id' => $activity->id,
                'subject' => $activity->subject->name ?? '—',
                'type' => $activity->activity_type,
                'groups' => $activity->groups->pluck('name')->join(', '),
                'teachers' => $activity->teachers->pluck('name')->join(', '),
                'required' => $required,
                'assigned' => $assigned,
                'diff' => $diff,
                'status' => $status,
            ];
        }

        return $stats;
    }

    // --- Stats Add Modal (missing activities) ---

    public function openStatsAddModal(int $activityId): void
    {
        $this->statsActivityId = $activityId;
        $this->statsAddRoomId = null;
        $this->statsAddDayOfWeek = null;
        $this->statsAddSlotIndex = null;
        $this->statsAddParity = 'both';
        $this->showStatsAddModal = true;
    }

    public function closeStatsAddModal(): void
    {
        $this->showStatsAddModal = false;
        $this->statsActivityId = null;
    }

    public function createStatsAssignment(): void
    {
        if (!$this->selectedVersion || !$this->statsActivityId || !$this->statsAddDayOfWeek || !$this->statsAddSlotIndex) {
            Notification::make()->title('Заповніть всі поля')->warning()->send();
            return;
        }

        $version = ScheduleVersion::find($this->selectedVersion);
        if (!$version) return;

        $activity = Activity::with(['teachers', 'groups'])->find($this->statsActivityId);
        if (!$activity) return;

        $teacherIds = $activity->teachers->pluck('id')->toArray();
        $groupIds = $activity->groups->pluck('id')->toArray();

        $conflict = $this->checkConflicts(
            $version->id,
            null,
            $this->statsAddDayOfWeek,
            $this->statsAddSlotIndex,
            $this->statsAddParity,
            $this->statsAddRoomId,
            $teacherIds,
            $groupIds,
        );

        if ($conflict) {
            Notification::make()->title($conflict['title'])->body($conflict['body'])->danger()->send();
            return;
        }

        ScheduleAssignment::create([
            'tenant_id' => $version->tenant_id,
            'schedule_version_id' => $version->id,
            'activity_id' => $this->statsActivityId,
            'day_of_week' => $this->statsAddDayOfWeek,
            'slot_index' => $this->statsAddSlotIndex,
            'parity' => $this->statsAddParity,
            'room_id' => $this->statsAddRoomId,
            'locked' => false,
            'source' => 'manual',
        ]);

        $this->showStatsAddModal = false;
        $this->statsActivityId = null;

        Notification::make()
            ->title('Додано!')
            ->body('Заняття додано до розкладу')
            ->success()
            ->send();
    }

    // --- Stats Delete Modal (excess activities) ---

    public function openStatsDeleteModal(int $activityId): void
    {
        $this->statsDeleteActivityId = $activityId;

        if (!$this->selectedVersion) return;

        $assignments = ScheduleAssignment::where('schedule_version_id', $this->selectedVersion)
            ->where('activity_id', $activityId)
            ->with(['room'])
            ->get();

        $dayNames = [
            1 => 'Пн', 2 => 'Вт', 3 => 'Ср',
            4 => 'Чт', 5 => "Пт", 6 => 'Сб', 7 => 'Нд',
        ];

        $this->statsAssignmentsList = $assignments->map(fn ($a) => [
            'id' => $a->id,
            'day_name' => $dayNames[$a->day_of_week] ?? '?',
            'slot_index' => $a->slot_index,
            'parity' => $a->parity,
            'room' => $a->room->code ?? '—',
            'locked' => $a->locked,
        ])->toArray();

        $this->showStatsDeleteModal = true;
    }

    public function closeStatsDeleteModal(): void
    {
        $this->showStatsDeleteModal = false;
        $this->statsDeleteActivityId = null;
        $this->statsAssignmentsList = [];
    }

    public function deleteStatsAssignment(int $assignmentId): void
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

        // Refresh the list
        if ($this->statsDeleteActivityId) {
            $this->openStatsDeleteModal($this->statsDeleteActivityId);
        }

        Notification::make()
            ->title('Видалено')
            ->body('Заняття видалено з розкладу')
            ->success()
            ->send();
    }

    // --- Conflict Checking ---

    private function checkConflicts(
        int $versionId,
        ?int $excludeAssignmentId,
        int $dayOfWeek,
        int $slotIndex,
        string $parity,
        ?int $roomId,
        array $teacherIds,
        array $groupIds,
    ): ?array {
        $baseQuery = fn () => ScheduleAssignment::where('schedule_version_id', $versionId)
            ->when($excludeAssignmentId, fn ($q) => $q->where('id', '!=', $excludeAssignmentId))
            ->where('day_of_week', $dayOfWeek)
            ->where('slot_index', $slotIndex)
            ->where(function ($q) use ($parity) {
                $q->where('parity', 'both')
                    ->orWhere('parity', $parity)
                    ->orWhere(fn ($q2) => $q2->whereRaw("? = 'both'", [$parity]));
            });

        // Room conflict
        if ($roomId) {
            $roomConflict = $baseQuery()
                ->where('room_id', $roomId)
                ->with('activity.subject')
                ->first();
            if ($roomConflict) {
                return [
                    'title' => 'Конфлікт аудиторії!',
                    'body' => 'Аудиторія вже зайнята: ' . ($roomConflict->activity?->subject?->name ?? '—'),
                ];
            }
        }

        // Teacher conflict
        if (!empty($teacherIds)) {
            $teacherConflict = $baseQuery()
                ->whereHas('activity.teachers', fn ($q) => $q->whereIn('teachers.id', $teacherIds))
                ->with('activity.subject')
                ->first();
            if ($teacherConflict) {
                return [
                    'title' => 'Конфлікт викладача!',
                    'body' => 'Викладач вже має заняття: ' . ($teacherConflict->activity?->subject?->name ?? '—'),
                ];
            }
        }

        // Group conflict
        if (!empty($groupIds)) {
            $groupConflict = $baseQuery()
                ->whereHas('activity.groups', fn ($q) => $q->whereIn('groups.id', $groupIds))
                ->with('activity.subject')
                ->first();
            if ($groupConflict) {
                return [
                    'title' => 'Конфлікт групи!',
                    'body' => 'Група вже має заняття: ' . ($groupConflict->activity?->subject?->name ?? '—'),
                ];
            }
        }

        return null;
    }
}
